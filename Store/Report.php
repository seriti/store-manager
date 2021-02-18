<?php
namespace App\Store;

use Seriti\Tools\Form;
use Seriti\Tools\Report AS ReportTool;

use App\Store\Helpers;

class Report extends ReportTool
{
     

    //configure
    public function setup() 
    {
        //$this->report_header = 'WTF';
        $this->report_select_title = 'Select Report:';
        $this->always_list_reports = true;

        $param = ['input'=>['select_store','select_format']];
        $this->addReport('STOCK_ALL','Current Stock',$param); 
        
        $param = ['input'=>['select_format']];
        $this->addReport('STOCK_ALL_STORES','Current Stock, items by store',$param); 
        
        
        //$this->addInput('select_provider','Select service provider');
        $this->addInput('select_store','Select store');
        //$this->addInput('select_date_from','From date:'); 
        //$this->addInput('select_date_to','To date:'); 
        $this->addInput('select_format',''); 
    }

    protected function viewInput($id,$form = []) 
    {
        $html = '';
        
        
        if($id === 'select_store') {
            $param = [];
            $param['class'] = 'form-control input-medium';
            $param['xtra'] = ['ALL'=>'All stores'];
            $sql = 'SELECT store_id,name FROM '.TABLE_PREFIX.'store ORDER BY name'; 
            if(isset($form['store_id'])) $store_id = $form['store_id']; else $store_id = 'ALL';
            $html .= Form::sqlList($sql,$this->db,'store_id',$store_id,$param);
        }
              
        
        
        /*
        if($id === 'select_date_from') {
            $param = [];
            $param['class'] = $this->classes['date'];
            if(isset($form['date_from'])) $date_from = $form['date_from']; else $date_from = date('Y-m-d',mktime(0,0,0,date('m')-12,date('j'),date('Y')));
            $html .= Form::textInput('date_from',$date_from,$param);
        }

        if($id === 'select_date_to') {
            $param = [];
            $param['class'] = $this->classes['date'];
            if(isset($form['date_to'])) $date_to = $form['date_to']; else $date_to = date('Y-m-d');
            $html .= Form::textInput('date_to',$date_to,$param);
        }      
        */

        if($id === 'select_format') {
            if(isset($form['format'])) $format = $form['format']; else $format = 'HTML';
            $html .= Form::radiobutton('format','PDF',$format).'&nbsp;<img src="/images/pdf_icon.gif">&nbsp;PDF document<br/>';
            $html .= Form::radiobutton('format','CSV',$format).'&nbsp;<img src="/images/excel_icon.gif">&nbsp;CSV/Excel document<br/>';
            $html .= Form::radiobutton('format','HTML',$format).'&nbsp;Show on page<br/>';
        }
        

        return $html;       
    }

    protected function processReport($id,$form = []) 
    {
        $html = '';
        $error = '';
        $options = [];
        $options['format'] = $form['format'];
        
        if($id === 'STOCK_ALL') {
            $html .= Helpers::stockReport($this->db,'SUMMARY',$form['store_id'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'STOCK_ALL_STORES') {
            $html .= Helpers::stockReportAllStores($this->db,'SUMMARY',$options,$error);
            if($error !== '') $this->addError($error);
        }
        
        return $html;
    }

}
