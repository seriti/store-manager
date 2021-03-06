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


class DeliverWizard extends Wizard 
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
        $this->addVariable(array('id'=>'client_id','type'=>'INTEGER','title'=>'Client','required'=>true));
        $this->addVariable(array('id'=>'client_location_id','type'=>'INTEGER','title'=>'Client Location','required'=>true));
        $this->addVariable(array('id'=>'client_order_no','type'=>'STRING','title'=>'Client Order No.','required'=>true,'new'=>'NA'));
        $this->addVariable(array('id'=>'store_id','type'=>'INTEGER','title'=>'Store','required'=>true));
        $this->addVariable(array('id'=>'date_deliver','type'=>'DATE','title'=>'Date','required'=>true,'new'=>date('Y-m-d')));
        $this->addVariable(array('id'=>'note','type'=>'TEXT','title'=>'Notes','required'=>false));
        
        $this->addVariable(array('id'=>'item_count','type'=>'INTEGER','title'=>'Item count','required'=>false));
        $this->addVariable(array('id'=>'confirm_action','type'=>'STRING','title'=>'Confirmation action','new'=>'NONE'));
        $this->addVariable(array('id'=>'confirm_email','type'=>'EMAIL','title'=>'Client email address','required'=>false));
        
        //define pages and templates
        $this->addPage(1,'Setup','store/deliver_page1.php',['go_back'=>true]);
        $this->addPage(2,'Add items','store/deliver_page2.php');
        $this->addPage(3,'Confirm details','store/deliver_page3.php');
        $this->addPage(4,'Summary','store/deliver_page4.php',['final'=>true]);
            

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';

        //supplier info
        if($this->page_no == 1) {

            
            $client_id = $this->form['client_id'];
            $client_location_id = $this->form['client_location_id'];
            $store_id = $this->form['store_id'];

            $this->data['client'] = Helpers::get($this->db,TABLE_PREFIX,'client',$client_id);
            $this->data['location'] = Helpers::get($this->db,TABLE_PREFIX,'client_location',$client_location_id,'location_id');
            $this->data['store'] = Helpers::get($this->db,TABLE_PREFIX,'store',$store_id);
            

            //*** Sales functionality not implemented yet ***
            $sale_id = 0;
            if($sale_id != 0) {
                $sale = Helpers::getSaleDetails($this->db,TABLE_PREFIX,$sale_id,$error_tmp);
                if($error_tmp != '') {
                    $this->addError('Invalid Sale ID['.$sale_id.'] :'.$error_tmp);
                } else {
                    if($sale['sale']['client_id'] !== $client_id) {
                        $sale_client = Helpers::get($this->db,TABLE_PREFIX,'supplier',$sale['sale']['client_id']);
                       $this->addError('Sale ID['.$sale_id.'] references Client['.$sale_client['name'].'] which is NOT '.$this->data['client']['name']);
                    } else {
                        $this->data['sale'] = $sale['sale'];

                        $items = [];
                        //NB: *** will need to convert from sale item_id to selected store stock data_id ****
                        foreach($sale['items'] AS $sale_item) {
                            $item = [];
                            $item['id'] = $sale_item['item_id'];
                            $item['amount'] = $sale_item['quantity'];
                            $item['price'] = $sale_item['price'];
                            $item['subtotal'] = $sale_item['subtotal'];
                            $item['tax'] = $sale_item['tax'];
                            $item['total'] = $sale_item['total'];

                            $items[] = $item;
                        }

                        $this->data['items'] = $items;
                        $this->data['item_count'] = count($items);
                    }
                }
            } else {
                //user can capture delivery without linking to an sale
                $this->data['sale'] = 0;
                $this->data['items'] = [];
                $this->data['item_count'] = 0;
            }
            
            

        } 
        
        //process all delivery items and calculate totals
        if($this->page_no == 2) {
            
            //get item list for validation, messages, templates
            $sql = 'SELECT item_id,name FROM '.TABLE_PREFIX.'item '.
                   'WHERE status <> "HIDE" '.
                   'ORDER BY name';
            $items = $this->db->readSqlList($sql);

            $item_count = $this->form['item_count'];
            $amount_min = 0.01;
            $amount_max = 10000000; 
            $price_min = 0.01;
            $price_max = 10000000; 
            $total_min = 1.00;
            $total_max = 100000000; 
            $subtotal = 0;
            $tax = 0;
            $total = 0;
            //allowable rounding error
            $calc_error = 1;
             
            //NB: item count can have blank rows due to deletes
            $n = 0;
            //reset items
            $this->data['items'] = [];
            for($i = 1; $i <= $item_count; $i++) {
                //NB: item_id refers to stock_store.data_id
                $item_id = 'item_'.$i;
                $amount_id = 'amount_'.$i;
                $price_id = 'price_'.$i;
                $subtotal_id = 'subtotal_'.$i;
                $tax_id = 'tax_'.$i;
                $total_id = 'total_'.$i;
                if(isset($_POST[$item_id]) and $_POST[$item_id] != 0) {
                    $n++;
                    $item = [];
                    
                    $item['id'] = $_POST[$item_id];
                    $item['amount'] = abs($_POST[$amount_id]);
                    $item['price'] = abs($_POST[$price_id]);
                    $item['subtotal'] = abs($_POST[$subtotal_id]);
                    $item['tax'] = abs($_POST[$tax_id]);
                    $item['total'] = abs($_POST[$total_id]);

                    //save additional item details
                    $stock = Helpers::getStockInStoreId($this->db,TABLE_PREFIX,$item['id']);
                    $item['stock_id'] = $stock['stock_id'];
                    $item['item_id'] = $stock['item_id'];
                    $item['name'] = $items[$item['id']];

                    if($stock['store_id'] !== $this->form['store_id']) {
                       $this->addError('Stock Store Item ID['.$item['id'].'] has INVALID store ID['.$stock['store_id'].'] NOT same as delivery store ID['.$this->form['store_id'].']'); 
                    }

                    if(!is_numeric($item['id']) or !isset($items[$item['item_id']])) {
                        $this->addError('Invalid Stock Store Item ID['.$item['id'].']');
                    } else {
                        $item_desc = $item['name'].' - amount';
                        Validate::number($item_desc,$amount_min,$amount_max,$item['amount'],$error_str);
                        if($error_str !== '') $this->addError($error_str);

                        $item_desc = $item['name'].' - price';
                        Validate::number($item_desc,$price_min,$price_max,$item['price'],$error_str);
                        if($error_str !== '') $this->addError($error_str);

                        $item_desc = $item['name'].' - subtotal';
                        Validate::number($item_desc,$total_min,$total_max,$item['subtotal'],$error_str);
                        if($error_str !== '') $this->addError($error_str);

                        $item_desc = $item['name'].' - tax';
                        Validate::number($item_desc,$total_min,$total_max,$item['tax'],$error_str);
                        if($error_str !== '') $this->addError($error_str);

                        $item_desc = $item['name'].' - total';
                        Validate::number($item_desc,$total_min,$total_max,$item['total'],$error_str);
                        if($error_str !== '') $this->addError($error_str);
                        
                        if(PRICE_TAX_INCLUSIVE) {
                            $calc_total = round(($item['amount'] * $item['price']),2);
                            $calc_subtotal = round(($calc_total / (1 + TAX_RATE)),2);
                            $calc_tax = $calc_total - $calc_subtotal;
                        } else {
                            $calc_subtotal = round(($item['amount'] * $item['price']),2);
                            $calc_tax = round(($calc_subtotal * TAX_RATE),2);
                            $calc_total = $item['subtotal'] + $item['tax'];
                        }
                        
                        if(abs($calc_subtotal - $item['subtotal']) > $calc_error)  {
                            $this->addError($item['name'].' calculated subtotal['.$calc_subtotal.'] NOT = input['.$item['subtotal'].']');
                        }
                        if(abs($calc_tax - $item['tax']) > $calc_error)  {
                            $this->addError($item['name'].' calculated tax['.$calc_tax.'] NOT = input['.$item['tax'].']');
                        }
                        if(abs($calc_total - $item['total']) > $calc_error)  {
                            $this->addError($item['name'].' calculated total['.$calc_total.'] NOT = input['.$item['total'].' ]');
                        }

                        
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
        
        //save delivery, email client
        if($this->page_no == 3) {
             
            $this->db->executeSql('START TRANSACTION',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not START transaction');

            //finally update reception with all details
            if(!$this->errors_found) {
                //$table_sale = $this->table_prefix.'sale';
                $table_deliver = $this->table_prefix.'deliver';
                $table_deliver_item = $this->table_prefix.'deliver_item';
                $table_stock = $this->table_prefix.'stock';
                $table_stock_store = $this->table_prefix.'stock_store';
                $data = [];
                

                $deliver = [];
                $deliver['client_id'] = $this->form['client_id'];
                $deliver['client_location_id'] = $this->form['client_location_id'];
                $deliver['client_order_no'] = $this->form['client_order_no'];
                $deliver['store_id'] = $this->form['store_id'];
                //$deliver['sale_id'] = $this->form['sale_id'];
                $deliver['item_no'] = $this->data['item_count'];
                $deliver['date'] = date('Y-m-d');
                $deliver['subtotal'] = $this->data['item_subtotal'];
                $deliver['tax'] = $this->data['item_tax'];
                $deliver['total'] = $this->data['item_total'];
                $deliver['note'] = $this->form['note'];
                $deliver['status'] = 'NEW';

                $deliver_id = $this->db->insertRecord($table_deliver,$deliver,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'We could not save delivery details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                }
            }

            if(!$this->errors_found) {
                $this->data['deliver_id'] = $deliver_id;
                //NB: item['id'] is stock_id in the store
                foreach($this->data['items'] as $item) {
                    $deliver_item = [];
                    $deliver_item['deliver_id'] = $deliver_id;
                    $deliver_item['stock_id'] = $item['stock_id'];
                    $deliver_item['quantity'] = $item['amount'];
                    $deliver_item['price'] = $item['price']; 
                    $deliver_item['subtotal'] = $item['subtotal'];
                    $deliver_item['tax'] = $item['tax'];
                    $deliver_item['total'] = $item['total'];
                    
                    $this->db->insertRecord($table_deliver_item,$deliver_item,$error_tmp);
                    if($error_tmp !== '') {
                        //NB: $item['id'] refers to stock_store data_id
                        $error .= 'We could not save delivery stock store item['.$item['id'].']';
                        if($this->debug) $error .= $error_tmp;
                        $this->addError($error);
                    }

                    //NB: $item['id'] links to stock_store store_id and stock_id
                    Helpers::updateStockDelivered($this->db,TABLE_PREFIX,$deliver['store_id'],$item['stock_id'],$deliver_item['quantity'],$error_tmp);
                    if($error_tmp !== '') {
                        $error .= 'We could not update stock store item['.$item['id'].'] ';
                        $error .= $error_tmp; //if($this->debug)
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
                    
                    //NB: if no email specified then uses default client email
                    $param = ['cc_admin'=>true,'email'=>$this->form['confirm_email']];
                    $subject = 'Confirmation';
                    $message = $this->form['note'];
                             
                    Helpers::sendDeliverConfirmation($this->db,$this->table_prefix,$this->container,$deliver_id,$subject,$message,$param,$error_tmp);
                    if($error_tmp !== '') {
                        $message = 'We could not email client delivery details, but your delivery has been successfully saved.';
                        if($this->debug) $message .= $error_tmp;
                        $this->addMessage($message);
                    } else {
                        $this->addMessage('Successfully emailed delivery details to client at: '.$this->form['confirm_email']);
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


