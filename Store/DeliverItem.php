<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

use App\Store\Helpers;

class DeliverItem extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Deliver item','col_label'=>'stock_id','pop_up'=>true];
        parent::setup($param);

         //NB: must use wizard to add items
        $this->modifyAccess(['add'=>false]);

        $this->setupMaster(['table'=>TABLE_PREFIX.'deliver','key'=>'deliver_id','child_col'=>'deliver_id',
                            'show_sql'=>'SELECT CONCAT("Delivery ID: ",deliver_id) FROM '.TABLE_PREFIX.'deliver WHERE deliver_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'Data ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'Store','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'stock_id','type'=>'INTEGER','title'=>'Stock item','join'=>'I.name FROM '.TABLE_PREFIX.'stock AS S JOIN '.TABLE_PREFIX.'item AS I USING(item_id) WHERE S.stock_id']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','min'=>0.01,'title'=>'Quantity']);
        $this->addTableCol(['id'=>'price','type'=>'DECIMAL','title'=>'Price']);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal']);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax']);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total']);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['data_id','deliver_id','store_id','stock_id','quantity','price','subtotal','tax','total','note','status'],['rows'=>2]);

        $this->addSelect('store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
        //$this->addSelect('stock_id','SELECT S.stock_id, I.name FROM '.TABLE_PREFIX.'stock AS S JOIN '.TABLE_PREFIX.'item AS I USING(item_id) ORDER BY I.name');

    }

    
    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
