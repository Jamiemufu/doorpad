<?php


namespace Whiskey\Bourbon\App\Http\Middleware;


use Whiskey\Bourbon\App\Http\MiddlewareInterface;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;
use Whiskey\Bourbon\Auth\Handler as Auth;


/**
 * Authentication middleware class
 * @package Whiskey\Bourbon\App\Middleware
 */
class Authentication implements MiddlewareInterface
{


    protected $_auth = null;


    /**
     * Set up dependencies
     * @param Auth $auth Auth object
     */
    public function __construct(Auth $auth)
    {

        $this->_auth = $auth;

    }


    /**
     * Pass the request through the middleware to be handled
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function handle(Request $request, Response $response)
    {

        if ($this->_auth->isGuest())
        {
            $response->deny();
        }

    }


}