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


class ReceiveWizard extends Wizard 
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
        $this->addVariable(array('id'=>'store_id','type'=>'INTEGER','title'=>'Store','required'=>true));
        $this->addVariable(array('id'=>'order_id','type'=>'INTEGER','title'=>'Order','required'=>true,'new'=>0));
        $this->addVariable(array('id'=>'date_receive','type'=>'DATE','title'=>'Date','required'=>true,'new'=>date('Y-m-d')));
        $this->addVariable(array('id'=>'invoice_no','type'=>'STRING','title'=>'Invoice No.','required'=>true));
        $this->addVariable(array('id'=>'note','type'=>'TEXT','title'=>'Notes','required'=>false));
        
        $this->addVariable(array('id'=>'item_count','type'=>'INTEGER','title'=>'Item count','required'=>false));
        $this->addVariable(array('id'=>'confirm_action','type'=>'STRING','title'=>'Confirmation action','new'=>'NONE'));
        $this->addVariable(array('id'=>'confirm_email','type'=>'EMAIL','title'=>'Supplier email address','required'=>true));
        
        //define pages and templates
        $this->addPage(1,'Setup','store/receive_page1.php',['go_back'=>true]);
        $this->addPage(2,'Add items','store/receive_page2.php');
        $this->addPage(3,'Confirm details','store/receive_page3.php');
        $this->addPage(4,'Summary','store/receive_page4.php',['final'=>true]);
            

    }

    public function processPage() 
    {
        $error = '';
        $error_tmp = '';

        //NB: if true and order_id set then assumes entire order processed. Can update order status manually 
        $update_order = false;

        //supplier info
        if($this->page_no == 1) {

            
            $supplier_id = $this->form['supplier_id'];
            $order_id = $this->form['order_id'];
            $invoice_no = $this->form['invoice_no'];
            $location_id = $this->form['location_id'];
            $date_receive = $this->form['date_receive'];

            $this->data['supplier'] = Helpers::get($this->db,TABLE_PREFIX,'supplier',$supplier_id);
            $this->data['location'] = Helpers::get($this->db,TABLE_PREFIX,'location',$location_id);

            if($order_id != 0) {
                $order = Helpers::getOrderDetails($this->db,TABLE_PREFIX,$order_id,$error_tmp);
                if($error_tmp != '') {
                    $this->addError('Invalid Order ID['.$order_id.'] :'.$error_tmp);
                } else {
                    if($order['order']['supplier_id'] !== $supplier_id) {
                        $order_supplier = Helpers::get($this->db,TABLE_PREFIX,'supplier',$order['order']['supplier_id']);
                       $this->addError('Order ID['.$order_id.'] references Supplier['.$order_supplier['name'].'] which is NOT '.$this->data['supplier']['name']);
                    } else {
                        $this->data['order'] = $order['order'];

                        $items = [];
                        foreach($order['items'] AS $order_item) {
                            $item = [];
                            $item['id'] = $order_item['item_id'];
                            $item['amount'] = $order_item['quantity'];
                            $item['price'] = $order_item['price'];
                            $item['subtotal'] = $order_item['subtotal'];
                            $item['tax'] = $order_item['tax'];
                            $item['total'] = $order_item['total'];

                            $items[] = $item;
                        }

                        $this->data['items'] = $items;
                        $this->data['item_count'] = count($items);
                    }
                }
            } else {
                //user can capture without linking to an order
                $this->data['order'] = 0;
                $this->data['items'] = [];
                $this->data['item_count'] = 0;
            }
            
            //check reception unique index, so can get meaningful error 
            $sql = 'SELECT COUNT(*) FROM '.TABLE_PREFIX.'receive '.
                   'WHERE supplier_id = "'.$this->db->escapeSql($supplier_id).'" AND date = "'.$this->db->escapeSql($date_receive).'" AND invoice_no = "'.$this->db->escapeSql($invoice_no).'" ';
            $count = $this->db->readSqlValue($sql);
            if($count != 0) {
                $error = 'Supplier['.$this->data['supplier']['name'].'] already has '.MODULE_STORE['labels']['invoice_no'].'['.$invoice_no.'] for date['.$this->data['supplier']['name'].'].'.
                         'Please change '.MODULE_STORE['labels']['invoice_no'].' so that unique for date';
                $this->addError($error);
            }       
            

        } 
        
        //process all reception items and calculate totals
        if($this->page_no == 2) {

            $store_id = $this->form['store_id'];
            $this->data['store'] = Helpers::get($this->db,TABLE_PREFIX,'store',$store_id);
           
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
                $item_id = 'item_'.$i;
                $amount_id = 'amount_'.$i;
                $price_id = 'price_'.$i;
                $subtotal_id = 'subtotal_'.$i;
                $tax_id = 'tax_'.$i;
                $total_id = 'total_'.$i;
                if(isset($_POST[$item_id])) {
                    $n++;
                    $item = [];
                    $item['store_id'] = $store_id;

                    $item['id'] = $_POST[$item_id];
                    $item['amount'] = abs($_POST[$amount_id]);
                    $item['price'] = abs($_POST[$price_id]);
                    $item['subtotal'] = abs($_POST[$subtotal_id]);
                    $item['tax'] = abs($_POST[$tax_id]);
                    $item['total'] = abs($_POST[$total_id]);
                    //save additional item details for messages and templates
                    $item['name'] = $items[$item['id']];

                    if(!is_numeric($item['id']) or !isset($items[$item['id']])) {
                        $this->addError('Invalid Item ID['.$item['id'].']');
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
        
        //save reception, email supplier
        if($this->page_no == 3) {
             
            $this->db->executeSql('START TRANSACTION',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not START transaction');

            //finally update reception with all details
            if(!$this->errors_found) {
                $table_order = $this->table_prefix.'order';
                $table_receive = $this->table_prefix.'receive';
                $table_receive_item = $this->table_prefix.'receive_item';
                $table_stock = $this->table_prefix.'stock';
                $table_stock_store = $this->table_prefix.'stock_store';
                $data = [];
                

                $receive = [];
                $receive['supplier_id'] = $this->form['supplier_id'];
                $receive['location_id'] = $this->form['location_id'];
                $receive['order_id'] = $this->form['order_id'];
                $receive['invoice_no'] = $this->form['invoice_no'];
                $receive['item_no'] = $this->data['item_count'];
                $receive['date'] = date('Y-m-d');
                $receive['subtotal'] = $this->data['item_subtotal'];
                $receive['tax'] = $this->data['item_tax'];
                $receive['total'] = $this->data['item_total'];
                $receive['note'] = $this->form['note'];
                $receive['status'] = 'NEW';

                $receive_id = $this->db->insertRecord($table_receive,$receive,$error_tmp);
                if($error_tmp !== '') {
                    $error = 'We could not save reception details.';
                    if($this->debug) $error .= $error_tmp;
                    $this->addError($error);
                }
            }

            if(!$this->errors_found) {
                $this->data['receive_id'] = $receive_id;

                foreach($this->data['items'] as $item) {
                    $store_id = Secure::clean('integer',$_POST['store_'.$item['id']]);

                    $receive_item = [];
                    $receive_item['receive_id'] = $receive_id;
                    $receive_item['item_id'] = $item['id'];
                    $receive_item['quantity'] = $item['amount'];
                    $receive_item['price'] = $item['price']; 
                    $receive_item['subtotal'] = $item['subtotal'];
                    $receive_item['tax'] = $item['tax'];
                    $receive_item['total'] = $item['total'];
                    $receive_item['store_id'] = $store_id;

                    $this->db->insertRecord($table_receive_item,$receive_item,$error_tmp);
                    if($error_tmp !== '') {
                        $error .= 'We could not save reception item['.$item['id'].']';
                        if($this->debug) $error .= $error_tmp;
                        $this->addError($error);
                    }

                    Helpers::updateStockReceived($this->db,TABLE_PREFIX,
                                                 $receive_item['item_id'],
                                                 $receive['supplier_id'],
                                                 $receive['invoice_no'],
                                                 $receive_item['quantity'],
                                                 $receive_item['store_id'],$error_tmp);
                    if($error_tmp !== '') {
                        $error .= 'We could not save stock item['.$item['id'].']';
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

            //update order if necessary
            if(!$this->errors_found) {
                //NB: partial order reception NOT catered for
                if($update_order and $this->data['order'] !== 0) {
                    $order_id = $this->data['order']['order_id'];
                    $sql = 'UPDATE '.TABLE_PREFIX.'order SET status = "RECEIVED" '.
                           'WHERE order_id = "'.$order_id.'" ';
                    $this->db->executeSql($sql,$error_tmp); 
                    if($error_tmp !== '') $this->addError('Could not update Order ID['.$order_id.'] status = RECEIVED');      
                }
            }    
            
            if(!$this->errors_found) {
                /*
                if($this->form['confirm_action'] === 'EMAIL') {
                    
                    //NB: if no email specified then uses default supplier email
                    $param = ['cc_admin'=>true,'email'=>$this->form['confirm_email']];
                    $subject = 'Confirmation';
                    $message = $this->form['note'];
                    Helpers::sendReceiveConfirmation($this->db,$this->table_prefix,$this->container,$order_id,$subject,$message,$param,$error_tmp);
                    if($error_tmp !== '') {
                        $message = 'We could not email supplier order details, but your order has been successfully saved.';
                        if($this->debug) $message .= $error_tmp;
                        $this->addMessage($message);
                    } else {
                        $this->addMessage('Successfully emailed order details to Supplier at: '.$this->form['confirm_email']);
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


