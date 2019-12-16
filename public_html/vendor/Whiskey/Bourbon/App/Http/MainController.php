<?php


namespace Whiskey\Bourbon\App\Http;


use stdClass;
use Closure;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\App\Http\Controller\MissingActionException;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\Html\FlashMessage;
use Whiskey\Bourbon\Routing\Handler as Router;
use Whiskey\Bourbon\Templating\Handler as Templating;
use Whiskey\Bourbon\Storage\Session;
use Whiskey\Bourbon\Html\FormBuilder;
use Whiskey\Bourbon\Security\Csrf;
use Whiskey\Bourbon\Auth\Handler as Auth;
use Whiskey\Bourbon\Auth\Acl\Handler as Acl;
use Whiskey\Bourbon\App\AppEnv;


/**
 * MainController base controller class
 * @package Whiskey\Bourbon\App\Http
 */
class MainController
{


    protected static $_bindings = [];


    protected $_dependencies = null;
    protected $_request      = null;
    protected $_response     = null;
    protected $_layout_file  = false;
    protected $_view_file    = false;
    protected $_variables    = [];


    public $_model  = null;
    public $_cached = false;


    /**
     * Instantiate the main controller
     * @param Bourbon      $bourbon       Bourbon bootstrap object
     * @param Request      $request       HTTP Request object
     * @param Response     $response      HTTP Response object
     * @param Csrf         $csrf          Csrf object
     * @param FlashMessage $flash_message FlashMessage object
     * @param Router       $router        Router object
     * @param Templating   $templating    Templating object
     * @param Session      $session       Session object
     * @param Auth         $auth          Auth object
     * @param Acl          $acl           Acl object
     * @param AppEnv       $app_env       AppEnv object
     * @throws InvalidArgumentException if any dependencies are not provided
     */
    public function __construct(Bourbon $bourbon, Request $request, Response $response, Csrf $csrf, FlashMessage $flash_message, Router $router, Templating $templating, Session $session, Auth $auth, Acl $acl, AppEnv $app_env)
    {

        if (!isset($bourbon) OR
            !isset($request) OR
            !isset($response) OR
            !isset($csrf) OR
            !isset($flash_message) OR
            !isset($router) OR
            !isset($templating) OR
            !isset($session) OR
            !isset($auth) OR
            !isset($acl) OR
            !isset($app_env))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        /*
         * Store Request and Response objects
         */
        $this->_request  = $request;
        $this->_response = $response;

        /*
         * Store dependencies
         */
        $this->_dependencies                = new stdClass();
        $this->_dependencies->csrf          = $csrf;
        $this->_dependencies->bourbon       = $bourbon;
        $this->_dependencies->flash_message = $flash_message;
        $this->_dependencies->router        = $router;
        $this->_dependencies->templating    = $templating;
        $this->_dependencies->session       = $session;
        $this->_dependencies->auth          = $auth;
        $this->_dependencies->acl           = $acl;
        $this->_dependencies->app_env       = $app_env;

        /*
         * Recover variables stored before a redirect
         */
        $redirect_variables = $this->_request->getRedirectVariables();

        if (is_array($redirect_variables))
        {
            $this->_variables = array_merge($this->_variables, $redirect_variables);
        }

        /*
         * Call the _init() method
         */
        $this->_init();

    }


    /**
     * Extendable method, executed when the controller is instantiated
     */
    public function _init() {}


    /**
     * Generate a URL relative to the domain root
     * @param  string ... Multiple strings representing fully-qualified controller class, action name and slugs
     * @return string     URL relative to domain root (blank if no routes match)
     */
    public function _link()
    {

        try
        {

            /*
             * See if the controller exists -- if it does not, try prepending
             * the default namespace
             */
            $arguments  = func_get_args();
            $controller = reset($arguments);

            if (!class_exists($controller))
            {

                $new_controller = 'Whiskey\\Bourbon\\App\\Http\\Controller\\' . $controller;

                if (class_exists($new_controller))
                {
                    $arguments[0] = $new_controller;
                }

            }

            /*
             * Pass the arguments onto the router's generateUrl() method
             */
            return call_user_func_array([$this->_dependencies->router, 'generateUrl'], $arguments);

        }

        catch (Exception $exception)
        {
            return '';
        }

    }


    /**
     * Set a flash message
     * @param string $message      Message content
     * @param bool   $good_message Whether a message is considered positive or not
     * @param bool   $persist      Whether the message should persist across redirects
     */
    public function _message($message = '', $good_message = true, $persist = true)
    {

        call_user_func_array([$this->_dependencies->flash_message, 'set'], func_get_args());

    }


    /**
     * Get the template filenames
     * @return object Object of layout and view template filenames (or FALSE if templates should not be rendered)
     */
    public function _getTemplateFiles()
    {
        
        $result         = new stdClass();
        $result->layout = $this->_layout_file;
        $result->view   = $this->_view_file;

        return $result;

    }


    /**
     * Store one or many variables to be passed to the template
     * @param string|array $key   Name to be given to variable, or array of key/value pairs
     * @param mixed        $value Value to be given to variable
     */
    public function _setVariable($key = null, $value = '')
    {

        /*
         * Array of variables
         */
        if (is_array($key))
        {

            foreach ($key as $variable_key => $variable_value)
            {
                $this->_setVariable($variable_key, $variable_value);
            }

        }

        /*
         * Single variable
         */
        else
        {

            if (is_string($key))
            {
                $this->_variables[$key] = $value;
            }

        }

    }


    /**
     * Get the variables set by the controller for the template
     * @return array Array of variables, with variable names as indices
     */
    public function _getVariables()
    {

        return $this->_variables;

    }


