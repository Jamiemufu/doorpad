<?php


namespace Whiskey\Bourbon\Storage\Cache\Engine;


use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Storage\Cache\CacheInterface;


/**
 * Apc cache class
 * @package Whiskey\Bourbon\Storage\Cache\Engine
 */
class Apc implements CacheInterface
{


    protected $_prefix = null;


    /**
     * Get the name of the cache storage extension
     * @return string Name of the storage extension
     */
    public function getName()
    {

        return 'apc';

    }


    /**
     * Initialise the cache
     * @param string $group_name Optional group cache name
     */
    protected function _init($group_name = '')
    {

        if (is_null($this->_prefix))
        {
            $this->_prefix = 'bourboncache__' . hash('md5', __DIR__) . '__' . md5($group_name) . '___';
        }

    }


    /**
     * Check whether the APC cache has been successfully initialised
     * @return bool Whether the cache is active
     */
    public function isActive()
    {

        $this->_init();

        if (extension_loaded('apc') AND
            !is_null($this->_prefix))
        {

            /*
             * Check first for fatal errors (possible CLI issue)
             */
            if (@apc_cache_info() === false)
            {
                return false;
            }

            return true;

        }

        return false;

    }


    /**
     * Set a cache group
     * @param  string $name Cache group name
     * @return self         Apc object with group name set
     */
    public function group($name = '')
    {

        $instance = new static();

        $instance->_init($name);

        return $instance;

    }


    /**
     * Retrieve a stored cache variable
     * @param  string $key Name of cache variable
     * @return mixed       Cache variable value
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function read($key = null)
    {


        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('APC cache not initialised');
        }
        
        $key = (string)$key;
        $key = $this->_prefix . $key;

        if (apc_exists($key))
        {

            $value = apc_fetch($key);

            if (!$value['ttl'] OR $value['ttl'] >= time())
            {
                return $value['value'];
            }

        }

        return null;

    }


    /**
     * Store a cache variable
     * @param  string $key   Name of cache variable
     * @param  mixed  $value Cache variable value
     * @param  int    $ttl   Number of seconds after which to discard cached value
     * @return bool          Whether the write was successful
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function write($key = null, $value = '', $ttl = 0)
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('APC cache not initialised');
        }

        $ttl = (int)$ttl;

        if ($key !== null AND (!((string)$key === '')))
        {

            $key = (string)$key;
            $key = $this->_prefix . $key;

            $value =
                [
                    'value' => $value,
                    'ttl'   => ($ttl ? (time() + $ttl) : 0)
                ];

            return apc_store($key, $value, $ttl);

        }

        return false;

    }


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
    public function remember($key = '', Closure $closure, $ttl = 0)
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('APC cache not initialised');
        }

        if (!(is_object($closure) AND ($closure instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid closure passed');
        }

        $result = $this->read($key);

        if (is_null($result))
        {

            $result = $closure();

            $this->write($key, $result, $ttl);

        }

        return $result;

    }


    /**
     * Unset a cache variable
     * @param  string $key Name of cache variable
     * @return bool        Whether the clear was successful
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function clear($key = null)
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('APC cache not initialised');
        }

        $key = (string)$key;
        $key = $this->_prefix . $key;
        
        if (apc_exists($key))
        {
            return apc_delete($key);
        }

        return true;

    }


    /**
     * Unset cache variables that begin with a certain string
     * @param  string $key_fragment Initial fragment of cache key name
     * @return bool                 Whether the clear was successful
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function prefixClear($key_fragment = null)
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('APC cache not initialised');
        }

        $key_fragment = (string)$key_fragment;
        $apc_cache    = $this->_getRawData(true);

        foreach ($apc_cache as $var => $value)
        {

            if (mb_substr($var, 0, mb_strlen($key_fragment)) == $key_fragment)
            {
                $this->clear($var);
            }

        }

        return true;

    }


    /**
     * Return an array of the cache's contents
     * @param  bool  $keys_only Whether to only return a list of keys without values
     * @return array            Contents of the cache
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    protected function _getRawData($keys_only = false)
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('APC cache not initialised');
        }

        $result    = [];
        $apc_cache = apc_cache_info('user');

        /*
         * Fallback for incompatible APC/u versions
         */
        if (!isset($apc_cache['cache_list']))
        {
            $apc_cache = apc_cache_info();
        }

        if (isset($apc_cache['cache_list']))
        {

            /*
             * Another fallback for different versions
             */
            foreach ($apc_cache['cache_list'] as $var => $value)
            {

                if (!isset($value['key']) AND isset($value['info']))
                {
                    $apc_cache['cache_list'][$var]['key'] = $value['info'];
                }

            }

            foreach ($apc_cache['cache_list'] as $value)
            {

                if (isset($value['key']))
                {

                    /*
                     * Only look at entries for this application
                     */
                    if (mb_substr($value['key'], 0, mb_strlen($this->_prefix)) == $this->_prefix)
                    {
                        $key          = mb_substr($value['key'], mb_strlen($this->_prefix));
                        $result[$key] = ($keys_only ? null : $this->read($key));
                    }

                }

            }

        }

        return $result;

    }


    /**
     * Clear all cache entries
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    public function clearAll()
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('APC cache not initialised');
        }

        $this->prefixClear('');

    }


    /**
     * Run the garbage collector to discard expired cache variables
     */
    public function gc() {}


}