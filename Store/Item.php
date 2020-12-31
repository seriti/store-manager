<?php
namespace App\Store;

use Exception;
use Seriti\Tools\Table;
use Seriti\Tools\Secure;
use Seriti\Tools\Form;
use Seriti\Tools\Validate;

class Item extends Table 
{
    protected $access_rank = 100;
    protected $location_base = 'ITF';

    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Item','col_label'=>'name'];
        parent::setup($param);

        $config = $this->getContainer('config');
     
        //$this->user_access_level and $this->user_id set in parent::setup() above
        if(isset(ACCESS_RANK[$this->user_access_level])) $this->access_rank = ACCESS_RANK[$this->user_access_level];

        $this->addTableCol(array('id'=>'item_id','type'=>'INTEGER','title'=>'Item ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Item name'));
        $this->addTableCol(array('id'=>'code','type'=>'STRING','title'=>'Item code',
                                 'hint'=>'Unique code typically shorter than name and should not change once set'));
        $this->addTableCol(array('id'=>'category_id','type'=>'INTEGER','title'=>'Category','join'=>'name FROM '.TABLE_PREFIX.'item_category WHERE category_id'));
        $this->addTableCol(array('id'=>'units','type'=>'STRING','title'=>'Units','new'=>'Kg'));
        $this->addTableCol(array('id'=>'units_kg_convert','type'=>'DECIMAL','title'=>'Units convert to Kg','new'=>'1'));
        $this->addTableCol(array('id'=>'price_buy','type'=>'DECIMAL','title'=>'Cost Price','new'=>0));
        $this->addTableCol(array('id'=>'price_sell','type'=>'DECIMAL','title'=>'Sale Price','new'=>0));
        $this->addTableCol(array('id'=>'tax_free','type'=>'BOOLEAN','title'=>'Tax free','new'=>0));
        $this->addTableCol(array('id'=>'note','type'=>'TEXT','title'=>'Notes','required'=>false));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        //$this->addSortOrder('T.name','Name','DEFAULT');

        $this->addSql('JOIN','JOIN '.TABLE_PREFIX.'item_category AS C ON(T.category_id = C.category_id) ');

        $this->addSql('WHERE','C.access_level >= "'.$this->access_rank.'" ');

        $this->setupFiles(array('table'=>TABLE_PREFIX.'file','location'=>$this->location_base,'max_no'=>1000,'icon'=>'<img src="/images/folder.png" border="0">manage',
                                'list'=>true,'list_no'=>100,
                                'link_url'=>'item_file','link_data'=>'SIMPLE','width'=>'800','height'=>'720'));

        $this->addAction(array('type'=>'edit','text'=>'edit','icon'=>false));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R','icon'=>false));
        
        $this->addSearch(array('name','category_id','units','price','note','status'),array('rows'=>2));

        $status = ['OK','HIDE'];
        $this->addSelect('status',['list'=>$status,'list_assoc'=>false]);
        $this->addSelect('category_id','SELECT category_id,name FROM '.TABLE_PREFIX.'item_category WHERE status <> "HIDE" ORDER BY sort');
    }

    protected function beforeUpdate($id,$context,&$data,&$error) 
    {
        $sql = 'SELECT COUNT(*) FROM '.$this->table.' '.
               'WHERE code = "'.$this->db->escapeSql($data['code']).'" ';
        if($context === 'UPDATE') $sql .= 'AND item_id <> "'.$this->db->escapeSql($id).'" ';
        $count = $this->db->readSqlValue($sql);
        if($count != 0) {
            $this->addError('Item code['.$data['code'].'] is altready in use. Code must be unique.');
        }

    }
   
} 