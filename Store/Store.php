<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Store extends Table
{
    protected $access_rank = 100;

    public function setup($param = []) 
    {
        $param = ['row_name'=>'Store','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $config = $this->getContainer('config');

        //$this->user_access_level and $this->user_id set in parent::setup() above
        if(isset(ACCESS_RANK[$this->user_access_level])) $this->access_rank = ACCESS_RANK[$this->user_access_level];

        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'store_ID','key'=>true,'key_auto'=>true]);
        $this->addTableCol(['id'=>'location_id','type'=>'INTEGER','title'=>'Location','join'=>'name FROM '.TABLE_PREFIX.'location WHERE location_id']);
        $this->addTableCol(['id'=>'name','type'=>'STRING','title'=>'Name']);
        $this->addTableCol(['id'=>'note','type'=>'TEXT','title'=>'Note','required'=>false]);
        $this->addTableCol(['id'=>'access','type'=>'STRING','title'=>'Access','new'=>'ADMIN',
                            'hint'=>'(GOD can do anything!<br/>
                                     ADMIN allows users to add, and delete most data.<br/>
                                     USER allows users to add and edit but not delete data.<br/>
                                     VIEW allows users to see anything but not to modify or add any data!']);
        $this->addTableCol(['id'=>'status','type'=>'STRING','title'=>'Status']);

        $this->addSql('WHERE','T.access_level >= "'.$this->access_rank.'" ');

        $this->addSortOrder('T.store_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['store_id','name','note','access','access_level','status'],['rows'=>1]);

        $this->addSelect('location_id','SELECT location_id, name FROM '.TABLE_PREFIX.'location ORDER BY name');
        $this->addSelect('access',['list'=>$config->get('user','access'),'list_assoc'=>false]);


    }

    protected function modifyRowValue($col_id,$data,&$value) {
        if($col_id === 'access') {
            if($value === 'GOD') {
                $value = 'GOD only';
            } else {
                $value .= ' & higher';    
            }    
        }    
    } 

    protected function afterUpdate($id,$edit_type,$form) {
        $sql = 'UPDATE '.$this->table.' SET access_level = "'.ACCESS_RANK[$form['access']].'" '.
               'WHERE '.$this->key['id'].' = "'.$this->db->escapeSql($id).'" ';

        $this->db->executeSql($sql,$error_tmp);  
         
    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
