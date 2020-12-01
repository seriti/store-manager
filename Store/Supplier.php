<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Supplier extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Supplier','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addTableCol(['id'=>'supplier_id','type'=>'INTEGER','title'=>'supplier_ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'contact','type'=>'STRING','title'=>'Contact']);
        $this->addTableCol(['id'=>'email','type'=>'STRING','title'=>'Email','required'=>true]);
        $this->addTableCol(['id'=>'address','type'=>'TEXT','title'=>'Address','required'=>false]);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);


        $this->addSortOrder('T.supplier_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['supplier_id','name','contact','email','address','note','status'],['rows'=>1]);


        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'SUP','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'supplier_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);

    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
