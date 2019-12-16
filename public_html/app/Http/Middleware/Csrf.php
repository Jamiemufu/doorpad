<?php


namespace Whiskey\Bourbon\App\Http\Middleware;


use Whiskey\Bourbon\App\Http\MiddlewareInterface;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;
use Whiskey\Bourbon\Security\Csrf as CsrfHandler;


/**
 * Csrf middleware class
 * @package Whiskey\Bourbon\App\Middleware
 */
class Csrf implements MiddlewareInterface
{


    protected $_csrf_handler = null;


    /**
     * Set up dependencies
     * @param CsrfHandler $csrf_handler CsrfHandler object
     */
    public function __construct(CsrfHandler $csrf_handler)
    {

        $this->_csrf_handler = $csrf_handler;

    }


    /**
     * Pass the request through the middleware to be handled
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function handle(Request $request, Response $response)
    {

        if ($request->method == 'POST' AND
            !$this->_csrf_handler->checkToken())
        {
            $response->deny();
        }

    }


}