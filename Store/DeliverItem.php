<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

use App\Store\Helpers;

class DeliverItem extends Table
{
    protected $deliver;

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Deliver item','col_label'=>'stock_id','pop_up'=>true];
        parent::setup($param);

        //NB: should use wizard to add items but can do so here
        //this->modifyAccess(['add'=>false]);

        $this->setupMaster(['table'=>TABLE_PREFIX.'deliver','key'=>'deliver_id','child_col'=>'deliver_id',
                            'show_sql'=>'SELECT CONCAT("Delivery ID: ",deliver_id) FROM '.TABLE_PREFIX.'deliver WHERE deliver_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'Data ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'stock_id','type'=>'INTEGER','title'=>'Stock item','join'=>'I.name FROM '.TABLE_PREFIX.'stock AS S JOIN '.TABLE_PREFIX.'item AS I USING(item_id) WHERE S.stock_id']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','min'=>0.01,'title'=>'Quantity']);
        $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price']);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal']);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax']);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total']);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        //$this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['data_id','deliver_id','store_id','stock_id','quantity','price','subtotal','tax','total','note','status'],['rows'=>2]);

        $this->addSelect('stock_id','SELECT S.stock_id, I.name FROM '.TABLE_PREFIX.'stock AS S JOIN '.TABLE_PREFIX.'item AS I USING(item_id) ORDER BY I.name');

    }

    protected function beforeProcess($id) 
    {
        $deliver_id = $this->master['key_val'];
        $this->deliver = Helpers::get($this->db,TABLE_PREFIX,'deliver',$deliver_id);

        if($this->mode === 'add' or $this->mode === 'update') {
            $sql = 'SELECT SS.stock_id,CONCAT(C.name,": ",I.name,"(",SU.name," - ",S.invoice_no,") ",SS.quantity,I.units," available") '.
                   'FROM '.TABLE_PREFIX.'stock_store AS SS JOIN '.TABLE_PREFIX.'stock AS S ON(SS.stock_id = S.stock_id) '.
                         'JOIN '.TABLE_PREFIX.'supplier AS SU ON(S.supplier_id = SU.supplier_id) '.
                         'JOIN '.TABLE_PREFIX.'item AS I ON(S.item_id = I.item_id) '.
                         'JOIN '.TABLE_PREFIX.'item_category AS C ON(I.category_id = C.category_id) '.
                   'WHERE SS.store_id = "'.$this->deliver['store_id'].'" AND SS.quantity > 0 AND I.status <> "HIDE" '.
                   'ORDER BY C.sort, I.name';
            $this->addSelect('stock_id',$sql);    
        }
        
    }
    
    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        $error_tmp = '';
        $deliver_id = $this->master['key_val'];

        $this->db->executeSql('START TRANSACTION',$error_tmp);

        if($error_tmp !== '') {
            $error .= 'Could not initiate '.$context.' transaction';
        } else {
            Helpers::deliverItemUpdate($this->db,TABLE_PREFIX,$context,$deliver_id,$id,$data,$error);
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
        Helpers::updateDeliver($this->db,TABLE_PREFIX,$this->master['key_val'],$error);
    }
    
    protected function beforeDelete($id,&$error) 
    {
        $error_tmp = '';
        $deliver_id = $this->master['key_val'];

        $this->db->executeSql('START TRANSACTION',$error_tmp);

        if($error_tmp !== '') {
            $error .= 'Could not initiate DELETE transaction';
        } else {
            $data = [];
            Helpers::deliverItemUpdate($this->db,TABLE_PREFIX,'DELETE',$deliver_id,$id,$data,$error);
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
        Helpers::updateDeliver($this->db,TABLE_PREFIX,$this->master['key_val'],$error);
    }

}
