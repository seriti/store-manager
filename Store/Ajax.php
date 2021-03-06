<?php
namespace App\Store;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Form;
use Seriti\Tools\Secure;

use App\Store\Helpers;


class Ajax
{
    protected $container;
    protected $db;
    protected $user;

    protected $debug = false;
    //Class accessed outside /App/Auction so cannot use TABLE_PREFIX constant
    protected $table_prefix = '';
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->db = $this->container->mysql;
        $this->user = $this->container->user;

        //Class accessed outside /App/Auction so cannot use TABLE_PREFIX constant
        $module = $this->container->config->get('module','store');
        $this->table_prefix = $module['table_prefix'];

        if(defined('\Seriti\Tools\DEBUG')) $this->debug = \Seriti\Tools\DEBUG;
    }


    public function __invoke($request, $response, $args)
    {
        $mode = '';
        $output = '';

        if(isset($_GET['mode'])) $mode = Secure::clean('basic',$_GET['mode']);

        if($mode === 'supplier_orders') $output = $this->getSupplierOrders($_POST);
        if($mode === 'deliver_confirm') $output = $this->confirmDeliver($_POST);
        if($mode === 'client_locations') $output = $this->getClientLocations($_POST);
        if($mode === 'stock_item') $output = $this->getStockItem($_POST);

            return $output;
    }

    protected function getSupplierOrders($form)
    {
        $error = '';
        $html = '';
       
        $supplier_id = Secure::clean('alpha',$form['supplier_id']);
        if($supplier_id === 'SELECT') {
           $sql = 'SELECT O.order_id, CONCAT(S.name," order ID[",O.order_id,"]: ",O.date_create) '.
                  'FROM '.$this->table_prefix.'order AS O JOIN '.$this->table_prefix.'supplier AS S ON(O.supplier_id = S.supplier_id) '.
                  'WHERE O.status = "NEW" ORDER BY S.name, O.date_create, O.order_id';
        } else {
            $sql = 'SELECT order_id, CONCAT("Order ID[",order_id,"]: ",date_create) FROM '.$this->table_prefix.'order '.
                    'WHERE supplier_id= "'.$this->db->escapeSql($supplier_id).'" AND status = "NEW" ORDER BY date_create, order_id';    
        }
        $orders = $this->db->readSqlList($sql);    
       
        if($orders == 0) {
            $orders = ['0'=>'No orders found. Receive without order'];
        } else {
            $orders = ['0'=>'Select order, or Receive without order'] + $orders;
        }
        $output = json_encode($orders);

        return $output;

    }

    protected function getClientLocations($form)
    {
        $error = '';
        $html = '';
       
        $client_id = Secure::clean('alpha',$form['client_id']);
        if($client_id === 'SELECT') {
            $sql = 'SELECT 0,"Unknown, Select client."';
        } else {
            $sql = 'SELECT location_id,name FROM '.TABLE_PREFIX.'client_location '.
                   'WHERE client_id= "'.$this->db->escapeSql($client_id).'" AND status <> "HIDE" ORDER BY sort';  
        }
        $locations = $this->db->readSqlList($sql);    
       
        if($locations == 0) {
            $locations = ['0'=>'No locations found. Deliver without location'];
        } else {
            $locations = ['0'=>'Select location, or Deliver without location'] + $locations;
        }
        $output = json_encode($locations);

        return $output;

    }

    protected function confirmDeliver($form)
    {
        $error = '';
        $error_tmp = '';
        $html = '';
        $output = [];

        $deliver = Helpers::get($this->db,$this->table_prefix,'deliver',$form['deliver_id']);
        if($deliver == 0) {
            $error .= 'Delivery ID['.$form['deliver_id'].'] is INVALID';
        } else {
            if($deliver['status'] === 'INVOICED') $error .= 'Delivery ID['.$form['deliver_id'].'] has already been invoiced.';
        }
        
        //NB: allows for toggle between DELIVERED/NEW
        if($error === '') {
            $table_deliver = $this->table_prefix.'deliver';
            $update = [];
            $where = ['deliver_id'=>$form['deliver_id']];
            $update['date'] = date('Y-m-d');
            //NB: javascript param string converts boolean to string
            if($form['checked'] === 'true') $update['status'] = 'DELIVERED'; else $update['status'] = 'NEW';
            $this->db->updateRecord($table_deliver,$update,$where,$error_tmp);
            if($error_tmp !== '') $error .= 'Could not update elivery status.';
        }
                
        if($error !== '') {
            $output['errors_found'] = true;
            $output['error'] = $error;
        } else {
            $output['errors_found'] = false;
            $output['message'] = 'Delivery ID['.$deliver['deliver_id'].'] status set to '.$update['status'].' cgecked:'.$form['checked'];
            $output['status'] = $update['status'];
        }    
  
        $output = json_encode($output);

        return $output;

    }

    protected function getStockItem($form)
    {
        $error = '';
        $html = '';

        if(isset($form['source'])) $source = $form['source']; else $source = 'item';

        if($source === 'store') {
            $data_id = Secure::clean('alpha',$form['data_id']); 
            $stock = Helpers::getStockInStoreId($this->db,$this->table_prefix,$data_id);  
            $item_id = $stock['item_id'];
        } else {
            $item_id = Secure::clean('alpha',$form['item_id']);    
        }
              
        $item = Helpers::get($this->db,$this->table_prefix,'item',$item_id);   
       
        if($item == 0) {
            $output = 'ERROR: No item['.$item_id.'] data found';
        } else {
            $output = json_encode($item);
        }
        
        return $output;

    }
}