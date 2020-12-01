<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ReceiveItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Receive item','col_label'=>'name','pop_up'=>true,'update_calling_page'=>true];
        parent::setup($param);

        //NB: should use wizard to add items but can do from here
        //$this->modifyAccess(['add'=>false]);

        $this->setupMaster(['table'=>TABLE_PREFIX.'receive','key'=>'receive_id','child_col'=>'receive_id',
                            'show_sql'=>'SELECT CONCAT("Reception ID[",receive_id,"] -",date) FROM '.TABLE_PREFIX.'receive WHERE receive_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'data_ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'Store id','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'Item id','join'=>'name FROM '.TABLE_PREFIX.'item WHERE item_id']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','min'=>0.01,'title'=>'Quantity']);
        $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price']);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal']);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax']);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total']);


        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['data_id','receive_id','store_id','item_id','quantity','price','subtotal','tax','total'],['rows'=>2]);

        $this->addSelect('store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
        $this->addSelect('item_id','SELECT item_id, name FROM '.TABLE_PREFIX.'item ORDER BY name');

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        $error_tmp = '';

        $this->db->executeSql('START TRANSACTION',$error_tmp);

        if($error_tmp !== '') {
            $error .= 'Could not initiate '.$context.' transaction';
        } else {
            Helpers::receiveItemUpdate($this->db,TABLE_PREFIX,$context,$id,$data,$error);
        }

        if($error !== '') {
            $this->db->executeSql('ROLLBACK',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not ROLLBACK transaction');
        } else {
            $this->db->executeSql('COMMIT',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not COMMIT transaction');
        }    
    }

    protected function afterUpdate($id,$context,$data) {
        $error = '';
        Helpers::updateReceive($this->db,TABLE_PREFIX,$this->master['key_val'],$error);
    }
    
    protected function beforeDelete($id,&$error) 
    {
        $this->db->executeSql('START TRANSACTION',$error_tmp);

        if($error_tmp !== '') {
            $error .= 'Could not initiate DELETE transaction';
        } else {
            $data = [];
            Helpers::receiveItemUpdate($this->db,TABLE_PREFIX,'DELETE',$id,$data,$error);
        }

        if($error !== '') {
            $this->db->executeSql('ROLLBACK',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not ROLLBACK transaction');
        } else {
            $this->db->executeSql('COMMIT',$error_tmp);
            if($error_tmp !== '') $this->addError('Could not COMMIT transaction');
        } 
    }

    protected function afterDelete($id) {
        $error = '';
        Helpers::updateReceive($this->db,TABLE_PREFIX,$this->master['key_val'],$error);
    }
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
