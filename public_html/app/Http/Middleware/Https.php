<?php


namespace Whiskey\Bourbon\App\Http\Middleware;


use Whiskey\Bourbon\App\Http\MiddlewareInterface;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;


/**
 * Https middleware class
 * @package Whiskey\Bourbon\App\Middleware
 */
class Https implements MiddlewareInterface
{


    /**
     * Pass the request through the middleware to be handled
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function handle(Request $request, Response $response)
    {

        $url = $request->url;

        if (mb_substr($url, 0, 7) == 'http://')
        {

            $https_url = 'https://' . mb_substr($url, 7);

            $response->redirect($https_url);

        }

    }


}