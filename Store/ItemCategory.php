<?php 
namespace App\Store;

use Seriti\Tools\Table;

class ItemCategory extends Table 
{
    protected $access_rank = 100;

    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Item category','row_name_plural'=>'Item categories','col_label'=>'name'];
        parent::setup($param);

        $config = $this->getContainer('config');

        //$this->user_access_level and $this->user_id set in parent::setup() above
        if(isset(ACCESS_RANK[$this->user_access_level])) $this->access_rank = ACCESS_RANK[$this->user_access_level];

        $this->addForeignKey(array('table'=>TABLE_PREFIX.'item','col_id'=>'category_id','message'=>'Items allocated to this category'));
                
        $this->addTableCol(array('id'=>'category_id','type'=>'INTEGER','title'=>'Category ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Name'));
        $this->addTableCol(array('id'=>'access','type'=>'STRING','title'=>'Access rights','new'=>'ADMIN',
                                 'hint'=>'(GOD can do anything!<br/>
                                 ADMIN allows users to add, and delete most data.<br/>
                                 USER allows users to add and edit but not delete data.<br/>
                                 VIEW allows users to see anything but not to modify or add any data!'));
        $this->addTableCol(array('id'=>'sort','type'=>'INTEGER','title'=>'Rank','hint'=>'Number to indicate dropdown display order'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        $this->addSortOrder('T.sort','Sort order','DEFAULT');

        $this->addSql('WHERE','T.access_level >= "'.$this->access_rank.'" ');

        $this->addAction(array('type'=>'edit','text'=>'edit','icon_text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R'));

        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);
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
}
?>
