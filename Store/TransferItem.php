<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

use App\Store\Helpers;

class TransferItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Transfer item','col_label'=>'stock_id','pop_up'=>true];
        parent::setup($param);

        //NB: should use wizard to add items but can do from here
        //$this->modifyAccess(['add'=>false,'edit']);

        $this->setupMaster(['table'=>TABLE_PREFIX.'transfer','key'=>'transfer_id','child_col'=>'transfer_id',
                            'show_sql'=>'SELECT CONCAT("Transfer ID[",transfer_id,"] ",date) FROM '.TABLE_PREFIX.'transfer WHERE transfer_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'Data ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'stock_id','type'=>'INTEGER','title'=>'Stock']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','title'=>'Quantity']);
        $this->addTableCol(['id'=>'total_kg','type'=>'DECIMAL','title'=>'Total Kg']);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        //$this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status','edit'=>false]);

        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['data_id','transfer_id','stock_id','quantity','note'],['rows'=>1]);

        $this->addSelect('stock_id','SELECT S.stock_id, I.name FROM '.TABLE_PREFIX.'stock AS S JOIN '.TABLE_PREFIX.'item AS I USING(item_id) ORDER BY I.name');
    }

    protected function beforeProcess($id) 
    {
        $transfer = Helpers::get($this->db,TABLE_PREFIX,'transfer',$this->master['key_val']);

        $sql = 'SELECT SS.stock_id,I.name FROM '.TABLE_PREFIX.'stock_store AS SS '.
               'JOIN '.TABLE_PREFIX.'stock AS S ON(SS.stock_id = S.stock_id) '.
               'JOIN '.TABLE_PREFIX.'item AS I ON(S.item_id = I.item_id) '.
               'WHERE SS.store_id = "'.$this->db->escapeSql($transfer['from_store_id']).'" '.
               'ORDER BY I.name';

        $this->addSelect('stock_id',$sql);
       
    }


    protected function modifyRowValue($col_id,$data,&$value)
    {
        if($col_id === 'stock_id') {
            $stock_id = $value;
            
            $stock = Helpers::getStockItem($this->db,TABLE_PREFIX,$stock_id);
            $value = $stock['summary'];
        }
    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        $error_tmp = '';
        $transfer_id = $this->master['key_val'];

        $this->db->executeSql('START TRANSACTION',$error_tmp);

        if($error_tmp !== '') {
            $error .= 'Could not initiate '.$context.' transaction';
        } else {
            Helpers::transferItemUpdate($this->db,TABLE_PREFIX,$context,$transfer_id,$id,$data,$error);
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
        Helpers::updateTransfer($this->db,TABLE_PREFIX,'TOTALS',$this->master['key_val'],$error);
    }
    
    protected function beforeDelete($id,&$error) 
    {
        $error_tmp = '';
        $transfer_id = $this->master['key_val'];

        $this->db->executeSql('START TRANSACTION',$error_tmp);

        if($error_tmp !== '') {
            $error .= 'Could not initiate '.$context.' transaction';
        } else {
            $data = [];
            Helpers::transferItemUpdate($this->db,TABLE_PREFIX,'DELETE',$transfer_id,$id,$data,$error);
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
        Helpers::updateTransfer($this->db,TABLE_PREFIX,'TOTALS',$this->master['key_val'],$error);
    }
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
