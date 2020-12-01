<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\ReceiveItem;

class ReceiveItemController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'receive_item';
        $table = new ReceiveItem($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Receive items';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
