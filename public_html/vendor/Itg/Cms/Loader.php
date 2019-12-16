<?php


namespace Itg\Cms;


use Whiskey\Bourbon\Config\Type\Routes;
use Itg\Cms\Http\Controller\AccountController;
use Itg\Cms\Http\Controller\AdminController;
use Itg\Cms\Http\Controller\PageController;
use Itg\Cms\Http\Model\AccountModel;
use Itg\Cms\Http\Model\AdminModel;
use Itg\Cms\Http\Middleware\Authentication;
use Itg\Cms\Http\Middleware\Csrf;
use Itg\Buildr\Facade\Nav;
use Whiskey\Bourbon\App\Facade\AppEnv;
use Whiskey\Bourbon\App\Facade\Auth;
use Whiskey\Bourbon\App\Facade\Hooks;
use Itg\Buildr\Facade\Me;
use Itg\Buildr\Facade\Log;


/**
 * CMS Loader class
 * @package Itg\Cms
 */
class Loader
{


    /**
     * Set up the plugin
     */
    public function __construct()
    {

        /*
         * Navigation links
         */
        Hooks::addListener('APP_POST_ROUTING', function()
        {

            Nav::build()->group('Dashboard', 'fa-home')->item('Dashboard', 'fa-home')->target(PageController::class, 'dashboard')->add();
            Nav::build()->group('My Account', 'fa-user')->item('My Account', 'fa-user')->target(AccountController::class, 'my_account')->add();
            Nav::build()->group('Profile', 'fa-user')->item('Profile', 'fa-user')->target(AccountController::class, 'view_user')->hide()->add();
            Nav::build()->group('Sign out', 'fa-home')->item('Visitor Sign Out', 'fa-user')->target(PageController::class, 'sign_out')->add();
            Nav::build()->group('Reports', 'fa-home')->item('Reports', 'fa-cogs')->target(PageController::class, 'reports')->add();

            if (Auth::isLoggedIn() AND Me::isAdmin())
            {
                Nav::build()->group('Administration', 'fa-cogs')->item('Users', 'fa-users')->target(AdminController::class, 'users')->add();
            }

            if (Auth::isLoggedIn() AND Me::isSuperUser())
            {
                Nav::build()->group('Administration', 'fa-cogs')->item('Migrations', 'fa-sort-amount-desc')->target(AdminController::class, 'migrations')->add();
                Nav::build()->group('Backups', 'fa-cloud-download')->item('Database', 'fa-database')->target(AdminController::class, 'backups_database')->add();
                Nav::build()->group('Backups', 'fa-cloud-download')->item('Full Site', 'fa-file-zip-o')->target(AdminController::class, 'backups_site')->add();
            }

            Nav::build()->group('Search', 'fa-search')->item('Search', 'fa-search')->target(PageController::class, 'search')->hide()->add();

        });

        /*
         * Log action
         */
        Hooks::addListener('APP_POST_ROUTING', function()
        {

            Log::registerException(AccountController::class, 'ping');
            Log::logHit(AppEnv::controller(), AppEnv::action(), AppEnv::slugs(), Me::_getInstance());

        });

    }


    /**
     * Set routes for the plugin
     * @return Routes Routes configuration object
     */
    public function _routes()
    {

        $routes = new Routes();


        /*
         * Regular expression route tags
         */
        $routes->addRegex('{alpha}',    '[a-zA-Z]+');
        $routes->addRegex('{num}',      '[0-9]+');
        $routes->addRegex('{alphanum}', '[a-zA-Z0-9]+');


        /*
         * Global middleware
         */
        $routes->addGlobalMiddleware(
            [
                Authentication::class,
                Csrf::class
            ]);

        /*
         * 'Page' controller
         */
        $routes->set('/admin/',
            [
                'controller' => PageController::class,
                'action'     => 'dashboard'
            ]);

        $routes->set('/admin/login/',
            [
                'controller' => PageController::class,
                'action'     => 'login'
            ]);

        $routes->set('/admin/login/attempt/',
            [
                'controller' => PageController::class,
                'action'     => 'login_attempt'
            ]);

        $routes->set('/admin/logout/',
            [
                'controller' => PageController::class,
                'action'     => 'logout'
            ]);

        $routes->set('/admin/search/',
            [
                'controller' => PageController::class,
                'action'     => 'search'
            ]);

        $routes->set('/admin/signout/',
            [
                'controller' => PageController::class,
                'action'     => 'sign_out'
            ]);

        $routes->set('/admin/visitors/signout',
            [
                'controller' => PageController::class,
                'action'     => 'signOutAll'
            ]);

        $routes->set('/admin/reports/',
            [
                'controller' => PageController::class,
                'action'     => 'reports'
            ]);

        $routes->set('/admin/download/reports',
            [
                'controller' => PageController::class,
                'action'     => 'downloadReports'
            ]);

        $routes->set('/admin/404/',
            [
                'controller' => PageController::class,
                'action'     => 'four_oh_four',
                '404'        => true
            ]);

        $routes->set('/admin/500/',
            [
                'controller' => PageController::class,
                'action'     => 'five_hundred',
                '500'        => true
            ]);

        /*
         * 'Account' controller
         */
        $routes->set('/admin/me/',
            [
                'controller' => AccountController::class,
                'model'      => AccountModel::class,
                'action'     => 'my_account'
            ]);

        $routes->set('/admin/users/{num}/',
            [
                'controller' => AccountController::class,
                'model'      => AccountModel::class,
                'action'     => 'view_user'
            ]);

        $routes->set('/admin/me/ping/',
            [
                'http_method' => ['ajax'],
                'controller'  => AccountController::class,
                'model'       => AccountModel::class,
                'action'      => 'ping'
            ]);

        /*
         * 'Admin' controller
         */
        $routes->set('/admin/users/',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'users'
            ]);

        $routes->set('/admin/users/create/',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'create_user'
            ]);

        $routes->set('/admin/users/{num}/edit/',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'edit_user'
            ]);

        $routes->set('/admin/users/{num}/delete/',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'delete_user'
            ]);

        $routes->set('/admin/migrations/',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'migrations'
            ]);

        $routes->set('/admin/migrations/to/{num}/',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'migrate_to'
            ]);

        $routes->set('/admin/backups/database/:',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'backups_database'
            ]);

        $routes->set('/admin/backups/site/:',
            [
                'controller' => AdminController::class,
                'model'      => AdminModel::class,
                'action'     => 'backups_site'
            ]);

        return $routes;

    }


}