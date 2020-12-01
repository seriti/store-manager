<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\Receive;

class ReceiveController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'receive';
        $table = new Receive($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Stock Reception';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
