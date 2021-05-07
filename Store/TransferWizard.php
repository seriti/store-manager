<?php
namespace App\Store;

use Exception;

use Seriti\Tools\Wizard;
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Validate;
use Seriti\Tools\Doc;
use Seriti\Tools\Calc;
use Seriti\Tools\Secure;
use Seriti\Tools\Plupload;
use Seriti\Tools\STORAGE;
use Seriti\Tools\BASE_UPLOAD;
use Seriti\Tools\UPLOAD_TEMP;
use Seriti\Tools\UPLOAD_DOCS;
use Seriti\Tools\TABLE_USER;
use Seriti\Tools\SITE_NAME;

use App\Store\Helpers;


class TransferWizard extends Wizard 
{
    protected $user;
    protected $user_id;
    protected $table_prefix;
    
    //configure
    public function setup($param = []) 
    {
        $this->table_prefix = TABLE_PREFIX;
        
        $this->user = $this->getContainer('user');
        $this->user_id = $this->user->getId();

        $param['bread_crumbs'] = true;
        $param['strict_var'] = false;
        $param['csrf_token'] = $this->getContainer('user')->getCsrfToken();
        parent::setup($param);

        //standard user cols
        $this->addVariable(array('id'=>'from_store_id','type'=>'INTEGER','title'=>'From store','required'=>true));
        $this->addVariable(array('id'=>'to_store_id','type'=>'INTEGER','title'=>'To Store','required'=>true));
        $this->addVariable(array('id'=>'date','type'=>'DATE','title'=>'Transfer date','required'=>true,'new'=>date('Y-m-d')));
        $this->addVariable(array('id'=>'note','type'=>'TEXT','title'=>'notes','required'=>false));
        
        $this->addVariable(array('id'=>'item_count','type'=>'INTEGER','title'=>'Item count','required'=>false));
        $this->addVariable(array('id'=>'confirm_action','type'=>'STRING','title'=>'Confirmation action','new'=>'NONE'));
        $this->addVariable(array('id'=>'confirm_email','type'=>'EMAIL','title'=>'Confirmation email address','required'=>false));
        
        //define pages and templates
        $this->addPage(1,'Setup','store/transfer_page1.php',['go_back'=>true]);
        $this->addPage(2,'Add items','store/transfer_page2.php');
        $this->addPage(3,'Confirm details','store/transfer_page3.php');
        $this->addPage(4,'Summary','store/transfer_page4.php',['final'=>true]);
            

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';

        //validate transfer from and to stores
        if($this->page_no == 1) {

            
            $from_store_id = $this->form['from_store_id'];
            $to_store_id = $this->form['to_store_id'];

            if($from_store_id === $to_store_id) {
                $this->addError('To store cannot be same as From store!');
            } else {
                $this->data['from_store'] = Helpers::get($this->db,TABLE_PREFIX,'store',$from_store_id);
                $this->data['to_store'] = Helpers::get($this->db,TABLE_PREFIX,'store',$to_store_id);
            }
        } 
        
        //process all transfer items and calculate totals
        if($this->page_no == 2) {

            //get stock item list for validation, calculation and messages
            $sql = 'SELECT SS.data_id,SS.stock_id,CONCAT(I.name,"(",SU.name," - ",S.invoice_no,")") AS name,'.
                         'I.units,I.units_kg_convert,SS.quantity,C.access_level '.
                   'FROM '.TABLE_PREFIX.'stock_store AS SS JOIN '.TABLE_PREFIX.'stock AS S ON(SS.stock_id = S.stock_id) '.
                         'JOIN '.TABLE_PREFIX.'supplier AS SU ON(S.supplier_id = SU.supplier_id) '.
                         'JOIN '.TABLE_PREFIX.'item AS I ON(S.item_id = I.item_id) '.
                         'JOIN '.TABLE_PREFIX.'item_category AS C ON(I.category_id = C.category_id) '.
                   'WHERE SS.store_id = "'.$this->db->escapeSql($this->form['from_store_id']).'" AND SS.quantity > 0 AND I.status <> "HIDE" '.
                   'ORDER BY C.sort, I.name';
            $store_stock = $this->db->readSqlArray($sql);

            $item_count = $this->form['item_count'];
            $amount_min = 0.01;
            $amount_max = 10000000; 
            $total_kg = 0;
             
            //NB: item count can have blank rows due to deletes
            $n = 0;
            //reset items
            $this->data['items'] = [];
            for($i = 1; $i <= $item_count; $i++) {
                $item_id = 'item_'.$i;
                $amount_id = 'amount_'.$i;
                if(isset($_POST[$item_id])) {
                    $n++;
                    $item = [];
                    $item['id'] = $_POST[$item_id];
                    $item['amount'] = abs($_POST[$amount_id]);
                    if(!is_numeric($item['id']) or !isset($store_stock[$item['id']])) {
                        $this->addError('Invalid stock store data ID['.$item['id'].']');
                    } else {
                        $stock = $store_stock[$item['id']];
                        $item_desc = $stock['name'].' - amount';
                        //NB: amount_max is stock quantity available in FROM store
                        $amount_max = $stock['quantity'];
                        Validate::number($item_desc,$amount_min,$amount_max,$item['amount'],$error_str);
                        if($error_str !== '') $this->addError($error_str);

                        $item['stock_id'] = $stock['stock_id'];
                        $item['name'] = $stock['name'];
                        $item['kg'] = $item['amount'] * $stock['units_kg_convert'];

                        $total_kg += $item['kg'];
                    } 

                    $this->data['items'][] = $item;
                }

                $this->data['total_kg'] = $total_kg;

                //reset item count
                $this->form['item_count'] = $n;
                $this->data['item_count'] = $n;

            }    

        }  
        
        //save transfer, email confirmation
        if($this->page_no == 3) {
             
            $this->db->executeSql('START TRANSACTION',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not START transaction');

            //finally update cart/order with all details
            if(!$this->errors_found) {
                $table_transfer = $this->table_prefix.'transfer';
                $table_item = $this->table_prefix.'transfer_item';
                $data = [];
                

                $transfer = [];
                $transfer['from_store_id'] = $this->form['from_store_id'];
                $transfer['to_store_id'] = $this->form['to_store_id'];
                $transfer['item_no'] = $this->data['item_count'];
                $transfer['total_kg'] = $this->data['total_kg'];
                $transfer['date'] = date('Y-m-d');
                $transfer['note'] = $this->form['note'];
                $transfer['status'] = 'NEW';

                $transfer_id = $this->db->insertRecord($table_transfer,$transfer,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'We could not save transfer details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                }
            }

            if(!$this->errors_found) {
                $this->data['transfer_id'] = $transfer_id;

                foreach($this->data['items'] as $item) {
                    $transfer_item = [];
                    $transfer_item['transfer_id'] = $transfer_id;
                    $transfer_item['stock_id'] = $item['stock_id'];
                    $transfer_item['quantity'] = $item['amount'];
                    $transfer_item['total_kg'] = $item['kg'];
                    
                    $this->db->insertRecord($table_item,$transfer_item,$error_tmp);
                    if($error_tmp !== '') {
                        $error = 'We could not save transfer item['.$item['name'].']';
                        if($this->debug) $error .= $error_tmp;
                        $this->addError($error);
                    }

                }
            }

            //finally update stock quantities in FROM store only as transfer status = NEW and NOT confirmed yet
            if(!$this->errors_found) {
                foreach($this->data['items'] as $item) {
                    $quantity = abs($item['amount']) * -1;
                    Helpers::updateStockInStore($this->db,TABLE_PREFIX,$this->form['from_store_id'],$item['stock_id'],$quantity,$error_tmp);
                    if($error_tmp !== '') {
                        $error = 'We could not update FROM store amounts for item['.$item['name'].']';
                        if($this->debug) $error .= $error_tmp;
                        $this->addError($error);
                    }
                    /*
                    Helpers::updateStockTransfered($this->db,TABLE_PREFIX,'FROM',$this->form['from_store_id'],$this->form['to_store_id'],$item['stock_id'],$item['amount'],$error_tmp);
                    if($error_tmp !== '') {
                        $error = 'We could not update store amounts for item['.$item['name'].']';
                        if($this->debug) $error .= $error_tmp;
                        $this->addError($error);
                    }
                    */
                }
            }    

            if($this->errors_found) {
                $this->db->executeSql('ROLLBACK',$error_tmp);
                if($error_tmp !== '') $this->addError('Could not ROLLBACK transaction');
            } else {
                $this->db->executeSql('COMMIT',$error_tmp);
                if($error_tmp !== '') $this->addError('Could not COMMIT transaction');
            }

            //finally email transfer details if requested
            //NB: not implemented yet as unlikely requirement
            if(!$this->errors_found) {
                
                /*
                if($this->form['confirm_action'] === 'EMAIL') {
                    $param = ['cc_admin'=>true,'email'=>$this->form['confirm_email']];
                    $subject = 'Transfer Confirmation';
                    $message = $this->form['note'];
                    Helpers::sendTransferConfirmation($this->db,$this->table_prefix,$this->container,$transfer_id,$subject,$message,$param,$error_tmp);
                    if($error_tmp !== '') {
                        $message = 'We could not email transfer details, but your transfer has been successfully processed.';
                        if($this->debug) $message .= $error_tmp;
                        $this->addMessage($message);
                    } else {
                        $this->addMessage('Successfully emailed transfer details to: '.$this->form['confirm_email']);
                    }
                }
                */
            }
        } 

        //final page so no fucking processing possible moron
        if($this->page_no == 4) {

            

            

            
        } 
    }

    public function setupPageData($no)
    {
        //if($no == 3) {}
        /*

        //NB: TEMP COOKIE CAN OUTLIVE USER LOGIN SESSION if did NOT select []remember me option
        if($this->user_id == 0 and isset($this->data['user_id'])) {
            unset($this->data['user_id']);
            $this->saveData('data');
        }



        //setup user data ONCE only, if a user is logged in
        if($this->user_id != 0 and !isset($this->data['user_id'])) {
            $this->data['user_id'] = $this->user_id;    
            $this->data['user_name'] = $this->user->getName();
            $this->data['user_email'] = $this->user->getEmail();

            $this->saveData('data');

            //get extended user info
            $sql = 'SELECT * FROM '.$this->table_prefix.'user_extend WHERE user_id = "'.$this->user_id.'" ';
            $user_extend = $this->db->readSqlRecord($sql);
            
            if($user_extend != 0) {
                $this->form['user_email_alt'] = $user_extend['email_alt'];
                $this->form['user_cell'] = $user_extend['cell'];
                $this->form['user_ship_address'] = $user_extend['ship_address'];
                $this->form['user_bill_address'] = $user_extend['bill_address'];

                //NB: need to save $this->data as required in subsequent pages
                $this->saveData('form');
            }    
        }
        */
    }

}

?>


