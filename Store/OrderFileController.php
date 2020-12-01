<?php
namespace App\Store;

use Psr\Container\ContainerInterface;
use App\Store\OrderFile;

class OrderFileController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'file';
        $upload = new OrderFile($this->container->mysql,$this->container,$table_name);

        $upload->setup();
        $html = $upload->processUpload();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Order documents';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
