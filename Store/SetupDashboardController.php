<?php
namespace App\Store;

use Psr\Container\ContainerInterface;

use App\Store\SetupDashboard;

class SetupDashboardController
{
    protected $container;
    

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $dashboard = new SetupDashboard($this->container->mysql,$this->container);
                
        $dashboard->setup();
        $html = $dashboard->viewBlocks();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'setup dashboard';
        //$template['javascript'] = $setup->getJavascript();

        return $this->container->view->render($response,'admin.php',$template);
    }
}