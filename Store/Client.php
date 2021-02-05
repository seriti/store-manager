<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Client extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Client','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $this->addTableCol(['id'=>'client_id','type'=>'INTEGER','title'=>'Client ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Client name']);
        $this->addTableCol(['id'=>'contact','type'=>'STRING','title'=>'Contact name','required'=>false]);
        $this->addTableCol(['id'=>'account_code','type'=>'STRING','title'=>'Account code']);
        $this->addTableCol(['id'=>'email','type'=>'EMAIL','title'=>'Email']);
        $this->addTableCol(['id'=>'cell','type'=>'STRING','title'=>'Cell/mobile No.','required'=>false]);
        $this->addTableCol(['id'=>'tel','type'=>'STRING','title'=>'Landline No.','required'=>false]);
        $this->addTableCol(['id'=>'address','type'=>'TEXT','title'=>'Address','required'=>false]);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSortOrder('T.client_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);
        $this->addAction(['type'=>'popup','text'=>'Locations','url'=>'client_location','mode'=>'view','width'=>600,'height'=>600]);

        $this->addSearch(['client_id','name','contact','account_code','email','cell','tel','address','note','status'],['rows'=>3]);


        $this->setupFiles(['table'=>TABLE_PREFIX.'file','location'=>'CLT','max_no'=>100,
                           'icon'=>'<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;manage',
                           'list'=>true,'list_no'=>5,'storage'=>STORAGE,
                           'link_url'=>'client_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600']);

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
