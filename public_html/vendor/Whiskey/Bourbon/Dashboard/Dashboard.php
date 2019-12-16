<?php


namespace Whiskey\Bourbon\Dashboard;


use stdClass;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\App\Http\MainController;
use Whiskey\Bourbon\Dashboard\Controller\WhiskeyDashboardController;
use Whiskey\Bourbon\Dashboard\Middleware\DevelopmentMode;
use Whiskey\Bourbon\Dashboard\Model\WhiskeyDashboardModel;
use Whiskey\Bourbon\App\AppEnv;
use Whiskey\Bourbon\Auth\Handler as Auth;
use Whiskey\Bourbon\Server\Info as Server;
use Whiskey\Bourbon\Config\Type\Routes;
use Whiskey\Bourbon\Templating\Engine\Ice\Loader as Ice;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;
use Whiskey\Bourbon\App\Migration\Handler as Migration;


/**
 * Dashboard class
 * @package Whiskey\Bourbon\Dashboard
 */
class Dashboard
{


    protected $_dependencies = null;


    /**
     * Instantiate the Dashboard object
     * @param Ice            $ice        Ice object
     * @param MainController $controller MainController object
     * @param Bourbon        $bourbon    Bourbon object
     * @param Db             $db         Db object
     * @param AppEnv         $app_env    AppEnv object
     * @param Auth           $auth       Auth object
     * @param Server         $server     Server object
     * @param Migration      $migration  Migration object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Ice $ice, MainController $controller, Bourbon $bourbon, Db $db, AppEnv $app_env, Auth $auth, Server $server, Migration $migration)
    {

        if (!isset($ice) OR
            !isset($controller) OR
            !isset($bourbon) OR
            !isset($db) OR
            !isset($app_env) OR
            !isset($auth) OR
            !isset($server) OR
            !isset($migration))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies             = new stdClass();
        $this->_dependencies->ice        = $ice;
        $this->_dependencies->controller = $controller;
        $this->_dependencies->bourbon    = $bourbon;
        $this->_dependencies->db         = $db;
        $this->_dependencies->app_env    = $app_env;
        $this->_dependencies->auth       = $auth;
        $this->_dependencies->server     = $server;
        $this->_dependencies->migration  = $migration;

    }


    /**
     * Set routes for the plugin
     * @return Routes Routes configuration object
     */
    public function _routes()
    {

        $base_url = '/_whsky/dashboard/';
        $routes   = new Routes();

        $routes->addGlobalMiddleware(DevelopmentMode::class);

        $routes->set($base_url . 'info/',
            [
                'controller' => WhiskeyDashboardController::class,
                'model'      => WhiskeyDashboardModel::class,
                'action'     => 'info'
            ]);

        $routes->set($base_url . 'info/flush-cache/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'clear_caches'
            ]);

        $routes->set($base_url . 'migrations/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'migrations'
            ]);

        $routes->set($base_url . 'migrations/create/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'create_migration'
            ]);

        $routes->set($base_url . 'migrations/reset-index/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'reset_migrations'
            ]);

        $routes->set($base_url . 'migrations/{num}/action/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'migrate_to'
            ]);

        $routes->set($base_url . 'migrations/{num}/action/stand-alone/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'action_migration'
            ]);

        $routes->set($base_url . 'cron/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'cron'
            ]);

        $routes->set($base_url . 'cron/add/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'cron_add'
            ]);

        $routes->set($base_url . 'cron/delete/',
            [
                'controller' => WhiskeyDashboardController::class,
                'action'     => 'cron_delete'
            ]);

        return $routes;

    }


    /**
     * Get the HTML to display the Dashboard tray widget
     * @return string Dashboard tray widget HTML
     */
    public function getTrayHtml()
    {

        $bourbon      = $this->_dependencies->bourbon;
        $app_env      = $this->_dependencies->app_env;
        $server       = $this->_dependencies->server;
        $db           = $this->_dependencies->db;
        $auth         = $this->_dependencies->auth;
        $auth_details = $auth->details();

        /*
         * Gather information about the environment
         */
        $environment =
            [
                   'Route'             => 'Controller: [' . $app_env->controller() . ']    Action: [' . $app_env->action() . ']    Slugs: [' . implode('/', $app_env->slugs()) . ']',
                   'Authentication'    => ($auth->isLoggedIn() ? (isset($auth_details->username) ? $auth_details->username : (isset($auth_details->email) ? $auth_details->email : json_encode($auth_details))) : 'Not logged in'),
                   'Framework Version' => Bourbon::VERSION,
                   'Environment'       => ucwords(strtolower($_ENV['APP_ENVIRONMENT'])),
                   'Execution Time'    => number_format($bourbon->getExecutionTime(), 5) . 's',
                   'PHP Version'       => phpversion(),
                   'Server User'       => $server->whoAmI(),
                   'Server Name'       => $_SERVER['SERVER_NAME'],
                   'IP Address'        => $_SERVER["SERVER_ADDR"]
            ];

        /*
         * Determine which database connections are active
         */
        $database_connections = [];

        foreach ($db->getConnectionNames() as $connection_name)
        {

            try
            {
                $database_connections[$connection_name] = $db->swap($connection_name)->isConnected();
            }

            catch (Exception $exception)
            {
                $database_connections[$connection_name] = false;
            }

        }

        /*
         * Package up all variables and render the Dashboard tray
         */
        $variables =
            [
                '_helper'              => $this->_dependencies->controller,
                'autoload_logs'        => $this->_dependencies->bourbon->getAutoloadLogs(),
                'database_connections' => $database_connections,
                'environment'          => $environment
            ];

        return $this->_dependencies->ice->render('blocks' . DIRECTORY_SEPARATOR . 'whiskey_dashboard_tray.ice.php', $variables);

    }


    /**
     * Get the HTML to display the migration warning
     * @return string Migration warning HTML
     */
    public function getMigrationWarningHtml()
    {

        $migration   = $this->_dependencies->migration;
        $outstanding = false;
        $variables   = ['_helper' => $this->_dependencies->controller];

        try
        {
            $outstanding = ($migration->areJobsOutstanding() OR count($migration->getSkipped()));
        }

        catch (Exception $exception) {}

        if ($outstanding)
        {
            return $this->_dependencies->ice->render('blocks' . DIRECTORY_SEPARATOR . 'whiskey_migration_warning.ice.php', $variables);
        }

        return '';

    }


}