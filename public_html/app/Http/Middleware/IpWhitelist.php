<?php


namespace Whiskey\Bourbon\App\Http\Middleware;


use Whiskey\Bourbon\App\Http\MiddlewareInterface;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;


/**
 * IpWhitelist middleware class
 * @package Whiskey\Bourbon\App\Middleware
 */
class IpWhitelist implements MiddlewareInterface
{
    
    
    protected $_whitelist = 
        [
            '127.0.0.1',
            '::1'
        ];


    /**
     * Pass the request through the middleware to be handled
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function handle(Request $request, Response $response)
    {

        if (!in_array($request->ip, $this->_whitelist))
        {
            $response->deny();
        }

    }


}