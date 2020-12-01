<?php
namespace App\Store;

use Seriti\Tools\Upload;
use Seriti\Tools\STORAGE;
use Seriti\Tools\BASE_PATH;
use Seriti\Tools\BASE_UPLOAD;
class ReceiveFile extends Upload
{
    public function setup($param = [])
    {
        $id_prefix = 'REC';

        $param = [];
        $param['row_name'] = 'Receive document';
        $param['pop_up'] = true;
        $param['col_label'] = 'file_name_orig';
        $param['update_calling_page'] = true;
        $param['prefix'] = $id_prefix; //will prefix file_name if used, but file_id.ext is unique
        $param['upload_location'] = $id_prefix;
        parent::setup($param);

        //$this->allow_ext = ['Documents'=>['doc','xls','ppt','pdf','rtf','docx','xlsx','pptx','ods','odt','txt','csv','zip','gz','msg','eml']]; 

        $param = [];
        $param['table']     = TABLE_PREFIX.'receive';
        $param['key']       = 'receive_id';
        $param['label']     = 'name';
        $param['child_col'] = 'location_id';
        $param['child_prefix'] = $id_prefix;
        $param['show_sql'] = 'SELECT CONCAT("Receive: ",name) FROM '.TABLE_PREFIX.'receive WHERE receive_id = "{KEY_VAL}" ';
        $this->setupMaster($param);

        $this->addAction(['type'=>'edit','text'=>'edit details of','icon_text'=>'edit']);
        $this->addAction(['type'=>'delete','text'=>'delete','pos'=>'R','icon_text'=>'delete']);
        $this->info['ADD'] = 'If you have Mozilla Firefox or Google Chrome you should be able to drag and drop files directly from your file explorer.'.
                               'Alternatively you can click [Add Documents] button to select multiple documents for upload using [Shift] or [Ctrl] keys. '.
                               'Finally you need to click [Upload selected Documents] button to upload documents to server.';

        //$access['read_only'] = true;
        //$this->modifyAccess($access);
    }
}
