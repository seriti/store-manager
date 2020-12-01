<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

use App\Store\Helpers;

class OrderItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Order item','col_label'=>'item_id','pop_up'=>true,'update_calling_page'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'order','key'=>'order_id','child_col'=>'order_id',
                            'show_sql'=>'SELECT CONCAT("Order ID[",order_id,"] - ",date_create) FROM '.TABLE_PREFIX.'order WHERE order_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'data ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'Item','join'=>'name FROM '.TABLE_PREFIX.'item WHERE item_id']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','min'=>0.01,'title'=>'Quantity']);
        $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price']);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal']);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax']);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total']);

        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['data_id','order_id','item_id','quantity','price','subtotal','tax','total'],['rows'=>2]);

        $this->addSelect('item_id','SELECT item_id, name FROM '.TABLE_PREFIX.'item ORDER BY name');

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        Helpers::verifyOrderItemUpdate($this->db,TABLE_PREFIX,'UPDATE',$id,$data,$error);
    }

    protected function afterUpdate($id,$context,$data) 
    {
        $error = '';
        Helpers::updateOrder($this->db,TABLE_PREFIX,$this->master['key_val'],$error);
    }

    protected function beforeDelete($id,&$error) 
    {
        $data = [];
        Helpers::verifyOrderItemUpdate($this->db,TABLE_PREFIX,'DELETE',$id,$data,$error);
    }

    protected function afterDelete($id) 
    {
        $error = '';
        Helpers::updateOrder($this->db,TABLE_PREFIX,$this->master['key_val'],$error);
    }
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
