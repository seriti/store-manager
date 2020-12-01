<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\Store;

class StoreController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'store';
        $table = new Store($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Stores';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
