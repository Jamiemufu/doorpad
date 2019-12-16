<?php


namespace Whiskey\Bourbon\Storage\Cache\Engine;


use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Storage\Cache\CacheInterface;
use Memcached as MemcachedLibrary;


/**
 * Memcached cache class
 * @package Whiskey\Bourbon\Storage\Cache\Engine
 */
class Memcached implements CacheInterface
{


    protected $_prefix    = null;
    protected $_memcached = null;
    protected $_username  = null;
    protected $_password  = null;
    protected $_servers   = [];
    protected $_connected = false;


    /**
     * Get the name of the cache storage extension
     * @return string Name of the storage extension
     */
    public function getName()
    {

        return 'memcached';

    }


    /**
     * Initialise the cache
     * @param string $group_name Optional group cache name
     */
    protected function _init($group_name = '')
    {

        if (extension_loaded('memcached') AND
            is_null($this->_prefix))
        {

            $this->_prefix    = 'bourboncache__' . hash('md5', $_SERVER['SERVER_ADDR'] . '__' . __DIR__ . '__' . $group_name) . '___';
            $this->_memcached = new MemcachedLibrary();

            /*
             * SASL authentication
             */
            if (method_exists($this->_memcached, 'setSaslAuthData') AND
                !is_null($this->_username) AND
                !is_null($this->_password))
            {
                $this->_memcached->setOption(MemcachedLibrary::OPT_BINARY_PROTOCOL, true);
                $this->_memcached->setSaslAuthData($this->_username, $this->_password);
            }

            $default_port = 11211;

            /*
             * FALSE until one server successfully connects
             */
            $this->_connected = false;

            if (!empty($this->_servers))
            {

                foreach ($this->_servers as $server)
                {

                    if (isset($server['host']))
                    {

                        $host   = $server['host'];
                        $port   = isset($server['port']) ? (int)$server['port'] : $default_port;
                        $weight = isset($server['weight']) ? (int)$server['weight'] : 50;

                        $memcached_servers = $this->_getServers();

                        $server_already_exists = false;

                        if (is_array($memcached_servers))
                        {

                            foreach ($memcached_servers as $memcached_server)
                            {

                                if ($memcached_server['host'] == $host AND
                                    $memcached_server['port'] == $port)
                                {
                                    $server_already_exists = true;
                                    break;
                                }

                            }

                        }

                        if (!$server_already_exists)
                        {

                            if ($this->_memcached->addServer($host, $port, $weight))
                            {
                                /*
                                 * If one server connects, that's enough to work
                                 * with
                                 */
                                $this->_connected = true;
                            }

                        }

                    }

                }

            }

            /*
             * If no custom servers have been provided, attempt to connect to
             * localhost
             */
            else
            {

                if ($this->_memcached->addServer('127.0.0.1', $default_port))
                {
                    $this->_connected = true;
                }

            }
        }

    }


    /**
     * Check whether the Memcached cache has been successfully initialised
     * @return bool Whether the cache is active
     */
    public function isActive()
    {

        $this->_init();

        if (extension_loaded('memcached') AND
            !is_null($this->_prefix) AND
            !is_null($this->_memcached) AND
            $this->_connected)
        {
            return true;
        }
        
        return false;

    }


    /**
     * Set a cache group
     * @param  string $name Cache group name
     * @return self         Memcached object with group name set
     */
    public function group($name = '')
    {

        $instance = new static();

        $instance->_init($name);

        return $instance;

    }


    /**
     * Get a list of all Memcached servers
     * @return array Array of server details
     * @throws EngineNotInitialisedException if the cache has not been initialised
     */
    protected function _getServers()
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Memcached cache not initialised');
        }
        
        return $this->_memcached->getServerList();

    }


    /**
     * Set credentials and register remote Memcached servers
     * @param string $username Optional Memcached username
     * @param string $password Optional Memcached password
     * @param array  $servers  Optional multidimensional array of remote server details
     */
    public function setCredentials($username = null, $password = null, array $servers = [])
    {

        if (!is_null($username) AND
            !is_null($password))
        {
            $this->_username = $username;
            $this->_password = $password;
        }

        if (!empty($servers))
        {
            $this->_servers = $servers;
        }

        /*
         * Reinitialise all connections so that the above settings can take
         * effect
         */
        $this->_init();

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
            throw new EngineNotInitialisedException('Memcached cache not initialised');
        }

        $key = (string)$key;
        $key = $this->_prefix . $key;

        $result = $this->_memcached->get($key);

        if ($result === false AND
            $this->_memcached->getResultCode() == MemcachedLibrary::RES_NOTFOUND)
        {
            return null;
        }

        if (!$result['ttl'] OR $result['ttl'] >= time())
        {
            return $result['value'];
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
            throw new EngineNotInitialisedException('Memcached cache not initialised');
        }

        if ($key !== null AND (!((string)$key === '')))
        {

            $key = (string)$key;
            $key = $this->_prefix . $key;

            $value =
                [
                    'value' => $value,
                    'ttl'   => ($ttl ? (time() + $ttl) : 0)
                ];

            return $this->_memcached->set($key, $value, $ttl);

        }
        
        return false;

    }


    /**
     * Retrieve a cache variable by its key if it exists, executing and caching
     * the result of a closure if it does not
     * @param  string   $key     Name of cache variable
     * @param  \Closure $closure Closure to execute to obtain value to cache (if required)
     * @param  int      $ttl     Number of seconds after which to discard cached value
     * @return mixed             Cache variable value
     * @throws EngineNotInitialisedException if the cache has not been initialised
     * @throws InvalidArgumentException if the closure is not valid
     */
    public function remember($key = '', \Closure $closure, $ttl = 0)
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Memcached cache not initialised');
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
            throw new EngineNotInitialisedException('Memcached cache not initialised');
        }

        $key = (string)$key;
        $key = $this->_prefix . $key;

        return $this->_memcached->delete($key);

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
            throw new EngineNotInitialisedException('Memcached cache not initialised');
        }

        $key_fragment = (string)$key_fragment;

        $memcached_cache = $this->_getRawData(true);

        foreach ($memcached_cache as $cache_entry_key => $cache_entry_value)
        {

            if (mb_substr($cache_entry_key, 0, mb_strlen($key_fragment)) == $key_fragment)
            {
                $this->clear($cache_entry_key);
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
            throw new EngineNotInitialisedException('Memcached cache not initialised');
        }

        $result          = [];
        $memcached_cache = $this->_memcached->getAllKeys();

        if (is_array($memcached_cache))
        {

            foreach ($memcached_cache as $cache_key_name)
            {

                if (mb_substr($cache_key_name, 0, mb_strlen($this->_prefix)) == $this->_prefix)
                {

                    /*
                     * Strip off the prefix, as we do not need to know about it
                     */
                    $temp_key = mb_substr($cache_key_name, mb_strlen($this->_prefix));
                    
                    $result[$temp_key] = ($keys_only ? null : $this->read($temp_key));

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
            throw new EngineNotInitialisedException('Memcached cache not initialised');
        }

        $this->_memcached->flush();

    }


    /**
     * Run the garbage collector to discard expired cache variables
     */
    public function gc() {}


}