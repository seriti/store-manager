<?php 
namespace App\Store;

use Seriti\Tools\Table;
use Seriti\Tools\TABLE_USER;

class UserExtend extends Table 
{
    protected function beforeUpdate($id,$edit_type,&$form,&$error_str) {
        if($form['parameter'] === 'HOURLY_RATE') {
          if(!is_numeric($form['value'])) $error_str .= 'Hourly rate not a valid number!';
        }  
    } 
    
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Setting','col_label'=>'parameter'];
        parent::setup($param);        

        $this->addTableCol(['id'=>'extend_id','type'=>'INTEGER','title'=>'Extend ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'user_id','type'=>'INTEGER','title'=>'User','join'=>'CONCAT(name,": ",email) FROM '.TABLE_USER.' WHERE user_id']);
        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'Linked Store','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'cell','type'=>'STRING','title'=>'Cellphone','required'=>false]);
        $this->addTableCol(['id'=>'tel','type'=>'STRING','title'=>'Telephone','required'=>false]);
        $this->addTableCol(['id'=>'email_alt','type'=>'EMAIL','title'=>'Email alternative','required'=>false]);
        $this->addTableCol(['id'=>'address','type'=>'TEXT','title'=>'Physical address','required'=>false]);
        
        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'view','text'=>'view'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));

        $this->addSearch(array('user_id','store_id','cell','tel','email_alt','address'),array('rows'=>2));

        $this->addSelect('user_id','SELECT user_id,name FROM '.TABLE_USER.' WHERE status = "OK"');
        $this->addSelect('store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
    }    

}
?>
