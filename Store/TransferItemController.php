<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\TransferItem;

class TransferItemController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'transfer_item';
        $table = new TransferItem($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Transfer items';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
