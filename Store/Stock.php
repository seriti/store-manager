<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Stock extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Stock','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addTableCol(['id'=>'stock_id','type'=>'INTEGER','title'=>'stock_ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'Store id','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'item_id','type'=>'INTEGER','title'=>'Item id','join'=>'name FROM '.TABLE_PREFIX.'item WHERE item_id']);
        $this->addTableCol(['id'=>'supplier_id','type'=>'INTEGER','title'=>'Supplier id','join'=>'name FROM '.TABLE_PREFIX.'supplier WHERE supplier_id']);
        $this->addTableCol(['id'=>'invoice_no','type'=>'STRING','title'=>'Invoice no']);
        $this->addTableCol(['id'=>'quantity_in','type'=>'DECIMAL','title'=>'Quantity received']);
        $this->addTableCol(['id'=>'quantity_out','type'=>'DECIMAL','title'=>'Quantity delivered']);


        $this->addSortOrder('T.stock_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['stock_id','store_id','item_id','supplier_id','invoice_no','quantity'],['rows'=>1]);

        $this->addSelect('store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
        $this->addSelect('item_id','SELECT item_id, name FROM '.TABLE_PREFIX.'item ORDER BY name');
        $this->addSelect('supplier_id','SELECT supplier_id, name FROM '.TABLE_PREFIX.'supplier ORDER BY name');

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
