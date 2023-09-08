<?php
namespace App\Store;

use Seriti\Tools\Table;
//use Seriti\Tools\Date;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;

class Stock extends Table
{
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Store Stock','col_label'=>'name','pop_up'=>false];
        parent::setup($param);

        $access['read_only'] = true;                         
        $this->modifyAccess($access);

        $this->addTableCol(['id'=>'data_id','type'=>'INTEGER','title'=>'Data ID','key'=>true,'key_auto'=>true,'list'=>false]);
        $this->addTableCol(['id'=>'store_id','type'=>'INTEGER','title'=>'Store','join'=>'name FROM '.TABLE_PREFIX.'store WHERE store_id']);
        $this->addTableCol(['id'=>'stock_id','type'=>'INTEGER','title'=>'Stock ID']);
        $this->addTableCol(['id'=>'item','type'=>'STRING','title'=>'Item','linked'=>'I.name']);
        $this->addTableCol(['id'=>'quantity','type'=>'DECIMAL','title'=>'Quantity']);
        $this->addTableCol(['id'=>'units','type'=>'STRING','title'=>'Units','linked'=>'I.units']);
        $this->addTableCol(['id'=>'supplier','type'=>'STRING','title'=>'Supplier','linked'=>'SU.name']);
        $this->addTableCol(['id'=>'invoice','type'=>'STRING','title'=>'Invoice','linked'=>'S.invoice_no']);
        
        $this->addSql('JOIN','JOIN '.TABLE_PREFIX.'stock AS S ON(T.stock_id = S.stock_id)');
        $this->addSql('JOIN','JOIN '.TABLE_PREFIX.'item AS I ON(S.item_id = I.item_id)');
        $this->addSql('JOIN','JOIN '.TABLE_PREFIX.'supplier AS SU ON(S.supplier_id = SU.supplier_id)');

        $this->addSortOrder('T.data_id DESC','Most recent first','DEFAULT');

        $this->addAction(['type'=>'edit','text'=>'edit','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','icon_text'=>'delete','pos'=>'R']);

        $this->addSearch(['store_id','stock_id','quantity'],['rows'=>2]);
        $this->addSearchXtra('I.name','Item');
        $this->addSearchXtra('SU.name','Supplier');
        $this->addSearchXtra('S.invoice_no','Invoice');

        $this->addSelect('store_id','SELECT store_id, name FROM '.TABLE_PREFIX.'store ORDER BY name');
        
    }

    public function beforeProcess($id = 0)
    {
        if($this->mode == 'list') $this->addMessage('Click Search link below to select Store, Item, Supplier details to find stock for');
    }

    /*** EVENT PLACEHOLDER FUNCTIONS ***/
    //protected function beforeUpdate($id,$context,&$data,&$error) {}
    //protected function afterUpdate($id,$context,$data) {}
    //protected function beforeDelete($id,&$error) {}
    //protected function afterDelete($id) {}
    //protected function beforeValidate($col_id,&$value,&$error,$context) {}

}
