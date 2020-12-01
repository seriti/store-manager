<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\OrderItem;

class OrderItemController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'order_item';
        $table = new OrderItem($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Order items';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
