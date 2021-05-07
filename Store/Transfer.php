<?php
namespace App\Store;

use Seriti\Tools\Table;
use Seriti\Tools\Date;
use Seriti\Tools\Form;
use Seriti\Tools\Secure;
use Seriti\Tools\Audit;
use Seriti\Tools\Validate;

use App\Store\Helpers;

class Transfer extends Table
{
    protected $status = ['NEW','CONFIRMED'];

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Transfer','col_label'=>'name','pop_up'=>false,'add_href'=>'transfer_wizard'];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'transfer_item','col_id'=>'transfer_id','message'=>'Transfer items exist for this Transfer']);


        $this->addTableCol(['id'=>'transfer_id','type'=>'INTEGER','title'=>'transfer_ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'date','type'=>'DATE','title'=>'Date','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'from_store_id','type'=>'INTEGER','title'=>'From store id','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'to_store_id','type'=>'INTEGER','title'=>'To store id','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'item_no','type'=>'INTEGER','title'=>'No items','edit'=>false]);
        $this->addTableCol(['id'=>'total_kg','type'=>'DECIMAL','title'=>'Total kg','edit'=>false]);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status','edit'=>false]);


        $this->addSortOrder('T.transfer_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'check_box','text'=>'']);
        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Items','url'=>'transfer_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['transfer_id','date','from_store_id','to_store_id','total_kg','item_no','note','status'],['rows'=>1]);

        $this->addSelect('from_store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
        $this->addSelect('to_store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
        
        $this->addSelect('status',['list'=>$this->status,'list_assoc'=>false]);

    }

    protected function modifyRowValue($col_id,$data,&$value) {
        if($col_id === 'status') {
            if($value == 'NEW') $value = 'NEW, To store stock not confirmed yet';
            if($value == 'CONFIRMED') $value = 'CONFIRMED, To store stock updated';

        }    
    } 

    protected function viewTableActions() {
        $html = '';
        $list = array();
            
        $status_set = 'NEW';
        $date_set = date('Y-m-d');
        
        if(!$this->access['read_only']) {
            $list['SELECT'] = 'Action for selected '.$this->row_name_plural;
            $list['CONFIRM'] = 'CONFIRM '.$this->row_name.' stock arrived at destination srore.';
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
                     '}'.
                     '</script>';
                    
            $html .= '&nbsp;<input type="submit" name="action_submit" value="Apply action to selected '.
                     $this->row_name_plural.'" class="btn btn-primary">';
        }  
        
        return $html; 
    }
  
    //update multiple records based on selected action
    protected function updateTable() {
        $error = '';
        $audit_str = '';
        $audit_count = 0;
        $html = '';

        //use for emailing multiple delivery docs to single email
        $action_delivery = array();
            
        $action = Secure::clean('basic',$_POST['table_action']);
        if($action === 'SELECT') {
            $this->addError('You have not selected any action to perform on '.$this->row_name_plural.'!');
        } else {
            if($action === 'CONFIRM') {
                $audit_str = 'CONFIRMED ';
            }
            
            if(!$this->errors_found) {     
                foreach($_POST as $key => $value) {
                    if(substr($key,0,8) === 'checked_') {
                        $transfer_id = substr($key,8);
                        $audit_str .= 'Transfer ID['.$transfer_id.'] ';

                        $transfer = Helpers::get($this->db,TABLE_PREFIX,'transfer',$transfer_id);

                        if($action === 'CONFIRM') {
                            if($transfer['status'] !== 'NEW') {
                                $this->addError('Could not CONFIRM Transfer ID['.$transfer_id.'] as status is '.$transfer['status']); 
                            } else {
                                Helpers::updateTransfer($this->db,TABLE_PREFIX,'CONFIRM',$transfer_id,$error);
                                if($error !== '') {
                                    $this->addError('Could not CONFIRM Transfer ID['.$transfer_id.'] :'.$error); 
                                } else {
                                    $message_str = 'Transfer ID['.$transfer_id.'] CONFIRMED';
                                    $audit_str .= ' success!';
                                    $audit_count++;
                                    $this->addMessage($message_str);   
                                }    
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

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
