<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Transfer extends Table
{
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
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.transfer_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Items','url'=>'transfer_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['transfer_id','date','from_store_id','to_store_id','total_kg','item_no','note','status'],['rows'=>1]);

        $this->addSelect('from_store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
        $this->addSelect('to_store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');

        $status = ['OK','VALIDATE','CONFIRM'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
