<?php


namespace Whiskey\Bourbon\App\Http\Middleware;


use Whiskey\Bourbon\App\Http\MiddlewareInterface;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;
use Whiskey\Bourbon\Storage\Cache\Handler as Cache;


/**
 * RateLimit middleware class
 * @package Whiskey\Bourbon\App\Middleware
 */
class RateLimit implements MiddlewareInterface
{


    protected $_cache = null;


    /**
     * Set up dependencies
     * @param Cache $cache Cache object
     */
    public function __construct(Cache $cache)
    {

        $this->_cache = $cache;

    }


    /**
     * Pass the request through the middleware to be handled
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function handle(Request $request, Response $response)
    {

        /*
         * Number of seconds between permitted visits to any given URL and how
         * long (in seconds) each rate limit log should be cached for
         */
        $rate_limit = 3;
        $cache_ttl  = (60 * 60 * 24);

        /*
         * Gather required data
         */
        $request_array = ['url' => $request->url, 'ip' => $request->ip];
        $request_hash  = hash('sha512', json_encode($request_array));
        $cache_key     = '_rate_limit_' . $request_hash;
        $requests      = $this->_cache->read($cache_key);
        $over_limit    = false;

        /*
         * If there's no data stored, start afresh
         */
        if (!is_array($requests))
        {
            $requests = [];
        }

        foreach ($requests as $key => $entry)
        {

            /*
             * Prune old entries
             */
            if ($entry <= (time() - $rate_limit))
            {
                unset($requests[$key]);
            }

            /*
             * Found a request within the rate limit
             */
            else
            {
                $over_limit = true;
            }

        }

        /*
         * Deny access if the rate limit has been exceeded
         */
        if ($over_limit)
        {
            $response->deny();
        }

        /*
         * Record the current request
         */
        else
        {

            $requests[] = microtime(true);

            $this->_cache->write($cache_key, $requests, $cache_ttl);

        }

    }


}