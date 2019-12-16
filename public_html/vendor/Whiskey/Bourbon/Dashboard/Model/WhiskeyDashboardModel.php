<?php


namespace Whiskey\Bourbon\Dashboard\Model;


use Exception;
use finfo;
use Whiskey\Bourbon\App\Facade\Cache;
use Whiskey\Bourbon\App\Facade\Db;
use Whiskey\Bourbon\App\Facade\Email;
use Whiskey\Bourbon\App\Facade\Server;
use Whiskey\Bourbon\App\Facade\Storage;
use Whiskey\Bourbon\App\Facade\Templating;
use Whiskey\Bourbon\App\Facade\Utils;
use Whiskey\Bourbon\App\Http\MainModel;


class WhiskeyDashboardModel extends MainModel
{


    /**
     * Get the statuses of the required/recommended extensions
     * @return array Array of extensions and their active statuses
     */
    public function getExtensionStatuses()
    {

        $extensions =
            [
                'MySQLi'   => extension_loaded('mysqli'),
                'GD'       => extension_loaded('gd'),
                'cURL'     => extension_loaded('curl'),
                'finfo'    => class_exists(finfo::class),
                'php-json' => function_exists('json_encode')
            ];

        if (function_exists('apache_get_modules'))
        {

            $apache_extensions =
                [
                    'mod_rewrite' => in_array('mod_rewrite', apache_get_modules()),
                    'mod_mime'    => in_array('mod_mime',    apache_get_modules()),
                    'mod_deflate' => in_array('mod_deflate', apache_get_modules()),
                    'mod_expires' => in_array('mod_expires', apache_get_modules()),
                    'mod_headers' => in_array('mod_headers', apache_get_modules())
                ];

            $extensions = array_merge($extensions, $apache_extensions);

        }

        return $extensions;

    }


    /**
     * Get the statuses of the database connections
     * @return array Array of database connections and their active statuses
     */
    public function getDatabaseStatuses()
    {

        $result               = [];
        $database_connections = Db::getConnectionNames();

        foreach ($database_connections as $connection_name)
        {

            try
            {
                $db_connected = Db::swap($connection_name)->connected();
            }

            catch (Exception $exception)
            {
                $db_connected = false;
            }

            $result[$connection_name] = $db_connected;

        }

        return $result;

    }


    /**
     * Get the statuses of the templating engines
     * @return array Array of templating engines and their active statuses
     */
    public function getTemplatingEngineStatuses()
    {

        $result             = [];
        $templating_engines = Templating::getEngines();

        foreach ($templating_engines as $templating_engine)
        {
            $engine        = $templating_engine['engine'];
            $name          = ucwords($engine->getName());
            $status        = $engine->isActive();
            $result[$name] = $status;
        }

        return $result;

    }


    /**
     * Get the statuses of the storage engines
     * @return array Array of storage engines and their active statuses
     */
    public function getStorageEngineStatuses()
    {

        $result          = [];
        $storage_engines = Storage::getEngines();

        foreach ($storage_engines as $storage_engine)
        {
            $engine        = $storage_engine['engine'];
            $name          = ucwords($engine->getName());
            $status        = $engine->isActive();
            $result[$name] = $status;
        }

        return $result;

    }


    /**
     * Get the statuses of the caching engines
     * @return array Array of caching engines and their active statuses
     */
    public function getCachingEngineStatuses()
    {

        $result          = [];
        $caching_engines = Cache::getEngines();

        foreach ($caching_engines as $caching_engine)
        {
            $engine        = $caching_engine['engine'];
            $name          = ucwords($engine->getName());
            $status        = $engine->isActive();
            $result[$name] = $status;
        }

        return $result;

    }


    /**
     * Get the statuses of the email engines
     * @return array Array of email engines and their active statuses
     */
    public function getEmailEngineStatuses()
    {

        $result        = [];
        $email_engines = Email::getEngines();

        foreach ($email_engines as $email_engine)
        {
            $engine        = $email_engine['engine'];
            $name          = ucwords($engine->getName());
            $status        = $engine->isActive();
            $result[$name] = $status;
        }

        return $result;

    }


    /**
     * Get the availability of the system's random sources
     * @return array Array of the system's random sources and their availability
     */
    public function getRandomSourceStatuses()
    {

        $result =
            [
                '/dev/random'  => false,
                '/dev/urandom' => false
            ];

        foreach ($result as $path => &$status)
        {
            $status = is_readable($path);
        }

        return $result;

    }


    /**
     * Get information about the application's environment
     * @return array Array of information about the application's environment
     */
    public function getEnvironmentalInformation()
    {

        $information =
            [
                'System User'    => Server::whoAmI(),
                'System Info'    => php_uname(),
                'PHP Version'    => PHP_VERSION,
                'Server Version' => $_SERVER['SERVER_SOFTWARE'],
                'Server Name'    => $_SERVER['SERVER_NAME'],
                'Server IP'      => $_SERVER['SERVER_ADDR'],
                'Domain Root'    => $_SERVER['DOCUMENT_ROOT'],
                'Server Admin'   => $_SERVER['SERVER_ADMIN'],
                'Disk Usage'     => Utils::friendlyFileSize(Server::disk()->used) . ' / ' . Utils::friendlyFileSize(Server::disk()->total)
            ];

        if (Server::memory()->total)
        {

            $cpu_models = Server::cpu()->names;
            $cpu_model  = reset($cpu_models);

            $server_information =
                [
                    'Memory Usage' => Utils::friendlyFileSize(Server::memory()->used) . ' / ' . Utils::friendlyFileSize(Server::memory()->total),
                    'CPU Model'    => $cpu_model,
                    'CPU Cores'    => Server::cpu()->cores
                ];

            $information = array_merge($information, $server_information);

        }

        return $information;

    }


}