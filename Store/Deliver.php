<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Deliver extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Delivery','row_name_plural'=>'Deliveries','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addForeignKey(['table'=>TABLE_PREFIX.'deliver_item','col_id'=>'deliver_id','message'=>'Deliver items exist for this Deliver']);
        $this->addTableCol(['id'=>'deliver_id','type'=>'INTEGER','title'=>'deliver_ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'date','type'=>'DATE','title'=>'Date','new'=>date('Y-m-d')]);
        $this->addTableCol(['id'=>'client_id','type'=>'INTEGER','title'=>'Client id','join'=>'name FROM '.TABLE_PREFIX.'client WHERE client_id']);
        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'Location','join'=>'name FROM '.TABLE_PREFIX.'location WHERE location_id']);
        $this->addTableCol(['id'=>'subtotal','type'=>'DECIMAL','title'=>'Subtotal']);
        $this->addTableCol(['id'=>'tax','type'=>'DECIMAL','title'=>'Tax']);
        $this->addTableCol(['id'=>'total','type'=>'DECIMAL','title'=>'Total']);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSortOrder('T.deliver_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addAction(['type'=>'popup','text'=>'Deliver item','url'=>'deliver_item','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['deliver_id','date','client_id','subtotal','tax','total','note','status'],['rows'=>2]);

        $this->addSelect('client_id','SELECT client_id, name FROM '.TABLE_PREFIX.'client ORDER BY name');
        $this->addSelect('location_id','SELECT location_id, name FROM '.TABLE_PREFIX.'location ORDER BY name');

        $status = ['OK','CONFIRM'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