    /**
     * Set the template file to be rendered
     * @param string|bool $view   Filename of view template file to be rendered, or FALSE if nothing should be rendered
     * @param string|bool $layout Filename of layout template file to be rendered, or FALSE if nothing should be rendered
     */
    public function _render($view = null, $layout = null)
    {

        /*
         * View template
         */
        if (!is_null($view))
        {
            $this->_view_file = $view;
        }
        
        /*
         * Layout template
         */
        if (!is_null($layout))
        {
            $this->_layout_file = $layout;
        }

    }


    /**
     * Renders an Ice content block template
     * @param  string $filename  Filename of content block, relative to 'blocks' directory
     * @param  array  $variables Array of key/value pairs to be set as variables
     * @return string            Rendered block template string
     */
    public function _renderBlock($filename = '', array $variables = [])
    {

        $layout_file = 'blocks' . DIRECTORY_SEPARATOR . $filename;
        $engine      = $this->_dependencies->templating->getLoaderFor($layout_file);
        $variables   = array_merge($this->_getVariables(), $variables, ['_helper' => $this]);

        return $engine->render($layout_file, $variables, true);

    }


    /**
     * Check whether a specific permission is granted for the action
     * @param string $permission_name Name of permission to check
     */
    public function _hasPermission($permission_name = '')
    {

        $controller = $this->_dependencies->app_env->controller();
        $action     = $this->_dependencies->app_env->action();
        $details    = $this->_dependencies->auth->details();

        return $this->_dependencies->acl->isPermissionAllowed($controller, $action, $details, $permission_name);

    }


    /**
     * Cache the output of the action
     * @param bool $state Whether to cache the action's output
     */
    public function _cacheOutput($state = true)
    {

        $this->_cached = !!$state;

    }


    /**
     * Generate a HTML <img> tag
     * @param  string $filename   Image filename
     * @param  array  $attributes Array of key/value attribute pairs
     * @return string             HTML <img> tag
     */
    public function _image($filename = '', array $attributes = [])
    {

        $image_dir_server = $this->_dependencies->bourbon->getPublicDirectory('images');
        $image_dir_client = $this->_dependencies->bourbon->getPublicPath('images');
        $is_local_file    = is_readable(realpath(strtok($image_dir_server . $filename, '?')));

        $result = '<img src="' . ($is_local_file ? $image_dir_client . $filename : $filename) . '"';

        foreach ($attributes as $var => $value)
        {
            $result .= ' ' . $var . '="' . str_replace('"', '&quot;', $value) . '"';
        }

        $result .= ' />';

        return $result;

    }


    /**
     * Generate a HTML <link> CSS tag
     * @param  string $filename CSS filename
     * @return string           HTML <link> CSS tag
     */
    public function _css($filename = '')
    {

        $css_dir_server = $this->_dependencies->bourbon->getPublicDirectory('css');
        $css_dir_client = $this->_dependencies->bourbon->getPublicPath('css');
        $is_local_file  = is_readable(realpath(strtok($css_dir_server . $filename, '?')));

        return '<link href="' . ($is_local_file ? $css_dir_client . $filename : $filename) . '" rel="stylesheet" />';

    }


    /**
     * Generate a HTML <script> tag
     * @param  string $filename JavaScript filename
     * @return string           HTML <script> tag
     */
    public function _js($filename = '')
    {

        $js_dir_server = $this->_dependencies->bourbon->getPublicDirectory('js');
        $js_dir_client = $this->_dependencies->bourbon->getPublicPath('js');
        $is_local_file = is_readable(realpath(strtok($js_dir_server . $filename, '?')));

        return '<script src="' . ($is_local_file ? $js_dir_client . $filename : $filename) . '"></script>';

    }


    /**
     * Instantiate and return a FormBuilder object for POST forms
     * @param  string      $action     Form action URL
     * @param  array       $attributes Array of key/value attribute pairs
     * @return FormBuilder             FormBuilder object
     */
    public function _postForm($action = '', array $attributes = [])
    {

        $csrf         = $this->_dependencies->csrf;
        $form_builder = new FormBuilder($csrf, 'POST', $action, $attributes);

        return $form_builder;

    }


    /**
     * Instantiate and return a FormBuilder object for GET forms
     * @param  string      $action     Form action URL
     * @param  array       $attributes Array of key/value attribute pairs
     * @return FormBuilder             FormBuilder object
     */
    public function _getForm($action = '', array $attributes = [])
    {

        $csrf         = $this->_dependencies->csrf;
        $form_builder = new FormBuilder($csrf, 'GET', $action, $attributes);

        return $form_builder;

    }


    /**
     * Store a closure to be bound as a controller method
     * @param string  $name    Name to use for method in controller
     * @param Closure $closure Closure to bind to controller
     * @throws InvalidArgumentException if the method name is not valid
     * @throws InvalidArgumentException if the closure is not valid
     */
    public static function _bindMethod($name = '', Closure $closure)
    {

        if ($name == '' OR
            method_exists(static::class, $name))
        {
            throw new InvalidArgumentException('Invalid name for bound controller method');
        }

        if (!is_object($closure) AND ($closure instanceof Closure))
        {
            throw new InvalidArgumentException('Invalid closure provided');
        }

        self::$_bindings[$name] = $closure;

    }


    /**
     * Fallback to execute bound methods
     * @param  string $name      Method name
     * @param  array  $arguments Method arguments
     * @return mixed             Return value of bound method
     * @throws MissingActionException if the bound method cannot be found
     */
    public function __call($name = '', array $arguments = [])
    {

        if (isset(self::$_bindings[$name]))
        {
            return call_user_func_array(self::$_bindings[$name], $arguments);
        }

        else
        {
            throw new MissingActionException('Controller method \'' . $name . '\' does not exist');
        }

    }


}