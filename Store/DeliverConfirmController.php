<?php
namespace App\Store;

use Psr\Container\ContainerInterface;

use App\Store\Helpers;

class DeliverConfirmController
{
    protected $container;
        

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $db = $this->container->mysql;
        $user = $this->container->user;
        $user_id = $user->getId();
        $user_access = $user->getAccessLevel();
        
        if($user_access === 'VIEW') {
            $allow_confirm = false;
        } else {
            $allow_confirm = true;
        }

        $user_extend = Helpers::get($db,TABLE_PREFIX,'user_extend',$user_id,'user_id');
        if($user_extend == 0) {
            $store_id = 'ALL';
        } else {
            $store_id = $user_extend['store_id'];
        }

        $html = Helpers::getDeliverConfirm($db,TABLE_PREFIX,$store_id,$allow_confirm);

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Delivery confirmation';
        //$template['javascript'] = $wizard->getJavascript();

        return $this->container->view->render($response,'admin.php',$template);
    }
}