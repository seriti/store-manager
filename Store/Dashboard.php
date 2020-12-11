<?php
namespace App\Store;

use Seriti\Tools\Dashboard AS DashboardTool;

class Dashboard extends DashboardTool
{
     

    //configure
    public function setup($param = []) 
    {
        $this->col_count = 2;  

        $login_user = $this->getContainer('user'); 

        //(block_id,col,row,title)
        $this->addBlock('ADD',1,1,'Capture new data');
        $this->addItem('ADD','Order stock',['link'=>'order_wizard']);
        $this->addItem('ADD','Receive stock',['link'=>'receive_wizard']);
        $this->addItem('ADD','Transfer stock',['link'=>'transfer_wizard']);
        $this->addItem('ADD','Deliver stock',['link'=>'deliver_wizard']);

        $this->addBlock('PROCESS',2,1,'View in process data');
        $this->addItem('PROCESS','Deliveries in process',['link'=>'deliver_confirm']);
        
        if($login_user->getAccessLevel() === 'GOD') {
            $this->addBlock('CONFIG',1,2,'Module Configuration');
            $this->addItem('CONFIG','Store User settings',['link'=>'user_extend','icon'=>'setup']);
            $this->addItem('CONFIG','Setup Defaults',['link'=>'setup','icon'=>'setup']);
            $this->addItem('CONFIG','Setup Database',['link'=>'setup_data','icon'=>'setup']);
        }    
        
    }

}

?>