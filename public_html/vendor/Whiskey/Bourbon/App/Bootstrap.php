<?php


namespace Whiskey\Bourbon\App;


use Exception;
use Closure;
use stdClass;
use ReflectionClass;
use Dotenv\Dotenv;
use Patchwork\Utf8\Bootup as Patchwork;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;
use Whiskey\Bourbon\Config\Collection as ConfigCollection;
use Whiskey\Bourbon\Config\Type\Routes as RouteConfig;
use Whiskey\Bourbon\Cli;
use Whiskey\Bourbon\Dashboard\Dashboard;
use Whiskey\Bourbon\Js as WhskyJs;
use Whiskey\Bourbon\Exception\App\Http\Controller\MissingActionException;
use Whiskey\Bourbon\Helper\Component\SafeString;
use Whiskey\Bourbon\Instance;
use Whiskey\Bourbon\Routing\Handler as Router;
use Whiskey\Bourbon\Routing\Route;
use Whiskey\Bourbon\App\Http\MainController;
use Whiskey\Bourbon\Schedule;
use Whiskey\Bourbon\Security\Crypt;
use Whiskey\Bourbon\Templating\Handler as Templating;
use Whiskey\Bourbon\Exception\Templating\MissingTemplateException;
use Whiskey\Bourbon\Templating\TemplatingInterface;
use Whiskey\Bourbon\ErrorReporting\Handler as ErrorHandler;
use Whiskey\Bourbon\Storage\Session;
use Whiskey\Bourbon\Html\FlashMessage;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Database;
use Whiskey\Bourbon\Hooks\Handler as Hooks;
use Whiskey\Bourbon\App\Listener\Handler as Listener;
use Whiskey\Bourbon\Validation\Handler as Validator;


/**
 * Bootstrapper for the Bourbon framework
 * @package Whiskey\Bourbon\App
 */
class Bootstrap
{


    const VERSION         = '5.0.2';
    const MIN_PHP_VERSION = '5.5.7';


    protected $_start_time           = 0;
    protected $_dependencies         = null;
    protected $_base_directory       = '';
    protected $_public_directory     = '';
    protected $_app_config           = [];
    protected $_template_directories = [];


    protected static $_latest_instance = null;
    protected static $_autoload_logs   = [];


