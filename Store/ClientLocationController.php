<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\ClientLocation;

class ClientLocationController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'client_location';
        $table = new ClientLocation($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        //$template['title'] = MODULE_LOGO.'All Client locations';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
