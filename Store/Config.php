<?php 
namespace App\Store;

use Psr\Container\ContainerInterface;
use Seriti\Tools\BASE_URL;
use Seriti\Tools\SITE_NAME;

class Config
{
    
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

    }

    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        
        $module = $this->container->config->get('module','store');

        define('MODULE_STORE',$module);
        
        $menu = $this->container->menu;
        $db = $this->container->mysql;
        $user = $this->container->user;
        
        define('TABLE_PREFIX',$module['table_prefix']);
        define('MODULE_ID','STORE');
        define('MODULE_LOGO','<span class="glyphicon glyphicon-th"></span> ');
        define('MODULE_PAGE',URL_CLEAN_LAST);

        define('CLIENT_LOCATION',true); //allow/disallow multiple client delivery locations
        define('DELIVER_NOTE_PRICE',false); //show/hide price data in delivery notes
 
        define('ACCESS_RANK',['GOD'=>1,'ADMIN'=>2,'USER'=>5,'VIEW'=>10]);
        define('TAX_RATE',0.15);
        
        $setup_pages = ['location','store','item','item_category','supplier','client'];

        $setup_link = '';
        if(in_array(MODULE_PAGE,$setup_pages)) {
            $page = 'setup_dashboard';
            $setup_link = '<a href="setup_dashboard"> -- back to setup options --</a><br/><br/>';
        } elseif(stripos(MODULE_PAGE,'_wizard') !== false) {
            $page = str_replace('_wizard','',MODULE_PAGE);
        } else {    
            $page = MODULE_PAGE;
        }
       
        //only show module sub menu for users with normal non-route based access
        if($user->getRouteAccess() === false) {
            $submenu_html = $menu->buildNav($module['route_list'],$page).$setup_link;
            $this->container->view->addAttribute('sub_menu',$submenu_html);
        }
        
        $response = $next($request, $response);
        
        return $response;
    }
}