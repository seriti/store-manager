<?php
namespace App\Store;

use Seriti\Tools\Form;
use Seriti\Tools\Dashboard AS DashboardTool;

class SetupDashboard extends DashboardTool
{
    protected $labels = MODULE_STORE['labels'];

    //configure
    public function setup($param = []) 
    {
        $this->col_count = 2;  

        $login_user = $this->getContainer('user'); 
       
                
        $this->addBlock('STORE',1,1,'Store setup');
        $this->addItem('STORE','Manage physical locations',['link'=>"location?mode=list"]); 
        $this->addItem('STORE','Manage stores',['link'=>"store?mode=list"]);    
        

        $this->addBlock('STOCK',1,2,'Stock setup');
        $this->addItem('STOCK','Manage stock items',['link'=>"item?mode=list"]); 
        $this->addItem('STOCK','Manage item categories',['link'=>"item_category?mode=list"]);    
        $this->addItem('STOCK','Manage suppliers',['link'=>"supplier?mode=list"]);    
        
        $this->addBlock('CLIENT',2,1,'Client setup');
        $this->addItem('CLIENT','Manage clients',['link'=>"client?mode=list"]);

        

        if($login_user->getAccessLevel() === 'GOD') {
            /*
            $this->addBlock('USER',2,1,'User setup');
            $this->addItem('USER','NON-admin user settings',['link'=>"user_extend?mode=list"]);
            $this->addItem('USER','Agent setup',['link'=>"agent?mode=list"]);
            */
        }    
        
    }
}

?>