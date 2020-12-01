<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Receive extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Reception','col_label'=>'name','pop_up'=>false,'add_href'=>'receive_wizard'];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'receive_item','col_id'=>'receive_id','message'=>'Receive items exist for this Receive']);
        $this->addTableCol(['id'=>'receive_id','type'=>'INTEGER','title'=>'Receive ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'supplier_id','type'=>'INTEGER','title'=>'Supplier','join'=>'name FROM '.TABLE_PREFIX.'supplier WHERE supplier_id']);
        $this->addTableCol(['id'=>'order_id','type'=>'INTEGER','title'=>'Order ID']);
        $this->addTableCol(['id'=>'date','type'=>'DATE','title'=>'Date','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'invoice_no','type'=>'STRING','title'=>'Invoice no']);
        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'Location','join'=>'name FROM '.TABLE_PREFIX.'location WHERE location_id']);
        $this->addTableCol(['id'=>'item_no','type'=>'INTEGER','title'=>'No items','edit'=>false]);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal','edit'=>false]);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax','edit'=>false]);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total','edit'=>false]);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.receive_id DESC','Most recent first','DEFAULT');

        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'REC','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'receive_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);


        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Items','url'=>'receive_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['receive_id','supplier_id','order_id','date','invoice_no','location_id','subtotal','tax','total','note','status'],['rows'=>3]);

        $this->addSelect('supplier_id','SELECT supplier_id, name FROM '.TABLE_PREFIX.'supplier ORDER BY name');
        $this->addSelect('location_id','SELECT location_id, name FROM '.TABLE_PREFIX.'location ORDER BY name');

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
