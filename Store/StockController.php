<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\Stock;

class StockController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'stock_store';
        $table = new Stock($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Stocks';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
