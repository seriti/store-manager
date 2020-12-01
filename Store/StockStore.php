<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class StockStore extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Store Stock','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'Data ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'Store ID','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'stock_id','type'=>'INTEGER','title'=>'Stock ID']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','title'=>'Quantity']);
        

        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['data_id','store_id','stock_id','quantity'],['rows'=>1]);

        $this->addSelect('store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
