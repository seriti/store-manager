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
}