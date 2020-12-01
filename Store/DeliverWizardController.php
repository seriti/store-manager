<?php
namespace App\Store;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Template;

use App\Store\DeliverWizard;

class DeliverWizardController
{
    protected $container;
        

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $db = $this->container->mysql;
        $cache = $this->container->cache;

        $user_specific = true;
        $cache_name = 'deliver_wizard';
        $cache->setCache($cache_name,$user_specific);

        $wizard_template = new Template(BASE_TEMPLATE);
        
        $wizard = new DeliverWizard($db,$this->container,$cache,$wizard_template);
        $wizard->setup();        

        $html = $wizard->process();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Delivery wizard';
        //$template['javascript'] = $wizard->getJavascript();

        return $this->container->view->render($response,'admin.php',$template);
    }
}