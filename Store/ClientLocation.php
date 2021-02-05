<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class ClientLocation extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Client location','col_label'=>'name','pop_up'=>true];
        parent::setup($param);

        $this->setupMaster(['table'=>TABLE_PREFIX.'client','key'=>'client_id','child_col'=>'client_id',
                            'show_sql'=>'SELECT CONCAT("Client: ",name) FROM '.TABLE_PREFIX.'client WHERE client_id = "{KEY_VAL}" ']);

        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'location ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'address','type'=>'TEXT','title'=>'Address','required'=>false,'list'=>true]);
        $this->addTableCol(['id'=>'contact','type'=>'STRING','title'=>'Contact person']);
        $this->addTableCol(['id'=>'cell','type'=>'STRING','title'=>'Cell','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'tel','type'=>'STRING','title'=>'Tel','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'email','type'=>'EMAIL','title'=>'Email','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'map_lat','type'=>'DECIMAL','title'=>'Map latitude','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'map_lng','type'=>'DECIMAL','title'=>'Map longitude','required'=>false,'list'=>false]);
        $this->addTableCol(['id'=>'sort','type'=>'INTEGER','title'=>'Sort','new'=>10]);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSortOrder('T.sort','Sort order','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['location_id','name','contact','cell','tel','email','status'],['rows'=>4]);
        
        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);
    }

    //protected function modifyRowValue($col_id,$data,&$value){}  
    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected  function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
