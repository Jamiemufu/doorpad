<?php


namespace Whiskey\Bourbon\Storage\Meta\Engine;


use stdClass;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Storage\CorruptedDataException;
use Whiskey\Bourbon\Storage\DataStorageInterface;
use Whiskey\Bourbon\Storage\Meta\MetaInterface;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;


/**
 * Database meta storage class
 * @package Whiskey\Bourbon\Storage\Meta\Engine
 */
class Database implements MetaInterface, DataStorageInterface
{


    protected $_dependencies = null;
    protected $_table        = '_bourbon_meta';


    /**
     * Instantiate the Database instance
     * @param Db $db Db object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Db $db)
    {

        if (!isset($db))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies     = new stdClass();
        $this->_dependencies->db = $db;

        $this->_init();

    }


    /**
     * Initialise the meta storage engine
     */
    protected function _init()
    {

        if ($this->isActive())
        {

            /*
             * See if the meta table exists
             */
            $table_name  = $this->_dependencies->db->escape($this->_table);
            $table_check = $this->_dependencies->db->raw('SHOW TABLES LIKE \'' . $table_name . '\'');

            foreach ($table_check as $value)
            {
                foreach ($value as $value_2)
                {
                    if ($value_2 == $this->_table)
                    {
                        return;
                    }
                }
            }

            /*
             * If it doesn't, create it
             */
            $this->_dependencies->db->buildSchema()->table($this->_table)
                                                   ->autoId()
                                                   ->varChar('key', '')
                                                   ->text('value')
                                                   ->create();

        }

    }


    /**
     * Check whether the engine has been successfully initialised
     * @return bool Whether the engine is active
     */
    public function isActive()
    {

        return (!is_null($this->_table) AND $this->_dependencies->db->connected());

    }


    /**
     * Get the name of the meta storage engine
     * @return string Name of the meta storage engine
     */
    public function getName()
    {

        return 'database';

    }


    /**
     * Read a meta value
     * @param  string $key Key of meta storage item
     * @return mixed       Value of meta storage item (or NULL if it does not exist)
     * @throws EngineNotInitialisedException if meta storage table could not be accessed
     * @throws CorruptedDataException if the data has become corrupted in storage
     */
    public function read($key = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed');
        }

        try
        {

            $value = $this->_dependencies->db->getBy($this->_table, 'key', $key, 'value');

            /*
             * If the value doesn't exist
             */
            if (is_null($value))
            {
                return null;
            }

            /*
             * Otherwise try to decode it
             */
            if (($value = unserialize(base64_decode($value))) !== false)
            {
                return $value;
            }

            else
            {
                throw new CorruptedDataException('Corrupted meta storage data');
            }

        }

        catch (Exception $exception)
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed', 0, $exception);
        }

    }


    /**
     * Write a meta value
     * @param  string $key   Key of meta storage item
     * @param  mixed  $value Value to write
     * @return bool          Whether the meta value was successfully stored
     * @throws EngineNotInitialisedException if meta storage table could not be accessed
     */
    public function write($key = '', $value = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed');
        }

        try
        {

            $value = base64_encode(serialize($value));

            $exists = $this->_dependencies->db->build()->table($this->_table)
                                                       ->where('key', $key)
                                                       ->exists();

            $query = $this->_dependencies->db->build()->table($this->_table)
                                                      ->data('value', $value);

            if ($exists)
            {
                $query->where('key', $key)->update();
            }

            else
            {
                $query->data('key', $key)->insert();
            }

            return true;

        }

        catch (Exception $exception)
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed', 0, $exception);
        }

    }


    /**
     * Clear a meta value
     * @param  string $key Key of meta storage item
     * @return bool        Whether the meta value was successfully cleared
     * @throws EngineNotInitialisedException if meta storage table could not be accessed
     */
    public function clear($key = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed');
        }

        try
        {

            $this->_dependencies->db->build()->table($this->_table)
                                             ->where('key', $key)
                                             ->delete();

            return true;

        }

        catch (Exception $exception)
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed', 0, $exception);
        }

    }


    /**
     * Clear meta values whose keys begin with a certain string
     * @param  string $key_fragment Initial fragment of key name
     * @return bool                 Whether the meta values were successfully cleared
     * @throws EngineNotInitialisedException if meta storage table could not be accessed
     */
    public function prefixClear($key_fragment = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed');
        }

        try
        {

            $db     = $this->_dependencies->db;
            $values = [$db->likeEscape($key_fragment)];

            $db->raw('DELETE FROM `' . $db->escape($this->_table) . '` WHERE `key` LIKE CONCAT(?, \'%\')', $values);

            return true;

        }

        catch (Exception $exception)
        {
            throw new EngineNotInitialisedException('Meta storage table could not be accessed', 0, $exception);
        }

    }


}