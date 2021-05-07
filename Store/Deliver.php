<?php
namespace App\Store;

use Seriti\Tools\Table;
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Secure;
use Seriti\Tools\Audit;
use Seriti\Tools\Validate;

use App\Store\Helpers;

class Deliver extends Table
{
    protected $status = ['NEW','DELIVERED','INVOICED'];
    protected $yes_no = ['YES','NO'];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Delivery','row_name_plural'=>'Deliveries','col_label'=>'name','pop_up'=>false,'add_href'=>'deliver_wizard'];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'deliver_item','col_id'=>'deliver_id','message'=>'Deliver items exist for this Deliver']);

        $this->addTableCol(['id'=>'deliver_id','type'=>'INTEGER','title'=>'Deliver ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'date','type'=>'DATE','title'=>'Date','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'client_id','type'=>'INTEGER','title'=>'Client','join'=>'name FROM '.TABLE_PREFIX.'client WHERE client_id']);
        if(CLIENT_LOCATION) {
            $this->addTableCol(['id'=>'client_location_id','type'=>'INTEGER','title'=>'Client location','join'=>'name FROM '.TABLE_PREFIX.'client_location WHERE location_id']);    
        }
        
        $this->addTableCol(['id'=>'client_order_no','type'=>'STRING','title'=>'Client order no.','new'=>'NA']);
        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'From Store','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'item_no','type'=>'INTEGER','title'=>'No items','edit'=>false]);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal']);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax']);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total']);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        $this->addTableCol(['id'=>'transport_paid','type'=>'BOOLEAN','title'=>'Transport paid','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSortOrder('T.deliver_id DESC','Most recent first','DEFAULT');

        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'DEL','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'deliver_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

        $this->addAction(['type'=>'check_box','text'=>'']); 
        //$this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Deliver&nbsp;items','url'=>'deliver_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['deliver_id','date','client_id','subtotal','tax','total','note','status'],['rows'=>2]);

        $this->addSelect('client_id','SELECT client_id, name FROM '.TABLE_PREFIX.'client ORDER BY name');
        $this->addSelect('store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');

        $this->addSelect('status',['list'=>$this->status,'list_assoc'=>false]);

    }

    protected function modifyRowValue($col_id,$data,&$value) {
        if($col_id === 'client_location_id') {
            if($value == '') $value = 'NO location';
        }    
    } 


    protected function viewTableActions() {
        $html = '';
        $list = array();
            
        $status_set = 'NEW';
        $date_set = date('Y-m-d');
        
        if(!$this->access['read_only']) {
            $list['SELECT'] = 'Action for selected '.$this->row_name_plural;
            $list['STATUS_CHANGE'] = 'Change '.$this->row_name.' Status.';
            $list['CREATE_PDF'] = 'Create delivery note PDF';
            $list['EMAIL_DELIVER'] = 'Email '.$this->row_name.' details';
            $list['EMAIL_ALL_DOCS'] = 'Email ALL '.$this->row_name.' documents in single email';
            $list['TRANSPORT_PAID'] = 'Update Transport paid.';
        }  
        
        if(count($list) != 0){
            $html .= '<span style="padding:8px;"><input type="checkbox" id="checkbox_all"></span> ';
            $param['class'] = 'form-control input-medium input-inline';
            $param['onchange'] = 'javascript:change_table_action()';
            $action_id = '';
            $status_change = 'NONE';
            $transport_paid = 'YES';
            $email_address = '';
            
            $html .= Form::arrayList($list,'table_action',$action_id,true,$param);
            
            //javascript to show collection list depending on selecetion      
            $html .= '<script type="text/javascript">'.
                     '$("#checkbox_all").click(function () {$(".checkbox_action").prop(\'checked\', $(this).prop(\'checked\'));});'.
                     'function change_table_action() {'.
                     'var table_action = document.getElementById(\'table_action\');'.
                     'var action = table_action.options[table_action.selectedIndex].value; '.
                     'var status_select = document.getElementById(\'status_select\');'.
                     'var email_deliver = document.getElementById(\'email_deliver\');'.
                     'var transport_select = document.getElementById(\'transport_select\');'.
                     'status_select.style.display = \'none\'; '.
                     'email_deliver.style.display = \'none\'; '.
                     'transport_select.style.display = \'none\'; '.
                     'if(action==\'STATUS_CHANGE\') status_select.style.display = \'inline\';'.
                     'if(action==\'EMAIL_DELIVER\') email_deliver.style.display = \'inline\';'.
                     'if(action==\'EMAIL_ALL_DOCS\') email_deliver.style.display = \'inline\';'.
                     'if(action==\'TRANSPORT_PAID\') transport_select.style.display = \'inline\';'.
                     '}'.
                     '</script>';
            
            $param = array();
            $param['class'] = 'form-control input-small input-inline';
            //$param['class']='form-control col-sm-3';
            $html .= '<span id="status_select" style="display:none"> status&raquo;'.
                     Form::arrayList($this->status,'status_change',$status_change,false,$param).
                     '</span>'; 
            
            $param['class'] = 'form-control input-medium input-inline';       
            $html .= '<span id="email_deliver" style="display:none"> Email address&raquo;'.
                     Form::textInput('email_address',$email_address,$param).
                     '</span>';

                     //$param['class']='form-control col-sm-3';
            $html .= '<span id="transport_select" style="display:none"> paid&raquo;'.
                     Form::arrayList($this->yes_no,'transport_paid',$transport_paid,false,$param).
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

        //use for emailing multiple delivery docs to single email
        $action_delivery = array();
            
        $action = Secure::clean('basic',$_POST['table_action']);
        if($action === 'SELECT') {
            $this->addError('You have not selected any action to perform on '.$this->row_name_plural.'!');
        } else {
            if($action === 'STATUS_CHANGE') {
                $status_change = Secure::clean('alpha',$_POST['status_change']);
                $audit_str = 'Status change['.$status_change.'] ';
                if($status_change === 'NONE') $this->addError('You have not selected a valid status['.$status_change.']!');
            }
            
            if($action === 'EMAIL_DELIVER') {
                $email_address = Secure::clean('email',$_POST['email_address']);
                Validate::email('email address',$email_address,$error_str);
                $audit_str = 'Email delivery to['.$email_address.'] ';
                if($error_str != '') $this->addError('INVAID email address['.$email_address.']!');
            }

            if($action === 'EMAIL_ALL_DOCS') {
                $email_address = Secure::clean('email',$_POST['email_address']);
                Validate::email('email address',$email_address,$error_str);
                $audit_str = 'Email all delivery docs to['.$email_address.'] ';
                if($error_str != '') $this->addError('INVAID email address['.$email_address.']!');
            }

            if($action === 'TRANSPORT_PAID') {
                $transport_paid = Secure::clean('alpha',$_POST['transport_paid']);
                $audit_str = 'Transport paid['.$transport_paid.'] ';
            }
            
            if(!$this->errors_found) {     
                foreach($_POST as $key => $value) {
                    if(substr($key,0,8) === 'checked_') {
                        $deliver_id = substr($key,8);
                        $audit_str .= 'Deliver ID['.$deliver_id.'] ';
                                            
                        if($action === 'STATUS_CHANGE') {
                            $sql = 'UPDATE '.$this->table.' SET status = "'.$this->db->escapeSql($status_change).'" '.
                                   'WHERE deliver_id = "'.$this->db->escapeSql($deliver_id).'" ';
                            $this->db->executeSql($sql,$error_tmp);
                            if($error_tmp === '') {
                                $message_str = 'Status set['.$status_change.'] for Deliver ID['.$deliver_id.'] ';
                                $audit_str .= ' success!';
                                $audit_count++;
                                
                                $this->addMessage($message_str);                
                            } else {
                                $this->addError('Could not update status for Deliver ID['.$deliver_id.']: '.$error_tmp);                
                            }  
                        }

                        if($action === 'TRANSPORT_PAID') {
                            if($transport_paid === 'YES') $bool = 1; else $bool = 0;
                            $sql = 'UPDATE '.$this->table.' SET transport_paid = "'.$bool.'" '.
                                   'WHERE deliver_id = "'.$this->db->escapeSql($deliver_id).'" ';
                            $this->db->executeSql($sql,$error_tmp);
                            if($error_tmp === '') {
                                $message_str = 'Transport paid['.$transport_paid.'] for Deliver ID['.$deliver_id.'] ';
                                $audit_str .= ' success!';
                                $audit_count++;
                                
                                $this->addMessage($message_str);                
                            } else {
                                $this->addError('Could not update transport paid for Deliver ID['.$deliver_id.']: '.$error_tmp);                
                            }  
                        }
                        
                        if($action === 'EMAIL_DELIVER') {
                            $param = ['email'=>$email_address];
                            $subject = '';
                            $message = '';
                            Helpers::sendDeliverConfirmation($this->db,TABLE_PREFIX,$this->container,$deliver_id,$subject,$message,$param,$error_tmp);
                            if($error_tmp === '') {
                                $audit_str .= ' success!';
                                $audit_count++;
                                $this->addMessage('Delivery ID['.$deliver_id.'] sent to email['.$email_address.']');      
                            } else {
                                $error = 'Cound not send Delivery ID['.$deliver_id.'] to email address['.$email_address.']!';
                                if($this->debug) $error .= $error_tmp;
                                $this->addError($error);
                            }   
                        }

                        if($action === 'EMAIL_ALL_DOCS') {
                            $action_delivery[] = $deliver_id;
                        }


                        if($action === 'CREATE_PDF') {
                            Helpers::createDeliverPdf($this->db,$this->container,$deliver_id,$doc_name,$error_tmp);
                            if($error_tmp === '') {
                                $audit_str .= ' success!';
                                $audit_count++;
                                $this->addMessage('Delivery ID['.$deliver_id.'] PDF created');      
                            } else {
                                $this->addError('Cound not create delivery note for delivery ID['.$deliver_id.']:'.$error_tmp);
                            }   
                        }    
                    }   
                }  
              
            } 

            if(!$this->errors_found and $action === 'EMAIL_ALL_DOCS') {
                $subject = '';
                $message = '';
                $send_param = [];
                Helpers::sendDeliverDocs($this->db,TABLE_PREFIX,$this->container,$action_delivery,$email_address,$subject,$message,$send_param,$error_tmp);
                
                if($error_tmp === '') {
                    $this->addMessage('SUCCESS sending delivery documents to['.$email_address.']'); 
                } else {
                    $this->addError('FAILURE sending delivery documents to['.$email_address.']:'.$error_tmp); 
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

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
