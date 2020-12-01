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


class OrderWizard extends Wizard 
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
        $this->addVariable(array('id'=>'supplier_id','type'=>'INTEGER','title'=>'Supplier','required'=>true));
        $this->addVariable(array('id'=>'location_id','type'=>'INTEGER','title'=>'Location','required'=>true));
        $this->addVariable(array('id'=>'date_receive','type'=>'DATE','title'=>'Date','required'=>true,'new'=>date('Y-m-d')));
        $this->addVariable(array('id'=>'note','type'=>'TEXT','title'=>'notes','required'=>false));
        
        $this->addVariable(array('id'=>'item_count','type'=>'INTEGER','title'=>'Item count','required'=>false));
        $this->addVariable(array('id'=>'confirm_action','type'=>'STRING','title'=>'Confirmation action','new'=>'EMAIL'));
        $this->addVariable(array('id'=>'supplier_email','type'=>'EMAIL','title'=>'Supplier email address','required'=>true));
        
        //define pages and templates
        $this->addPage(1,'Setup','store/order_page1.php',['go_back'=>true]);
        $this->addPage(2,'Add items','store/order_page2.php');
        $this->addPage(3,'Confirm details','store/order_page3.php');
        $this->addPage(4,'Summary','store/order_page4.php',['final'=>true]);
            

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';

        //supplier info
        if($this->page_no == 1) {

            
            $supplier_id = $this->form['supplier_id'];
            $location_id = $this->form['location_id'];

            $this->data['supplier'] = Helpers::get($this->db,TABLE_PREFIX,'supplier',$supplier_id);
            $this->data['location'] = Helpers::get($this->db,TABLE_PREFIX,'location',$location_id);
            
            

        } 
        
        //process all order items and calculate totals
        if($this->page_no == 2) {

            //get item list for validation and messages
            $sql = 'SELECT item_id,name FROM '.TABLE_PREFIX.'item '.
                   'WHERE status <> "HIDE" '.
                   'ORDER BY name';
            $items = $this->db->readSqlList($sql);

            $item_count = $this->form['item_count'];
            $amount_min = 0.01;
            $amount_max = 10000000; 
            $price_min = 0.01;
            $price_max = 10000000; 
            $subtotal = 0;
            $tax = 0;
            $total = 0;
             
            //NB: item count can have blank rows due to deletes
            $n = 0;
            //reset items
            $this->data['items'] = [];
            for($i = 1; $i <= $item_count; $i++) {
                $item_id = 'item_'.$i;
                $amount_id = 'amount_'.$i;
                $price_id = 'price_'.$i;
                if(isset($_POST[$item_id])) {
                    $n++;
                    $item = [];
                    $item['id'] = $_POST[$item_id];
                    $item['amount'] = abs($_POST[$amount_id]);
                    $item['price'] = abs($_POST[$price_id]);
                    if(!is_numeric($item['id']) or !isset($items[$item['id']])) {
                        $this->addError('Invalid Item ID['.$item['id'].']');
                    } else {
                        $item_desc = $items[$item['id']].' - amount';
                        Validate::number($item_desc,$amount_min,$amount_max,$item['amount'],$error_str);
                        if($error_str !== '') $this->addError($error_str);

                        $item_desc = $items[$item['id']].' - price';
                        Validate::number($item_desc,$price_min,$price_max,$item['price'],$error_str);
                        if($error_str !== '') $this->addError($error_str);

                        $item['subtotal'] = round(($item['amount']*$item['price']),2);
                        $item['tax'] = round(($item['subtotal']*TAX_RATE),2);
                        $item['total'] = $item['subtotal'] + $item['tax'];

                        $subtotal += $item['subtotal'];
                        $tax += $item['tax'];
                        $total += $item['total'];
                    } 

                    $this->data['items'][] = $item;
                }
            }

            $this->data['item_subtotal'] = $subtotal;
            $this->data['item_tax'] = $tax;
            $this->data['item_total'] = $total;

            //reset item count
            $this->form['item_count'] = $n;
            $this->data['item_count'] = $n;
        }  
        
        //save order, email supplier
        if($this->page_no == 3) {
             
            $this->db->executeSql('START TRANSACTION',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not START transaction');

            //finally update cart/order with all details
            if(!$this->errors_found) {
                $table_order = $this->table_prefix.'order';
                $table_item = $this->table_prefix.'order_item';
                $data = [];
                

                $order = [];
                $order['supplier_id'] = $this->form['supplier_id'];
                $order['location_id'] = $this->form['location_id'];
                $order['item_no'] = $this->data['item_count'];
                $order['date_create'] = date('Y-m-d');
                $order['date_receive'] = $this->form['date_receive'];
                $order['subtotal'] = $this->data['item_subtotal'];
                $order['tax'] = $this->data['item_tax'];
                $order['total'] = $this->data['item_total'];
                $order['note'] = $this->form['note'];
                $order['status'] = 'NEW';

                $order_id = $this->db->insertRecord($table_order,$order,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'We could not save order details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                }
            }

            if(!$this->errors_found) {
                $this->data['order_id'] = $order_id;

                foreach($this->data['items'] as $item) {
                    $order_item = [];
                    $order_item['order_id'] = $order_id;
                    $order_item['item_id'] = $item['id'];
                    $order_item['quantity'] = $item['amount'];
                    $order_item['price'] = $item['price']; 
                    $order_item['subtotal'] = $item['subtotal'];
                    $order_item['tax'] = $item['tax'];
                    $order_item['total'] = $item['total'];

                    $this->db->insertRecord($table_item,$order_item,$error_tmp);
                    if($error_tmp !== '') {
                        $error = 'We could not save order item['.$item['id'].']';
                        if($this->debug) $error .= $error_tmp;
                        $this->addError($error);
                    }

                }
            }    

            if($this->errors_found) {
                $this->db->executeSql('ROLLBACK',$error_tmp);
                if($error_tmp !== '') $this->addError('Could not ROLLBACK transaction');
            } else {
                $this->db->executeSql('COMMIT',$error_tmp);
                if($error_tmp !== '') $this->addError('Could not COMMIT transaction');
            }

            //finally SETUP payment gateway form if that option requested, or email EFT instructions
            if(!$this->errors_found) {
                
                if($this->form['confirm_action'] === 'EMAIL') {
                    
                    //NB: if no email specified then uses default supplier email
                    $param = ['cc_admin'=>true,'email'=>$this->form['supplier_email']];
                    $subject = 'Confirmation';
                    $message = $this->form['note'];
                    Helpers::sendOrderConfirmation($this->db,$this->table_prefix,$this->container,$order_id,$subject,$message,$param,$error_tmp);
                    if($error_tmp !== '') {
                        $message = 'We could not email supplier order details, but your order has been successfully saved.';
                        if($this->debug) $message .= $error_tmp;
                        $this->addMessage($message);
                    } else {
                        $this->addMessage('Successfully emailed order details to Supplier at: '.$this->form['supplier_email']);
                    }
                }
                
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


