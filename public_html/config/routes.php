<?php


namespace Whiskey\Bourbon\Config;


use Whiskey\Bourbon\Config\Type\Routes;
use Whiskey\Bourbon\App\Http\Middleware\AccessControlList;
use Whiskey\Bourbon\App\Http\Middleware\Authentication;
use Whiskey\Bourbon\App\Http\Middleware\Csrf;
use Whiskey\Bourbon\App\Http\Middleware\Https;
use Whiskey\Bourbon\App\Http\Middleware\IpWhitelist;
use Whiskey\Bourbon\App\Http\Middleware\RateLimit;
use Whiskey\Bourbon\App\Http\Model\PageModel;
use Whiskey\Bourbon\App\Http\Controller\PageController;


$routes = new Routes();


/*
 * Regular expression route tags
 */
$routes->addRegex('{alpha}',    '[a-zA-Z]+');
$routes->addRegex('{num}',      '[0-9]+');
$routes->addRegex('{alphanum}', '[a-zA-Z0-9]+');


/*
 * Routes
 */
$routes->set('/',
    [
        'controller' => PageController::class,
        'model'      => PageModel::class,
        'action'     => 'home'
    ]);

$routes->set('/login/',
    [
        'controller' => PageController::class,
        'model'      => PageModel::class,
        'action'     => 'login'
    ]);

$routes->set('/logout/',
    [
        'controller' => PageController::class,
        'model'      => PageModel::class,
        'action'     => 'logout'
    ]);

$routes->set('/signout/{num}',
    [
        'controller' => PageController::class,
        'model'      => PageModel::class,
        'action'     => 'signout'
    ]);

$routes->set('/404/',
    [
        'controller' => PageController::class,
        'model'      => PageModel::class,
        'action'     => 'four_oh_four',
        '404'        => true
    ]);

$routes->set('/500/',
    [
        'controller' => PageController::class,
        'model'      => PageModel::class,
        'action'     => 'five_hundred',
        '500'        => true
    ]);


return $routes;