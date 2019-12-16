<?php


namespace Whiskey\Bourbon\Storage\Cache;


use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;


/**
 * CacheInterface interface
 * @package Whiskey\Bourbon\Storage\Cache
 */
interface CacheInterface
{


    /**
     * Get the name of the cache storage extension
     * @return string Name of the storage extension
     */
    public function getName();


    /**
     * Check whether the cache has been successfully initialised
     * @return bool Whether the cache is active
     */
    public function isActive();


    /**
     * Set a cache group
     * @param  string $name Cache group name
     * @return self         Cache object with group name set
     */
    public function group($name);


    /**
     * Retrieve a stored cache variable
     * @param  string $key Name of cache variable
     * @return mixed       Cache variable value
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function read($key);


    /**
     * Store a cache variable
     * @param  string $key   Name of cache variable
     * @param  mixed  $value Cache variable value
     * @param  int    $ttl   Number of seconds after which to discard cached value
     * @return bool          Whether the write was successful
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function write($key, $value, $ttl);


    /**
     * Retrieve a cache variable by its key if it exists, executing and caching
     * the result of a closure if it does not
     * @param  string  $key     Name of cache variable
     * @param  Closure $closure Closure to execute to obtain value to cache (if required)
     * @param  int     $ttl     Number of seconds after which to discard cached value
     * @return mixed            Cache variable value
     * @throws EngineNotInitialisedException if the cache has not been initialised
     * @throws InvalidArgumentException if the closure is not valid
     */
    public function remember($key, Closure $closure, $ttl);


    /**
     * Unset a cache variable
     * @param  string $key Name of cache variable
     * @return bool        Whether the clear was successful
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function clear($key);


    /**
     * Unset cache variables that begin with a certain string
     * @param  string $key_fragment Initial fragment of cache key name
     * @return bool                 Whether the clear was successful
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function prefixClear($key_fragment);


    /**
     * Clear all cache entries
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function clearAll();


    /**
     * Run the garbage collector to discard expired cache variables
     */
    public function gc();


}