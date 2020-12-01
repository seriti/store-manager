<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\Item;

class ItemController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        
        $table = TABLE_PREFIX.'item';

        $table = new Item($this->container->mysql,$this->container,$table);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Items';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}