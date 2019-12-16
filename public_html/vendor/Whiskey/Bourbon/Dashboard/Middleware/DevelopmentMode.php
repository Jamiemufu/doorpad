<?php


namespace Whiskey\Bourbon\Dashboard\Middleware;


use Whiskey\Bourbon\App\Http\MiddlewareInterface;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;


/**
 * DevelopmentMode middleware class
 * @package Whiskey\Bourbon\Dashboard\Middleware
 */
class DevelopmentMode implements MiddlewareInterface
{


    /**
     * Pass the request through the middleware to be handled
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function handle(Request $request, Response $response)
    {

        if (!($_ENV['APP_ENVIRONMENT'] == 'development' OR $_ENV['APP_DEBUG']))
        {
            $response->deny();
        }

    }


}