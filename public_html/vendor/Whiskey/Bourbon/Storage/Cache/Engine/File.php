<?php

namespace Whiskey\Bourbon\Storage\Cache\Engine;


use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Storage\CorruptedDataException;
use Whiskey\Bourbon\Storage\Cache\CacheInterface;


/**
 * File cache class
 * @package Whiskey\Bourbon\Storage\Cache\Engine
 */
class File implements CacheInterface
{


    protected $_prefix     = null;
    protected $_cache_dir  = null;
    protected $_hashes_dir = null;


    /**
     * Run the garbage collector when the script ends
     */
    public function __destruct()
    {

        $this->gc();

    }


    /**
     * Set the cache directory
     * @param  string $directory Path to cache directory
     * @return bool              Whether the cache directory was successfully set
     */
    public function setDirectory($directory = null)
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_null($directory) AND
            is_readable($directory) AND
            is_writable($directory))
        {

            $this->_cache_dir  = $directory;
            $this->_hashes_dir = $this->_cache_dir . DIRECTORY_SEPARATOR . 'hashes' . DIRECTORY_SEPARATOR;

            return true;

        }

        return false;

    }


    /**
     * Get the name of the cache storage extension
     * @return string Name of the storage extension
     */
    public function getName()
    {
    
        return 'file';

    }


    /**
     * Initialise the cache
     * @param string $group_name Optional group cache name
     */
    protected function _init($group_name = '')
    {

        if (is_null($this->_prefix))
        {
            $this->_prefix = 'bourboncache__' . md5($group_name) . '___';
        }

        /*
         * Set up the 'hashes' subdirectory
         */
        if (!is_null($this->_cache_dir) AND
            is_readable($this->_cache_dir) AND
            is_writable($this->_cache_dir) AND
            !file_exists($this->_hashes_dir))
        {
            mkdir($this->_hashes_dir);
            file_put_contents($this->_hashes_dir . 'index.html', '');
        }

    }


    /**
     * Check whether the File cache has been successfully initialised
     * @return bool Whether the cache is active
     */
    public function isActive()
    {

        $this->_init();
        
        if (!is_null($this->_prefix) AND
            !is_null($this->_cache_dir) AND
            is_readable($this->_cache_dir) AND
            is_writable($this->_cache_dir))
        {
            return true;
        }
        
        return false;

    }


    /**
     * Set a cache group
     * @param  string $name Cache group name
     * @return self         File object with group name set
     */
    public function group($name = '')
    {

        $instance = new static();

        $instance->setDirectory($this->_cache_dir);
        $instance->_init($name);

        return $instance;

    }


    /**
     * Calculate the subdirectory for a cache entry key
     * @param  string $key Cache entry key
     * @return string      Key subdirectory
     */
    protected function _calculateSubdirectory($key = '')
    {

        if (substr($key, -4) == '.php')
        {
            $key = substr($key, 0, -4);
        }

        return substr($key, -2) . DIRECTORY_SEPARATOR;

    }


    /**
     * Sanitise a key name
     * @param  string $key Key name
     * @return string      Sanitised key name
     */
    protected function _sanitiseKeyName($key = '')
    {

        $key          = rtrim(strtr(base64_encode((string)$key), '+/', '-_'), '=');
        $hash         = hash('sha512', $key);
        $subdirectory = rtrim($this->_calculateSubdirectory($hash), DIRECTORY_SEPARATOR);

        if (!file_exists($this->_hashes_dir . $subdirectory))
        {
            mkdir($this->_hashes_dir . $subdirectory);
            file_put_contents($this->_hashes_dir . $subdirectory . DIRECTORY_SEPARATOR . 'index.html', '');
        }

        file_put_contents($this->_hashes_dir . $subdirectory . DIRECTORY_SEPARATOR . $hash, $key);

        return $hash;

    }


    /**
     * Unsanitise a key name
     * @param  string $key Key name
     * @return string      Unsanitised key name
     */
    protected function _unsanitiseKeyName($key = '')
    {

        $key = @file_get_contents($this->_hashes_dir . $this->_calculateSubdirectory($key) . $key);

        if ($key === false)
        {
            return '';
        }

        return base64_decode(str_pad(strtr($key, '-_', '+/'), (mb_strlen($key) % 4), '=', STR_PAD_RIGHT));

    }


    /**
     * Read and load a cache file
     * @param  string $filename Name of the cache file
     * @return object           Contents of the cache file
     * @throws CorruptedDataException if the cache file is not valid
     */
    protected function _readCacheFile($filename = '')
    {

        if (is_readable($this->_cache_dir . $this->_calculateSubdirectory($filename) . $filename . '.php'))
        {

            $value = file($this->_cache_dir . $this->_calculateSubdirectory($filename) . $filename . '.php');

            if (count($value) === 4)
            {

                $value = $value[2];
                $value = mb_substr($value, 2);

                if (($value = unserialize(base64_decode($value))) !== false)
                {
                    return $value;
                }

            }

        }

        throw new CorruptedDataException('Invalid cache file, ' . $filename);

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
            throw new EngineNotInitialisedException('File cache not initialised');
        }

        $key = $this->_sanitiseKeyName($key);
        $key = $this->_prefix . $key;

        if (is_readable($this->_cache_dir . $this->_calculateSubdirectory($key) . $key . '.php'))
        {

            $value = $this->_readCacheFile($key);

            if ($value['expires'] >= time())
            {
                return $value['value'];
            }

            else
            {
                $this->clear($key);
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
            throw new EngineNotInitialisedException('File cache not initialised');
        }

        if ($key !== null AND (!((string)$key === '')))
        {

            $key     = $this->_sanitiseKeyName($key);
            $key     = $this->_prefix . $key;
            $expires = ($ttl ? (time() + $ttl) : PHP_INT_MAX);
            
            $value        = "<?php\n//" . $expires . "\n//" . base64_encode(serialize(['value' => $value, 'expires' => $expires])) . "\n?>";
            $subdirectory = rtrim($this->_calculateSubdirectory($key), DIRECTORY_SEPARATOR);

            if (!file_exists($this->_cache_dir . $subdirectory))
            {
                mkdir($this->_cache_dir . $subdirectory);
                file_put_contents($this->_cache_dir . $subdirectory . DIRECTORY_SEPARATOR . 'index.html', '');
            }

            $result = file_put_contents($this->_cache_dir . $subdirectory . DIRECTORY_SEPARATOR . $key . '.php', $value);
            
            return ($result === false) ? false : true;

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
            throw new EngineNotInitialisedException('File cache not initialised');
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
            throw new EngineNotInitialisedException('File cache not initialised');
        }

        $key = $this->_sanitiseKeyName($key);

        @unlink($this->_hashes_dir . $this->_calculateSubdirectory($key) . $key);

        $key = $this->_prefix . $key;

        if (is_readable($this->_cache_dir . $this->_calculateSubdirectory($key) . $key . '.php'))
        {
            return @unlink($this->_cache_dir . $this->_calculateSubdirectory($key) . $key . '.php');
        }

        /*
         * If the entry doesn't exist, return true
         */
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
            throw new EngineNotInitialisedException('File cache not initialised');
        }

        $cache  = $this->_getRawData(true);
        $result = true;

        foreach ($cache as $key => $value)
        {

            if (mb_substr($key, 0, mb_strlen($key_fragment)) == $key_fragment)
            {

                if (!$this->clear($key))
                {
                    $result = false;
                }

            }

        }

        return $result;
    
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
            throw new EngineNotInitialisedException('File cache not initialised');
        }

        $cache  = glob($this->_cache_dir . '*' . DIRECTORY_SEPARATOR . '*.php');
        $result = [];

        foreach ($cache as $cache_filename)
        {

            $cache_filename = substr($cache_filename, (mb_strlen($this->_cache_dir) + 3));

            if (mb_substr($cache_filename, 0, mb_strlen($this->_prefix)) == $this->_prefix)
            {

                $key          = mb_substr($cache_filename, mb_strlen($this->_prefix), (0 - mb_strlen('.php')));
                $original_key = $key;
                $key          = $this->_unsanitiseKeyName($key);

                /*
                 * If a lookup for the key does not exist, remove the entry
                 */
                if ($key == '')
                {
                    @unlink($this->_cache_dir . $this->_calculateSubdirectory($original_key) . $cache_filename);
                }

                else
                {
                    $result[$key] = ($keys_only ? null : $this->read($key));
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
            throw new EngineNotInitialisedException('File cache not initialised');
        }

        $cache_files = glob($this->_cache_dir . '*' . DIRECTORY_SEPARATOR . '*');
        $hash_files  = glob($this->_hashes_dir . '*' . DIRECTORY_SEPARATOR . '*');

        $files = array_merge($cache_files, $hash_files);

        foreach ($files as $file)
        {
            if (basename($file) != 'index.html')
            {
                @unlink($file);
            }
        }

    }


    /**
     * Run the garbage collector to discard expired cache variables
     */
    public function gc()
    {

        $cache    = glob($this->_cache_dir . '*' . DIRECTORY_SEPARATOR . '*');
        $excluded = ['.', '..', 'index.html'];

        foreach ($cache as $cache_filename)
        {

            $cache_filename = substr($cache_filename, (mb_strlen($this->_cache_dir) + 3));

            if (!in_array($cache_filename, $excluded) AND
                mb_substr($cache_filename, 0, mb_strlen($this->_prefix)) == $this->_prefix)
            {

                /*
                 * Use the second line of the file to determine the expiry date
                 */
                $calculated_filename = $this->_cache_dir . $this->_calculateSubdirectory($cache_filename) . $cache_filename;

                if (is_readable($calculated_filename))
                {

                    $handle = fopen($calculated_filename, 'r');

                    fseek($handle, 8);

                    $expires = trim(fgets($handle));

                    if ($expires < time())
                    {

                        $key = mb_substr($cache_filename, mb_strlen($this->_prefix), (0 - mb_strlen('.php')));
                        $key = $this->_unsanitiseKeyName($key);

                        /*
                         * If we don't have a lookup for the key, remove the entry
                         */
                        if ($key == '')
                        {
                            @unlink($calculated_filename);
                        }

                        else
                        {
                            $this->clear($key);
                        }

                    }

                }

            }

        }
    
    }


}