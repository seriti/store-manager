<?php
namespace App\Store;

use Seriti\Tools\Table;
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Secure;
use Seriti\Tools\Audit;
use Seriti\Tools\Validate;

class Order extends Table
{
    protected $status = ['NEW','CONFIRMED','RECEIVED'];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Order','col_label'=>'order_id','pop_up'=>false,'add_href'=>'order_wizard'];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'order_item','col_id'=>'order_id','message'=>'Order items exist for this Order']);
        
        $this->addTableCol(['id'=>'order_id','type'=>'INTEGER','title'=>'order ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'supplier_id','type'=>'INTEGER','title'=>'Supplier','join'=>'name FROM '.TABLE_PREFIX.'supplier WHERE supplier_id']);
        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'Location','join'=>'name FROM '.TABLE_PREFIX.'location WHERE location_id']);
        $this->addTableCol(['id'=>'date_create','type'=>'DATE','title'=>'Date created','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'date_receive','type'=>'DATE','title'=>'Est. reception date','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'item_no','type'=>'INTEGER','title'=>'No items','edit'=>false]);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal','edit'=>false]);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax','edit'=>false]);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total','edit'=>false]);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSortOrder('T.order_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'check_box','text'=>'']); 
        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Items','url'=>'order_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['order_id','location_id','supplier_id','date','subtotal','tax','total','note','status'],['rows'=>2]);

        $this->addSearchAggregate(['sql'=>'SUM(T.total)','title'=>'Total value']);

        $this->addSelect('supplier_id','SELECT supplier_id, name FROM '.TABLE_PREFIX.'supplier ORDER BY name');
        $this->addSelect('location_id','SELECT location_id, name FROM '.TABLE_PREFIX.'location ORDER BY name');

        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'ORD','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'order_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

        $this->addSelect('status',['list'=>$this->status,'list_assoc'=>false]);

    }


    protected function viewTableActions() {
        $html = '';
        $list = array();
            
        $status_set = 'NEW';
        $date_set = date('Y-m-d');
        
        if(!$this->access['read_only']) {
            $list['SELECT'] = 'Action for selected '.$this->row_name_plural;
            $list['STATUS_CHANGE'] = 'Change order Status.';
            $list['EMAIL_ORDER'] = 'Email order';
        }  
        
        if(count($list) != 0){
            $html .= '<span style="padding:8px;"><input type="checkbox" id="checkbox_all"></span> ';
            $param['class'] = 'form-control input-medium input-inline';
            $param['onchange'] = 'javascript:change_table_action()';
            $action_id = '';
            $status_change = 'NONE';
            $email_address = '';
            
            $html .= Form::arrayList($list,'table_action',$action_id,true,$param);
            
            //javascript to show collection list depending on selecetion      
            $html .= '<script type="text/javascript">'.
                     '$("#checkbox_all").click(function () {$(".checkbox_action").prop(\'checked\', $(this).prop(\'checked\'));});'.
                     'function change_table_action() {'.
                     'var table_action = document.getElementById(\'table_action\');'.
                     'var action = table_action.options[table_action.selectedIndex].value; '.
                     'var status_select = document.getElementById(\'status_select\');'.
                     'var email_order = document.getElementById(\'email_order\');'.
                     'status_select.style.display = \'none\'; '.
                     'email_order.style.display = \'none\'; '.
                     'if(action==\'STATUS_CHANGE\') status_select.style.display = \'inline\';'.
                     'if(action==\'EMAIL_ORDER\') email_order.style.display = \'inline\';'.
                     '}'.
                     '</script>';
            
            $param = array();
            $param['class'] = 'form-control input-small input-inline';
            //$param['class']='form-control col-sm-3';
            $html .= '<span id="status_select" style="display:none"> status&raquo;'.
                     Form::arrayList($this->status,'status_change',$status_change,false,$param).
                     '</span>'; 
            
            $param['class'] = 'form-control input-medium input-inline';       
            $html .= '<span id="email_order" style="display:none"> Email address&raquo;'.
                     Form::textInput('email_address',$email_address,$param).
                     '</span>';
                    
            $html .= '&nbsp;<input type="submit" name="action_submit" value="Apply action to selected '.
                     $this->row_name_plural.'" class="btn btn-primary">';
        }  
        
        return $html; 
    }
  
    //update multiple records based on selected action
    protected function updateTable() {
        $error_str = '';
        $error_tmp = '';
        $message_str = '';
        $audit_str = '';
        $audit_count = 0;
        $html = '';
            
        $action = Secure::clean('basic',$_POST['table_action']);
        if($action === 'SELECT') {
            $this->addError('You have not selected any action to perform on '.$this->row_name_plural.'!');
        } else {
            if($action === 'STATUS_CHANGE') {
                $status_change = Secure::clean('alpha',$_POST['status_change']);
                $audit_str = 'Status change['.$status_change.'] ';
                if($status_change === 'NONE') $this->addError('You have not selected a valid status['.$status_change.']!');
            }
            
            if($action === 'EMAIL_ORDER') {
                $email_address = Secure::clean('email',$_POST['email_address']);
                Validate::email('email address',$email_address,$error_str);
                $audit_str = 'Email order to['.$email_address.'] ';
                if($error_str != '') $this->addError('INVAID email address['.$email_address.']!');
            }
            
            if(!$this->errors_found) {     
                foreach($_POST as $key => $value) {
                    if(substr($key,0,8) === 'checked_') {
                        $order_id = substr($key,8);
                        $audit_str .= 'order ID['.$order_id.'] ';
                                            
                        if($action === 'STATUS_CHANGE') {
                            $sql = 'UPDATE '.$this->table.' SET status = "'.$this->db->escapeSql($status_change).'" '.
                                   'WHERE order_id = "'.$this->db->escapeSql($order_id).'" ';
                            $this->db->executeSql($sql,$error_tmp);
                            if($error_tmp === '') {
                                $message_str = 'Status set['.$status_change.'] for order ID['.$order_id.'] ';
                                $audit_str .= ' success!';
                                $audit_count++;
                                
                                $this->addMessage($message_str);                
                            } else {
                                $this->addError('Could not update status for order['.$order_id.']: '.$error_tmp);                
                            }  
                        }
                        
                        if($action === 'EMAIL_ORDER') {
                            $param = ['email'=>$email_address];
                            $subject = '';
                            $message = '';
                            Helpers::sendOrderConfirmation($this->db,TABLE_PREFIX,$this->container,$order_id,$subject,$message,$param,$error_tmp);
                            if($error_tmp === '') {
                                $audit_str .= ' success!';
                                $audit_count++;
                                $this->addMessage('Order ID['.$order_id.'] sent to email['.$email_address.']');      
                            } else {
                                $error = 'Cound not send order['.$order_id.'] to email address['.$email_address.']!';
                                if($this->debug) $error .= $error_tmp;
                                $this->addError($error);
                            }   
                        }  
                    }   
                }  
              
            }  
        }  
        
        //audit any updates except for deletes as these are already audited 
        if($audit_count != 0 and $action != 'DELETE') {
            $audit_action = $action.'_'.strtoupper($this->table);
            Audit::action($this->db,$this->user_id,$audit_action,$audit_str);
        }  
            
        $this->mode = 'list';
        $html .= $this->viewTable();
            
        return $html;
    }

}
