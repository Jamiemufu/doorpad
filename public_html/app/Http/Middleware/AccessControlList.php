<?php


namespace Whiskey\Bourbon\App\Http\Middleware;


use Whiskey\Bourbon\App\Http\MiddlewareInterface;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;
use Whiskey\Bourbon\Auth\Acl\Handler as Acl;
use Whiskey\Bourbon\Auth\Handler as Auth;
use Whiskey\Bourbon\App\AppEnv;


/**
 * Authentication middleware class
 * @package Whiskey\Bourbon\App\Middleware
 */
class AccessControlList implements MiddlewareInterface
{


    protected $_acl     = null;
    protected $_auth    = null;
    protected $_app_env = null;


    /**
     * Set up dependencies
     * @param Acl    $acl     Acl object
     * @param Auth   $auth    Auth object
     * @param AppEnv $app_env AppEnv object
     */
    public function __construct(Acl $acl, Auth $auth, AppEnv $app_env)
    {

        $this->_acl     = $acl;
        $this->_auth    = $auth;
        $this->_app_env = $app_env;

    }


    /**
     * Pass the request through the middleware to be handled
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function handle(Request $request, Response $response)
    {

        $details    = $this->_auth->details();
        $controller = $this->_app_env->controller();
        $action     = $this->_app_env->action();
        $allowed    = $this->_acl->isAllowed($controller, $action, $details);

        if ($allowed === false)
        {
            $response->deny();
        }

    }


}