    /**
     * Instantiate a new Bootstrap object to bootstrap the Bourbon instance
     * @param string $base_directory   Optional path to the server side base directory
     * @param string $public_directory Optional path to the client side 'public' directory
     */
    public function __construct($base_directory = '', $public_directory = '')
    {

        $this->_start_time   = microtime(true);
        $this->_dependencies = new stdClass();

        /*
         * Set the base directories (or guess if they haven't been provided)
         */
        $this->_base_directory   = !empty($base_directory)   ? rtrim($base_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR   : rtrim(dirname($_SERVER['SCRIPT_FILENAME']), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->_public_directory = !empty($public_directory) ? rtrim($public_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : rtrim($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_NAME'])) . '_public/';

        static::$_latest_instance = $this;

        /*
         * Decrease the likelihood of session ID clashes
         */
        ini_set('session.hash_function',           'sha512');
        ini_set('session.hash_bits_per_character', 5);
        ini_set('session.entropy_length',          64);

        /*
         * Set a session name unique to the application
         */
        if (session_status() == PHP_SESSION_NONE)
        {
            session_name('bourbon' . hash('md5', __DIR__));
        }

        /*
         * Enable automatic detection of file line endings
         */
        ini_set('auto_detect_line_endings', true);

        /*
         * Stop the server from revealing that it is running PHP
         */
        header_remove('X-Powered-By');

        /*
         * Set an initial timezone
         */
         date_default_timezone_set(@date_default_timezone_get());

    }


    /**
     * Get the latest Bootstrap instance
     * @return Bootstrap|null Bootstrap object (or NULL if the class has yet to be instantiated)
     */
    public static function getInstance()
    {

        return static::$_latest_instance;

    }


    /**
     * Get the execution time of the framework up until the current point
     * @return float Amount of time (in seconds) that the framework has been running for
     */
    public function getExecutionTime()
    {

        return (microtime(true) - $this->_start_time);

    }


    /**
     * Get the base directory path
     * @return string Path to base directory
     */
    public function getBaseDirectory()
    {

        return $this->_base_directory;

    }


    /**
     * Get the client side public directory path
     * @param  string $subdirectory Subdirectory to include on end of path
     * @return string               Path to client side public directory
     */
    public function getPublicPath($subdirectory = '')
    {

        if ($subdirectory != '')
        {
            $subdirectory = trim($subdirectory, '/') . '/';
        }

        return $this->_public_directory . $subdirectory;

    }


    /**
     * Get the public directory path
     * @param  string $subdirectory Subdirectory to include on end of path
     * @return string               Path to public directory
     */
    public function getPublicDirectory($subdirectory = '')
    {

        if ($subdirectory != '')
        {
            $subdirectory = trim($subdirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        return $this->getBaseDirectory() . '_public' . DIRECTORY_SEPARATOR . $subdirectory;

    }


    /**
     * Get the configuration directory path
     * @return string Path to configuration directory
     */
    public function getConfigDirectory()
    {

        return $this->getBaseDirectory() . 'config' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the logs directory path
     * @return string Path to logs directory
     */
    public function getLogDirectory()
    {

        return $this->getBaseDirectory() . 'logs' . DIRECTORY_SEPARATOR;

    }
    
    
    /**
     * Get the extensions directory path
     * @return string Path to extensions directory
     */
    public function getExtensionDirectory()
    {

        return $this->getBaseDirectory() . 'extensions' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the src directory path
     * @return string Path to src directory
     */
    public function getSrcDirectory()
    {

        return $this->getBaseDirectory() . 'src' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the template directory path
     * @return string Path to template directory
     */
    public function getTemplateDirectory()
    {

        return $this->getSrcDirectory() . 'templates' . DIRECTORY_SEPARATOR;

    }
    
    
    /**
     * Get the layout template directory path
     * @param  bool   $relative Whether the result should be relative to the template directory
     * @return string           Path to layout template directory
     */
    public function getLayoutTemplateDirectory($relative = false)
    {

        $directory = 'layouts';
        
        if ($relative)
        {
            return $directory . DIRECTORY_SEPARATOR;
        }

        return $this->getTemplateDirectory() . $directory . DIRECTORY_SEPARATOR;

    }
    
    
    /**
     * Get the view template directory path
     * @param  bool   $relative Whether the result should be relative to the template directory
     * @return string           Path to view template directory
     */
    public function getViewTemplateDirectory($relative = false)
    {
        
        $directory = 'views';
        
        if ($relative)
        {
            return $directory . DIRECTORY_SEPARATOR;
        }

        return $this->getTemplateDirectory() . $directory . DIRECTORY_SEPARATOR;

    }
    
    
    /**
     * Get the block template directory path
     * @param  bool   $relative Whether the result should be relative to the template directory
     * @return string           Path to block template directory
     */
    public function getBlockTemplateDirectory($relative = false)
    {
        
        $directory = 'blocks';
        
        if ($relative)
        {
            return $directory . DIRECTORY_SEPARATOR;
        }

        return $this->getTemplateDirectory() . $directory . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the vendor directory path
     * @return string Path to vendor directory
     */
    public function getVendorDirectory()
    {

        return $this->getBaseDirectory() . 'vendor' . DIRECTORY_SEPARATOR;

    }
    
    
    /**
     * Get the Bourbon vendor directory path
     * @return string Path to Bourbon vendor directory
     */
    public function getBourbonDirectory()
    {

        return $this->getVendorDirectory() . 'Whiskey' . DIRECTORY_SEPARATOR . 'Bourbon' . DIRECTORY_SEPARATOR;

    }
    
    
    /**
     * Get the app directory path
     * @return string Path to app directory
     */
    public function getAppDirectory()
    {
        
        return $this->getBaseDirectory() . 'app' . DIRECTORY_SEPARATOR;
        
    }


    /**
     * Get the Migration directory path
     * @return string Path to Migration directory
     */
    public function getMigrationDirectory()
    {

        return $this->getAppDirectory() . 'Migration' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the Scheduled jobs directory path
     * @return string Path to Scheduled jobs directory
     */
    public function getScheduledJobsDirectory()
    {

        return $this->getAppDirectory() . 'Schedule' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the Listener directory path
     * @return string Path to Listener directory
     */
    public function getListenerDirectory()
    {

        return $this->getAppDirectory() . 'Listener' . DIRECTORY_SEPARATOR;

    }
    
    
    /**
     * Get the Http directory path
     * @return string Path to Http directory
     */
    public function getHttpDirectory()
    {
        
        return $this->getAppDirectory() . 'Http' . DIRECTORY_SEPARATOR; 
        
    }
    
    
    /**
     * Get the Middleware directory path
     * @return string Path to middleware directory
     */
    public function getMiddlewareDirectory()
    {
        
        return $this->getHttpDirectory() . 'Middleware' . DIRECTORY_SEPARATOR; 
        
    }


    /**
     * Get the data directory path
     * @return string Path to data directory
     */
    public function getDataDirectory()
    {

        return $this->getBaseDirectory() . 'data' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the temp directory path
     * @return string Path to temp directory
     */
    public function getTempDirectory()
    {

        return $this->getBaseDirectory() . 'temp' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the cache directory path
     * @return string Path to cache directory
     */
    public function getCacheDirectory()
    {

        return $this->getTempDirectory() . 'cache' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the data cache directory path
     * @return string Path to data cache directory
     */
    public function getDataCacheDirectory()
    {

        return $this->getCacheDirectory() . 'data' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the template cache directory path
     * @return string Path to template cache directory
     */
    public function getTemplateCacheDirectory()
    {

        return $this->getCacheDirectory() . 'templates' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the output cache directory path
     * @return string Path to output cache directory
     */
    public function getOutputCacheDirectory()
    {

        return $this->getCacheDirectory() . 'output' . DIRECTORY_SEPARATOR;

    }


    /**
     * Check for the framework's dependencies and show an error message if any
     * are not met
     */
    protected function _checkDependencies()
    {

        $fail_messages   = [];
        $tmp_dir         = sys_get_temp_dir();
        $bourbon_tmp_dir = $this->getTempDirectory();

        /*
         * Dependency checks
         */
        $php_version_check     = version_compare(PHP_VERSION, static::MIN_PHP_VERSION, '<');
        $tmp_dir_check         = (!is_readable($tmp_dir) OR !is_dir($tmp_dir) OR !is_writable($tmp_dir));
        $bourbon_tmp_dir_check = (!is_readable($bourbon_tmp_dir) OR !is_dir($bourbon_tmp_dir) OR !is_writable($bourbon_tmp_dir));
        $json_check            = !function_exists('\\json_encode');
        $curl_check            = !extension_loaded('curl');
        $finfo_check           = !class_exists('\\finfo');

        /*
         * Failure messages for above checks
         */
        $messages =
            [
                'php_version_check'     => 'PHP ' . static::MIN_PHP_VERSION . ' or greater',
                'tmp_dir_check'         => 'A writable temporary directory on the server',
                'bourbon_tmp_dir_check' => 'A writable temporary directory at ' . $bourbon_tmp_dir,
                'json_check'            => 'The PHP json library',
                'curl_check'            => 'The PHP curl library',
                'finfo_check'           => 'The fileinfo (finfo) library'
            ];

        /*
         * Determine which messages to display
         */
        foreach ($messages as $name => $message)
        {

            if ($$name)
            {
                $fail_messages[] = $message;
            }

        }

        if (count($fail_messages))
        {
            die('<p>Bourbon requires the following in order to run:</p><ul><li>' . implode('</li><li>', $fail_messages) . '</li></ul>');
        }

    }


    /**
     * Accepts an array by reference and checks for the existence of a key,
     * adding it (with the passed value) if absent
     * @param array          $array Array to look in
     * @param string         $key   Name of key to check for
     * @param string|Closure $value Value to insert with key if not present (or closure returning value)
     */
    protected function _populateMissingArrayValue(array &$array, $key = '', $value = null)
    {

        if (!isset($array[$key]))
        {

            if (is_object($value) AND ($value instanceof Closure))
            {
                $value = $value();
            }

            $array[$key] = $value;

        }

    }
    
    
    /**
     * Include compatibility files
     */
    protected function _includeCompatibilityLayer()
    {

        require_once($this->getBourbonDirectory() . '_bourbon_compat.php');

        /*
         * Fill in any missing $_SERVER variables (primarily to maintain
         * compatibility when running from the terminal)
         */
        $this->_populateMissingArrayValue($_SERVER, 'HTTPS',           function() { return ''; });
        $this->_populateMissingArrayValue($_SERVER, 'HTTP_HOST',       function() { return 'localhost'; });
        $this->_populateMissingArrayValue($_SERVER, 'REMOTE_ADDR',     function() { return ''; });
        $this->_populateMissingArrayValue($_SERVER, 'HTTP_USER_AGENT', function() { return ''; });
        $this->_populateMissingArrayValue($_SERVER, 'REQUEST_METHOD',  function() { return 'GET'; });
        $this->_populateMissingArrayValue($_SERVER, 'REQUEST_URI',     function()
        {

            $args = '';

            if (isset($_SERVER['argv'][1]))
            {
                $args = $_SERVER['argv'][1];
            }

            $args = trim($args, '/');

            return $args;

        });
        $this->_populateMissingArrayValue($_SERVER, 'SERVER_ADDR',     function() { return '::1'; });
        $this->_populateMissingArrayValue($_SERVER, 'SERVER_ADMIN',    function() { return trim(shell_exec('whoami')) . '@' . trim(shell_exec('hostname')); });
        $this->_populateMissingArrayValue($_SERVER, 'SERVER_NAME',     function() { return trim(shell_exec('hostname')); });
        $this->_populateMissingArrayValue($_SERVER, 'SERVER_PORT',     function() { return '80'; });
        $this->_populateMissingArrayValue($_SERVER, 'SERVER_PROTOCOL', function() { return 'HTTP/1.1'; });
        $this->_populateMissingArrayValue($_SERVER, 'SERVER_SOFTWARE', function() { return isset($_SERVER['TERM_PROGRAM']) ? $_SERVER['TERM_PROGRAM'] : 'Unknown'; });

    }


    /**
     * Register the framework's PSR-4 autoloader
     */
    protected function _registerAutoloader()
    {

        spl_autoload_register(function($class = '')
        {

            /*
             * First see if the class belongs to a core or third-party library
             */
            $class_file = $this->getVendorDirectory() . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

            if (is_readable($class_file))
            {

                require_once($class_file);

                $this->_registerClassLoad($class);

            }
            
            /*
             * Otherwise see if it resides in the app directory
             */
             else if (mb_substr($class, 0, 20) == 'Whiskey\\Bourbon\\App\\')
             {
                 
                 $class_file = $this->getAppDirectory() . str_replace('\\', DIRECTORY_SEPARATOR, mb_substr($class, 20)) . '.php';

                 if (is_readable($class_file))
                 {

                     require_once($class_file);

                     $this->_registerClassLoad($class);

                 }
                 
             }

        });

    }


    /**
     * Register an autoloaded class file
     * @param string $class_file Path to class file
     */
    protected function _registerClassLoad($class_file = '')
    {

        /*
         * If this is the first registered class load, take the application's
         * start time as the global start time
         */
        if (!isset(self::$_autoload_logs['stats']['start_time']))
        {
            self::$_autoload_logs['stats']['start_time'] = $this->_start_time;
        }

        if ($class_file != '')
        {

            $information = ['class'  => $class_file,
                            'time'   => microtime(true),
                            'memory' => memory_get_peak_usage()];

            self::$_autoload_logs['autoloads'][] = $information;

        }

    }


    /**
     * Get the autoload logs
     * @return array Array of autoload logs
     */
    public function getAutoloadLogs()
    {

        $result = self::$_autoload_logs;

        /*
         * Calculate and set some basic stats to use below
         */
        $start_time = $result['stats']['start_time'];
        $end_time   = microtime(true);
        $total_time = ($end_time - $start_time);
        $max_memory = 0;

        $result['stats']['end_time']   = $end_time;
        $result['stats']['total_time'] = $total_time;

        /*
         * Bump up the memory usage maximum, if necessary
         */
        foreach ($result['autoloads'] as $entry)
        {

            if ($entry['memory'] > $max_memory)
            {
                $max_memory = $entry['memory'];
            }

        }

        /*
         * Assign time and memory percentages to each entry
         */
        foreach ($result['autoloads'] as &$entry)
        {
            $entry['time']              -= $start_time;
            $entry['time_percentage']    = (($entry['time'] * 100) / $total_time);
            $entry['memory_percentage']  = (($entry['memory'] / $max_memory) * 100);
        }

        /*
         * Add the current time on, for context
         */
        $result['autoloads'][] =
            [
                'class'             => 'End Of Script',
                'time'              => (microtime(true) - $start_time),
                'time_percentage'   => 100,
                'memory'            => memory_get_peak_usage(),
                'memory_percentage' => 100
            ];

        return $result;

    }


    /**
     * Load auto-loaded plugin files
     */
    protected function _loadAutoPlugins()
    {

        $auto_plugin_files = glob($this->getVendorDirectory() . "auto*.php");

        foreach ($auto_plugin_files as $auto_plugin_file)
        {
            require_once($auto_plugin_file);
        }

    }


    /**
     * Initialise third-party dependencies
     */
    protected function _initDependencies()
    {

        /*
         * Set UTF-8
         */
        if (class_exists(Patchwork::class))
        {
            Patchwork::initAll();
            Patchwork::filterRequestUri();
            Patchwork::filterRequestInputs();
        }

        else
        {

            echo "Please install Composer dependencies\n";

            exit;

        }

    }
    
    
    /**
     * Include application extension PHP files
     */
    protected function _loadExtensions()
    {

        /*
         * Include hooks dependencies
         */
        $this->_dependencies->hooks    = Instance::_retrieve(Hooks::class);
        $this->_dependencies->listener = Instance::_retrieve(Listener::class);

        /*
         * Register all listener hooks
         */
        $this->_dependencies->listener->setDirectory($this->getListenerDirectory());
        $this->_dependencies->listener->registerHooks();

        /*
         * Broadcast the pre-extensions hook
         */
        $this->_dependencies->hooks->broadcast('APP_PRE_EXTENSIONS');

        /*
         * Include extension files
         */
        $extension_dir = $this->getExtensionDirectory();
        
        require_once($extension_dir . 'vars.php');
        require_once($extension_dir . 'functions.php');
        require_once($extension_dir . 'hooks.php');

        /*
         * Broadcast the post-extensions hook
         */
        $this->_dependencies->hooks->broadcast('APP_POST_EXTENSIONS');

    }


    /**
     * Load the application's core dependencies
     */
    protected function _loadDependencies()
    {
        
        Instance::_manualClassRegister($this);

        $this->_dependencies->configuration = Instance::_retrieve(ConfigCollection::class);
        $this->_dependencies->app_env       = Instance::_retrieve(AppEnv::class);
        $this->_dependencies->session       = Instance::_retrieve(Session::class);
        $this->_dependencies->router        = Instance::_retrieve(Router::class);
        $this->_dependencies->error_logger  = Instance::_retrieve(ErrorHandler::class);
        $this->_dependencies->crypt         = Instance::_retrieve(Crypt::class);
        $this->_dependencies->flash_message = Instance::_retrieve(FlashMessage::class);
        $this->_dependencies->templating    = Instance::_retrieve(Templating::class);
        $this->_dependencies->safe_string   = Instance::_retrieve(SafeString::class);

    }


    /**
     * Load configuration files
     * @param string|null $dot_env_location (Optional) path to .env file
     */
    protected function _loadConfigurationFiles($dot_env_location = null)
    {

        /*
         * Broadcast the pre-configuration hook
         */
        $this->_dependencies->hooks->broadcast('APP_PRE_CONFIGURATION');

        /*
         * Load dotenv
         */
        try
        {
            
            $dot_env_file = '.env';
            $dot_env_dir  = $this->getBaseDirectory();
            
            if (!is_null($dot_env_location))
            {
                $dot_env_file = basename($dot_env_location);
                $dot_env_dir  = realpath(mb_substr($dot_env_location, 0, (0 - mb_strlen($dot_env_file))));
            }

            (new Dotenv($dot_env_dir, $dot_env_file))->load();
            
        }

        /*
         * Populate some fallback values if a .env file was not found
         */
        catch (Exception $exception)
        {

            $values =
                [
                    'APP_ENVIRONMENT'      => 'development',
                    'APP_DEBUG'            => '0',
                    'APP_ADMIN_EMAIL'      => '',
                    'APP_CANONICAL_DOMAIN' => '',
                    'DB_HOST'              => '',
                    'DB_DATABASE'          => '',
                    'DB_USERNAME'          => '',
                    'DB_PASSWORD'          => '',
                    'DB_PORT'              => '',
                    'DB_SOCKET'            => '',
                    'AWS_KEY'              => '',
                    'AWS_SECRET'           => '',
                    'S3_BUCKET'            => '',
                    'SES_REGION'           => 'eu-west-1',
                    'RECAPTCHA_SITE_KEY'   => '',
                    'RECAPTCHA_SECRET'     => ''
                ];

            foreach ($values as $key => $value)
            {
                $this->_populateMissingArrayValue($_ENV,    $key, function() use ($value) { return $value; });
                $this->_populateMissingArrayValue($_SERVER, $key, function() use ($value) { return $value; });
            }

        }

        /*
         * Load Bourbon configuration files
         */
        $config_files = glob($this->getConfigDirectory() . '*.php');

        foreach ($config_files as $config_file)
        {

            $config = include_once($config_file);

            $this->_dependencies->configuration->add($config);

        }

        /*
         * Broadcast the post-configuration hook
         */
        $this->_dependencies->hooks->broadcast('APP_POST_CONFIGURATION');

    }


    /**
     * Load the application's core dependencies that rely upon configuration settings
     */
    protected function _loadPostConfigDependencies()
    {

        $this->_dependencies->dashboard = Instance::_retrieve(Dashboard::class);

    }


    /**
     * Register template directories other than the default
     */
    protected function _registerTemplateDirectories()
    {

        $this->addTemplateDirectory(Dashboard::class);

    }


    /**
     * Initialise the Request and Response objects
     */
    protected function _initialiseRequestResponse()
    {

        $this->_dependencies->request         = Instance::_retrieve(Request::class);
        $this->_dependencies->response        = Instance::_retrieve(Response::class);
        $this->_dependencies->main_controller = Instance::_retrieve(MainController::class);

    }


    /**
     * Set up application configuration options
     */
    protected function _setUpAppConfiguration()
    {

        /*
         * Store directories that have already been determined
         */
        $app_env = $this->_dependencies->app_env;

        $app_env->set('publicDir', (string)$this->getPublicPath());
        $app_env->set('imageDir',  (string)$this->getPublicPath('images'));
        $app_env->set('cssDir',    (string)$this->getPublicPath('css'));
        $app_env->set('jsDir',     (string)$this->getPublicPath('js'));
        $app_env->set('fontDir',   (string)$this->getPublicPath('fonts'));

        /*
         * Store reference to this instance
         */
        $app_env->set('bourbon', $this);

        /*
         * Load configuration file values into the bootstrapper
         */
        $this->_app_config = [];
        $configs           = $this->_dependencies->configuration->get('general');

        foreach ($configs as $config)
        {

            foreach ($config->getAllValues() as $key => $value)
            {
                $this->_app_config[$key] = $value;
            }

        }

        /*
         * Action specific configuration settings
         */
        $timezone = $this->getConfiguration('timezone');

        if (!is_null($timezone))
        {
            date_default_timezone_set($timezone);
        }

        /*
         * If not in the production environment, set a robots.txt route and
         * matching header
         */
        if ($_ENV['APP_ENVIRONMENT'] != 'production')
        {

            $this->_dependencies->response->headers->set('X-Robots-Tag: noindex, nofollow');

            $robots = function()
            {

                $this->_response->headers->set('Content-Type: text/plain');

                $this->_response->body = "User-agent: *\nDisallow: /";

            };

            $main_controller = $this->_dependencies->main_controller;
            $robots          = Closure::bind($robots, $main_controller, $main_controller);
            $http_methods    = ['get', 'post', 'put', 'delete', 'ajax'];

            foreach ($http_methods as $http_method)
            {

                $route = new Route('robots.txt', ['http_method' => $http_method], $robots);

                $this->_dependencies->router->add($route);

            }

        }

        /*
         * Apply default settings to dependencies that can't be applied inside
         * configuration files themselves
         */
        $this->_dependencies->crypt->setDefaultKey($this->getConfiguration('project_key'));

    }


    /**
     * Set up class aliases
     */
    protected function _setUpClassAliases()
    {

        $configs = $this->_dependencies->configuration->get('class_aliases');

        foreach ($configs as $config)
        {

            foreach ($config->getAllValues() as $class_name => $alias)
            {
                class_alias($class_name, $alias);
            }

        }

    }


    /**
     * Get a configuration value
     * @param  string     $name Name of configuration option
     * @return mixed|null       Configuration value (or NULL if not found)
     */
    public function getConfiguration($name = '')
    {

        if (isset($this->_app_config[$name]))
        {
            return $this->_app_config[$name];
        }

        return null;

    }


    /**
     * Set up the exception handler
     */
    protected function _initialiseExceptionHandling()
    {

        $error_logger = $this->_dependencies->error_logger;
        $configs      = $this->_dependencies->configuration->get('engines');

        foreach ($configs as $config)
        {

            /*
             * Register logging engines
             */
            $config_value = $config->getValue('logging');

            if (!is_null($config_value))
            {

                foreach ($config_value as $log_levels)
                {

                    foreach ($log_levels as $log_level => $engine_class)
                    {

                        /*
                         * If only a single logger has been specified, put it into
                         * an array to save duplicating code below
                         */
                        if (!is_array($engine_class))
                        {
                            $engine_class = [$engine_class];
                        }

                        /*
                         * Retrieve an instance of each logger
                         */
                        foreach ($engine_class as &$class_object)
                        {
                            $class_object = Instance::_retrieve($class_object);
                        }

                        /*
                         * Register the logger(s) against the error type
                         */
                        $error_logger->mapErrorType($log_level, $engine_class);

                    }

                }

            }

        }

        /*
         * Include backtraces if not in the production environment
         */
        if ($_ENV['APP_ENVIRONMENT'] != 'production')
        {
            $error_logger->includeBacktraces(true);
        }

        /*
         * Set the global exception handler
         */
        set_exception_handler([$this, 'exceptionHandler']);

    }


    /**
     * Uncaught exception handler
     * @param Exception $exception Exception object
     */
    public function exceptionHandler(Exception $exception)
    {

        $number  = (int)$exception->getCode();
        $string  = (string)$exception->getMessage() . "\nStack trace:\n" . $exception->getTraceAsString() . "\n  thrown";
        $file    = (string)$exception->getFile();
        $line    = (int)$exception->getLine();
        $context = [];

        $this->_dependencies->error_logger->log($number, $string, $file, $line, $context);

        /*
         * Set a 500 error and render the appropriate template if in the
         * production environment
         */
        if ($_ENV['APP_ENVIRONMENT'] == 'production')
        {
            $this->_executeRoute(true);
            $this->_dependencies->response->output();
        }

    }


    /**
     * Make database connections
     */
    protected function _connectDatabases()
    {

        $connections = $this->_dependencies->configuration->get('database');

        foreach ($connections as $connection)
        {

            foreach ($connection->getAllValues() as $connection_name => $connection_details)
            {

                /*
                 * Instantiate the database handler, if necessary
                 */
                if (!isset($this->_dependencies->database))
                {
                    $this->_dependencies->database = Instance::_retrieve(Database::class);
                }

                /*
                 * Set up the connection
                 */
                if ($connection_details['database'] != '' AND
                    $connection_details['username'] != '' AND
                    $connection_details['password'] != '')
                {
                    $this->_dependencies->database->add($connection_name, $connection_details);
                }

            }

        }

    }


    /**
     * Set up core engines based on the 'engines' configuration file
     */
    protected function _setUpCoreEngines()
    {

        /*
         * Get all 'engines' configuration objects
         */
        $configs = $this->_dependencies->configuration->get('engines');

        /*
         * Get all of the configuration value arrays, in turn
         */
        foreach ($configs as $config)
        {

            /*
             * Get the engine key class name mappings for the current config
             */
            $class_mappings = $config->getHandlerMappings();

            /*
             * Retrieve the handler class for each mapped engine type
             */
            foreach ($class_mappings as $engine_key => $class_name)
            {

                $handler      = Instance::_retrieve($class_name);
                $config_value = $config->getValue($engine_key);

                if (!is_null($config_value))
                {

                    /*
                     * Register each engine with the handler
                     */
                    foreach ($config_value as $engine_class_name)
                    {
                        $handler->registerEngine($engine_class_name);
                    }

                }

            }

        }

    }


    /**
     * Import an alternative template directory
     * @param string $class_name Name of class to import the directory of
     */
    public function addTemplateDirectory($class_name = '')
    {

        /*
         * Convert the class name to a directory
         */
        $directory = trim($class_name, '\\');
        $directory = explode('\\', $directory);

        array_pop($directory);

        $directory  = implode(DIRECTORY_SEPARATOR, $directory);
        $directory  = $this->getVendorDirectory() . $directory;
        $directory  = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $directory .= 'templates' . DIRECTORY_SEPARATOR;

        /*
         * Add the directory
         */
        $this->_template_directories[] = $directory;

        /*
         * Add routes, if any are set
         */
        $instance = Instance::_retrieve($class_name);

        if (method_exists($instance, '_routes'))
        {

            $routes = $instance->_routes();

            if ($routes instanceof RouteConfig)
            {
                $this->_dependencies->configuration->add($routes);
            }

        }

    }


    /**
     * Set up templating engines based on the 'engines' configuration file
     */
    protected function _setUpTemplatingEngines()
    {

        $configs    = $this->_dependencies->configuration->get('engines');
        $templating = $this->_dependencies->templating;
        $base_dir   = $this->getTemplateDirectory();
        $cache_dir  = $this->getTemplateCacheDirectory();

        /*
         * Set cache directory for all templating engines, if in the production environment
         */
        if ($_ENV['APP_ENVIRONMENT'] == 'production')
        {
            $templating->setCacheDirectory($cache_dir);
        }

        /*
         * Add default base template directory to beginning of template
         * directory array so that it gets priority
         */
        array_unshift($this->_template_directories, $base_dir);

        /*
         * Add all base template directories to templating engines
         */
        foreach ($this->_template_directories as $template_directory)
        {
            $templating->addBaseDirectory($template_directory);
        }

        /*
         * Register all engines defined in the configuration with the Handler
         * class
         */
        foreach ($configs as $config)
        {

            $config_value = $config->getValue('templating');

            if (!is_null($config_value))
            {

                foreach ($config_value as $extensions)
                {

                    foreach ($extensions as $extension => $engine_class)
                    {
                        $templating->registerEngine($extension, $engine_class);
                    }

                }

            }

        }

    }


    /**
     * Set up routes based on the 'routes' configuration file
     */
    protected function _setUpRoutes()
    {

        $router = $this->_dependencies->router;
        $routes = $this->_dependencies->configuration->get('routes');

        /*
         * Pass the current URL path to the router
         */
        $router->setUrlPath($this->getRequestedPath());
        $router->setRootPath($this->getLinkRootPath());

        foreach ($routes as $entry)
        {

            /*
             * Regexes
             */
            foreach ($entry->getRegexes() as $route_tag => $regex)
            {
                $router->addRegex($route_tag, $regex);
            }

            /*
             * Global middleware
             */
            $global_middleware = $entry->getGlobalMiddleware();

            /*
             * Routes
             */
            foreach ($entry->getAllValues() as $url => $http_group)
            {

                foreach ($http_group as $http_method => $route_details)
                {

                    $route_details['http_method'] = $http_method;
                    $action                       = $route_details['action'];
                    $closure                      = null;
                    $regexes                      = $router->getRegexes();
                    $middleware                   = array_merge($route_details['middleware'], $global_middleware);
                    $additional                   = ['middleware' => $middleware, '404' => $route_details['404'], '500' => $route_details['500']];

                    /*
                     * If the action is a closure, separate it out and bind it
                     * to an instance of MainController
                     */
                    if ((is_object($action) AND ($action instanceof Closure)))
                    {
                        $route_details['action']  = '';
                        $main_controller          = $this->_dependencies->main_controller;
                        $closure                  = Closure::bind($action, $main_controller, $main_controller);
                    }

                    $route = new Route($url, $route_details, $closure, $regexes, $additional);

                    $this->_dependencies->router->add($route);

                }

            }

        }

    }
    
    
    /**
     * Invoke special pages to show if on certain URLs
     */
    protected function _checkForSpecialPages()
    {

        /*
         * CLI-accessible scripts
         */
        if ($this->runningFromCli())
        {

            /*
             * Command-line interface (whsky.sh)
             */
            if ($this->_dependencies->router->isCurrentUrl(Cli::getPath(), true))
            {
                new Cli();
                exit;
            }

            /*
             * Schedule executor
             */
            if ($this->_dependencies->router->isCurrentUrl(Schedule::getPath()))
            {
                new Schedule();
                exit;
            }

        }

        /*
         * Web-accessible pages
         */
        new WhskyJs();
        
    }
    
    
    /**
     * Execute middleware applicable to the current route
     * @param array  $middleware_to_apply Array of fully-qualified middleware class names to execute
     * @param string $controller          Fully-qualified controller class name
     * @param string $action              Action name
     */
    protected function _executeMiddleware(array $middleware_to_apply = [], $controller = '', $action = '')
    {

        $request  = $this->_dependencies->request;
        $response = $this->_dependencies->response;

        foreach ($middleware_to_apply as $middleware)
        {

            /*
             * Check for optional arguments
             */
            $arguments = [];

            if (is_array($middleware))
            {

                foreach ($middleware as $instance => $options)
                {

                    $middleware = $instance;
                    $arguments  = $options;

                    break;

                }

            }

            /*
             * Instantiate the middleware
             */
            $middleware = Instance::_retrieve($middleware);

            /*
             * Check if the current route should be exempt from the middleware
             */
            if (isset($middleware->except[$controller]))
            {

                if (in_array($action, $middleware->except[$controller]))
                {
                    continue;
                }

            }
            
            /*
             * Execute the middleware
             */
            array_unshift($arguments, $response);
            array_unshift($arguments, $request);

            call_user_func_array([$middleware, 'handle'], $arguments);
            
        }
        
    }


    /**
     * Check whether the application is running from the command-line
     * @return bool Whether the application is running from the command-line
     */
    public function runningFromCli()
    {

        if (defined('STDIN'))
        {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) AND
            empty($_SERVER['HTTP_USER_AGENT']) AND
            count($_SERVER['argv']) > 0)
        {
            return true;
        }

        return false;

    }
    
    
    /**
     * Get the URL path requested by the client
     * @return string Requested URL path
     */
    public function getRequestedPath()
    {

        /*
         * Terminal
         */
        if ($this->runningFromCli())
        {

            if (isset($_SERVER['argv'][1]))
            {
                $result = trim($_SERVER['argv'][1], '/');
            }

            else
            {
                $result = '';
            }

        }

        /*
         * Apache with .htaccess
         */
        else if (isset($_GET['_bourbon_path']))
        {
            $result = $_GET['_bourbon_path'];
        }

        /*
         * Generic server without .htaccess
         */
        else
        {
            $result = ltrim(mb_substr($_SERVER['PHP_SELF'], mb_strlen($_SERVER['SCRIPT_NAME'])), '/');
        }

        if (!trim($result, '/'))
        {
            $result = '/';
        }

        return $result;

    }


    /**
     * Get the path to the root of the application, relative to the domain
     * @return string Application root path
     */
    public function getLinkRootPath()
    {

        $script_name = rtrim($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_NAME']));

        if (isset($_GET['_bourbon_path']))
        {
            return $script_name;
        }

        else
        {
            return $script_name . 'index.php/';
        }

    }


    /**
     * Get the filename to which output will be cached
     * @return string|bool Filename of output cache file (or FALSE if the output cannot be cached)
     */
    protected function _getOutputCacheFilename()
    {

        $app_env = $this->_dependencies->app_env;

        /*
         * Always return false if not in the 'production' environment or not
         * using a controller
         */
        if ($_ENV['APP_ENVIRONMENT'] != 'production' OR
            $app_env->controller() == '')
        {
            return false;
        }

        /*
         * Create a hash of the route details, to be used for the filename
         */
        $action_hash = hash('sha512', json_encode(
            [
                'model'      => $app_env->model(),
                'controller' => $app_env->controller(),
                'action'     => $app_env->action(),
                'slugs'      => $app_env->slugs()
            ]
        ));

        return $this->getOutputCacheDirectory() . $action_hash . '.html';

    }


    /**
     * Check whether output has been cached for the current route
     * @return string|bool Filename of output cache file (or FALSE if output has not been cached)
     */
    protected function _outputHasBeenCached()
    {

        $filename = $this->_getOutputCacheFilename();

        if ($filename !== false AND is_readable($filename))
        {
            return $filename;
        }

        return false;

    }


    /**
     * Populate the response body from the output cache
     */
    protected function _populateResponseBodyFromCache()
    {

        if (($filename = $this->_outputHasBeenCached()) !== false)
        {
            $this->_dependencies->response->body = file_get_contents($filename);
        }

    }


    /**
     * Check whether output should be cached, based upon the controller
     * @return bool Whether output should be cached
     */
    protected function _outputShouldBeCached()
    {

        if ($this->_getOutputCacheFilename() === false)
        {
            return false;
        }

        $controller = Instance::_retrieve($this->_dependencies->app_env->controller());

        return !!$controller->_cached;

    }


    /**
     * Cache the response body, based upon the current route
     */
    protected function _cacheResponseBody()
    {

        if (($filename = $this->_getOutputCacheFilename()) !== false)
        {
            file_put_contents($filename, $this->_dependencies->response->body);
        }

    }


    /**
     * Get the current route target, instantiate the model & controller, execute
     * applicable middleware and execute the model & controller
     * @param bool $fatal_error Whether the 500 route should be invoked
     * @throws MissingActionException if the controller method does not exist
     */
    protected function _executeRoute($fatal_error = false)
    {

        /*
         * Broadcast the pre-routing hook
         */
        $this->_dependencies->hooks->broadcast('APP_PRE_ROUTING');

        $router = $this->_dependencies->router;

        /*
         * Look for a 500 route if a fatal error has occurred
         */
        if ($fatal_error)
        {

            try
            {
                $route_target = $router->getByAdditionalInformation('500', true);
                $exit_script  = false;
            }

            catch (Exception $exception)
            {
                $exit_script = true;
            }

            /*
             * Send a 500 header regardless of whether a 500 route is found
             */
            $this->_dependencies->response->fatalError($exit_script);

        }

        /*
         * If a fatal error has not occurred, look for a matching route
         */
        else
        {

            try
            {
                $route_target = $router->getCurrentRoute();
            }

            catch (Exception $exception)
            {

                /*
                 * If no matching route has been found, look for a 404 route
                 */
                try
                {
                    $route_target = $router->getByAdditionalInformation('404', true);
                    $exit_script  = false;
                }

                catch (Exception $exception)
                {
                    $exit_script = true;
                }

                /*
                 * Send a 404 header regardless of whether a 404 route is found
                 */
                $this->_dependencies->response->notFound($exit_script);

            }

        }

        /*
         * Get route variables
         */
        $model_name   = $route_target->getModel();
        $model_exists = !empty($model_name);
        $model        = null;
        $controller   = null;
        $action       = $route_target->getAction();
        $slugs        = $route_target->getSlugs($this->getRequestedPath());
        $closure      = $route_target->getClosure();
        $additional   = $route_target->getAdditionalInformation();
        $middleware   = isset($additional['middleware']) ? $additional['middleware'] : [];

        /*
         * Store route variables
         */
        $app_env = $this->_dependencies->app_env;

        $app_env->set('model',      (string)$model_name);
        $app_env->set('controller', (string)(empty($closure) ? $route_target->getController() : ''));
        $app_env->set('action',     (string)$action);
        $app_env->set('slugs',      (array)$slugs);

        /*
         * Broadcast the post-routing hook
         */
        $this->_dependencies->hooks->broadcast('APP_POST_ROUTING');

        /*
         * Sanitise any slugs
         */
        foreach ($slugs as &$slug)
        {
            $slug = $this->_dependencies->safe_string->sanitise($slug);
        }

        /*
         * Broadcast the pre-middleware hook
         */
        $this->_dependencies->hooks->broadcast('APP_PRE_MIDDLEWARE');

        /*
         * Execute applicable middleware
         */
        $this->_executeMiddleware($middleware, $app_env->controller(), $app_env->action());

        /*
         * Broadcast the pre-model hook
         */
        $this->_dependencies->hooks->broadcast('APP_PRE_MODEL');

        /*
         * Instantiate the model and execute its _before() method
         */
        if ($model_exists)
        {

            $model = Instance::_retrieve($model_name);

            /*
             * Execute the model's _before() method
             */
            $model->_before();

            /*
             * Perform any required validation
             */
            $this->_dependencies->validator = Instance::_retrieve(Validator::class);
            $validator                      = $this->_dependencies->validator;

            $model->_validate($validator, $action, $slugs);

            if ($validator->failed())
            {
                $model->_onValidationFail($validator, $action, $slugs);
            }

        }

        /*
         * Broadcast the pre-controller hook
         */
        $this->_dependencies->hooks->broadcast('APP_PRE_CONTROLLER');

        /*
         * Check to see if a cached version of the output exists
         */
        if ($this->_outputHasBeenCached() !== false)
        {
            $this->_populateResponseBodyFromCache();
        }

        /*
         * If the output has not been cached, render it from the template files
         */
        else
        {

            /*
             * Execute the route's closure
             */
            if (!empty($closure))
            {

                $this->_dependencies->main_controller->_model = $model;

                call_user_func_array($closure, $slugs);

                $this->_renderOutput($closure);

            }

            /*
             * Or instantiate and execute the controller
             */
            else
            {

                $controller         = Instance::_retrieve($route_target->getController());
                $controller->_model = $model;

                if (!method_exists($controller, $action))
                {
                    throw new MissingActionException('Method \'' . $action . '()\' does not exist in controller \'' . $route_target->getController() . '\'');
                }

                $this->_defineDefaultTemplates($controller, $action);

                call_user_func_array([$controller, $action], $slugs);

                $this->_renderOutput($controller);

            }

            /*
             * If the controller requested that the output be cached, cache the
             * response body
             */
            if ($this->_outputShouldBeCached())
            {
                $this->_cacheResponseBody();
            }

        }

        /*
         * Broadcast the post-controller hook
         */
        $this->_dependencies->hooks->broadcast('APP_POST_CONTROLLER');

        /*
         * Execute the model's _after() method
         */
        if ($model_exists)
        {
            $model->_after();
        }

    }


    /**
     * Get the templating engine for the selected template file
     * @param  string              $template_file Template filename
     * @return TemplatingInterface                Templating engine loader object
     * @throws MissingTemplateException if the template file cannot be found
     */
    protected function _getTemplatingEngine($template_file = '')
    {

        if ($template_file === false)
        {
            throw new MissingTemplateException('Invalid template file');
        }

        foreach ($this->_template_directories as $directory)
        {

            if (is_readable($directory . $template_file))
            {

                $templating = $this->_dependencies->templating;
                $engine     = $templating->getLoaderFor($template_file);

                return $engine;

            }

        }

        throw new MissingTemplateException('Invalid template file');

    }
    
    
    /**
     * Set up the default templates for the action
     * @param MainController $controller MainController object
     * @param string         $action     Name of controller action
     */
    protected function _defineDefaultTemplates(MainController $controller, $action = '')
    {

        $controller_reflection = new ReflectionClass($controller);
        $controller_template   = $controller_reflection->getDefaultProperties();
        $controller_template   = $controller_template['_layout_file'];
        $template              = $controller_template ? $controller_template : 'default.ice.php';
        $view                  = $controller_reflection->getShortName() . DIRECTORY_SEPARATOR . $action . '.ice.php';

        $controller->_render($view, $template);

    }


    /**
     * Set up the relevant templating engine and render any output
     * @param MainController|Closure $controller_or_closure MainController or bound closure object
     */
    protected function _renderOutput($controller_or_closure)
    {

        /*
         * Broadcast the pre-render hook
         */
        $this->_dependencies->hooks->broadcast('APP_PRE_RENDER');

        /*
         * If a closure has been passed, retrieve the MainController instance,
         * to which it is bound
         */
        $is_closure = (is_object($controller_or_closure) AND ($controller_or_closure instanceof Closure));
        $controller = $is_closure ? $this->_dependencies->main_controller : $controller_or_closure;

        /*
         * Attempt to retrieve the appropriate templating engines and render
         * the templates (if they have been set)
         */
        $response       = $this->_dependencies->response;
        $template_files = $controller->_getTemplateFiles();
        $layout_file    = $template_files->layout;
        $view_file      = $template_files->view;
        $variables      = array_merge($controller->_getVariables(), ['_helper' => $controller]);

        if ($layout_file !== false AND $view_file !== false)
        {

            /*
            * Layout
            */
            $layout_file       = $this->getLayoutTemplateDirectory(true) . $layout_file;
            $templating_engine = $this->_getTemplatingEngine($layout_file);
            $rendered_layout   = $templating_engine->render($layout_file, $variables, true);

            /*
            * View
            */
            $view_file         = $this->getViewTemplateDirectory(true) . $view_file;
            $templating_engine = $this->_getTemplatingEngine($view_file);
            $rendered_view     = $templating_engine->render($view_file, $variables, true);

            /*
             * Compile and set the output body
             */
            $flash_message     = $this->_dependencies->flash_message->get();
            $compiled_template = str_replace('{include:content}', $rendered_view, $rendered_layout);
            $compiled_template = str_replace('{include:message}', $flash_message, $compiled_template);
            $response->body    = $compiled_template;

            /*
             * Clear any redirect variables, now that a template has been output
             */
            $this->_dependencies->session->clear('_bourbon_redirect_variables');

        }

        /*
         * Broadcast the pre-render hook
         */
        $this->_dependencies->hooks->broadcast('APP_POST_RENDER');

    }


    /**
     * Execute the bootstrapper
     * @param string|null $dot_env_location (Optional) path to .env file
     * @param bool        $full_run         Whether to load controllers and templates or just bootstrap
     */
    public function execute($dot_env_location = null, $full_run = true)
    {

        /*
         * Check that the framework's dependencies are met
         */
        $this->_checkDependencies();

        /*
         * Bootstrap the application
         */
        $this->_includeCompatibilityLayer();
        $this->_registerAutoloader();
        $this->_loadAutoPlugins();
        $this->_initDependencies();
        $this->_loadExtensions();
        $this->_loadDependencies();
        $this->_loadConfigurationFiles($dot_env_location);
        $this->_loadPostConfigDependencies();
        $this->_registerTemplateDirectories();
        $this->_initialiseRequestResponse();
        $this->_setUpAppConfiguration();
        $this->_setUpClassAliases();
        $this->_setUpTemplatingEngines();
        $this->_initialiseExceptionHandling();
        $this->_connectDatabases();
        $this->_setUpCoreEngines();
        $this->_setUpRoutes();
        $this->_checkForSpecialPages();

        /*
         * Invoke routing and templating if doing a full run
         */
        if ($full_run)
        {

            /*
             * Execute the route and output the response
             */
            $this->_executeRoute();
            $this->_dependencies->response->output();

            /*
             * Show the dashboard widgets, if in the development environment,
             * not responding to an AJAX request, not accessing from the
             * command-line and where the body contains HTML
             */
            $is_ajax_connection = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

            if (($_ENV['APP_ENVIRONMENT'] == 'development' OR $_ENV['APP_DEBUG']) AND
                !$is_ajax_connection AND
                !$this->runningFromCli() AND
                ($this->_dependencies->response->body != strip_tags($this->_dependencies->response->body)))
            {
                echo $this->_dependencies->dashboard->getTrayHtml();
                echo $this->_dependencies->dashboard->getMigrationWarningHtml();
            }

            /*
             * Show the error widget (if applicable)
             */
            echo $this->_dependencies->error_logger->getWidget();

        }

    }


}