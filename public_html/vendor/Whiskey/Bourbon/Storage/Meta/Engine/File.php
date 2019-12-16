<?php


namespace Whiskey\Bourbon\Storage\Meta\Engine;


use stdClass;
use Exception;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Storage\CorruptedDataException;
use Whiskey\Bourbon\Storage\DataStorageInterface;
use Whiskey\Bourbon\Storage\Meta\MetaInterface;


/**
 * File meta storage class
 * @package Whiskey\Bourbon\Storage\Meta\Engine
 */
class File implements MetaInterface, DataStorageInterface
{


    protected $_file = null;
    protected $_meta = null;


    /**
     * Instantiate the File instance
     */
    public function __construct()
    {

        $this->_meta = new stdClass();

    }


    /**
     * Initialise the meta storage engine
     */
    protected function _init()
    {

        if (!is_null($this->_file) AND
            !is_readable($this->_file))
        {
            $this->_save();
        }

        $this->_open();

    }


    /**
     * Check whether the engine has been successfully initialised
     * @return bool Whether the engine is active
     */
    public function isActive()
    {

        return (!is_null($this->_file) AND
                is_readable($this->_file) AND
                is_writable($this->_file));

    }


    /**
     * Get the name of the meta storage engine
     * @return string Name of the meta storage engine
     */
    public function getName()
    {

        return 'file';

    }


    /**
     * Open the meta file
     * @throws EngineNotInitialisedException if meta storage file could not be accessed or was not valid
     * @throws CorruptedDataException if the data has become corrupted in storage
     */
    protected function _open()
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage file could not be accessed');
        }

        $file = file($this->_file);

        if (count($file) === 3)
        {

            $file = $file[1];
            $file = mb_substr($file, 2);

            if (($file = unserialize(base64_decode($file))) !== false)
            {
                $this->_meta = $file;
            }

            else
            {
                throw new CorruptedDataException('Corrupted meta storage file');
            }

        }

        else
        {
            throw new CorruptedDataException('Invalid meta storage file');
        }

    }


    /**
     * Save the meta file
     * @throws EngineNotInitialisedException if meta storage file could not be accessed
     */
    protected function _save()
    {

        if (is_null($this->_file))
        {
            throw new EngineNotInitialisedException('Meta storage file could not be accessed');
        }

        $value  = "<?php\n//" . base64_encode(serialize($this->_meta)) . "\n?>";
        $result = file_put_contents($this->_file, $value);

        if ($result === false)
        {
            throw new EngineNotInitialisedException('Meta storage file could not be accessed');
        }

    }


    /**
     * Set the path to the storage file
     * @param string $path Full path to storage file
     */
    public function setPath($path = '')
    {

        $dir  = dirname($path);

        if ($path != '' AND
            is_readable($dir) AND
            is_writable($dir))
        {

            $this->_file = $path;

            $this->_init();

        }

    }


    /**
     * Read a meta value
     * @param  string $key Key of meta storage item
     * @return mixed       Value of meta storage item (or NULL if it does not exist)
     * @throws EngineNotInitialisedException if meta storage file could not be accessed
     */
    public function read($key = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage file could not be accessed');
        }

        if (isset($this->_meta->$key))
        {
            return $this->_meta->$key;
        }

        return null;

    }


    /**
     * Write a meta value
     * @param  string $key   Key of meta storage item
     * @param  mixed  $value Value to write
     * @return bool          Whether the meta value was successfully stored
     * @throws EngineNotInitialisedException if meta storage file could not be accessed
     */
    public function write($key = '', $value = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage file could not be accessed');
        }

        try
        {

            $this->_open();

            /*
             * Return TRUE if the value already matches
             */
            if ($this->read($key) === $value)
            {
                return true;
            }

            $this->_meta->$key = $value;
            $this->_save();

            return true;

        }

        catch (Exception $exception)
        {
            return false;
        }

    }


    /**
     * Clear a meta value
     * @param  string $key Key of meta storage item
     * @return bool        Whether the meta value was successfully cleared
     * @throws EngineNotInitialisedException if meta storage file could not be accessed
     */
    public function clear($key = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage file could not be accessed');
        }

        try
        {

            $this->_open();

            unset($this->_meta->$key);

            $this->_save();

            return true;

        }

        catch (Exception $exception)
        {
            return false;
        }

    }


    /**
     * Clear meta values whose keys begin with a certain string
     * @param  string $key_fragment Initial fragment of key name
     * @return bool                 Whether the meta values were successfully cleared
     * @throws EngineNotInitialisedException if meta storage file could not be accessed
     */
    public function prefixClear($key_fragment = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage file could not be accessed');
        }

        try
        {

            $this->_open();

            foreach ($this->_meta as $var => $value)
            {

                if (mb_substr($var, 0, mb_strlen($key_fragment)) == $key_fragment)
                {
                    unset($this->_meta->$var);
                }

            }

            $this->_save();

            return true;

        }

        catch (Exception $exception)
        {
            return false;
        }

    }


}