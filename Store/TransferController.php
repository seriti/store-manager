<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\Transfer;

class TransferController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'transfer';
        $table = new Transfer($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Transfers';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
