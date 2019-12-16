<?php


namespace Itg\SaveTheDate\Component;


use InvalidArgumentException;
use Itg\SaveTheDate\Exception\InvalidRecordException;
use Whiskey\Bourbon\App\Facade\Db;


/**
 * Template class
 * @package Itg\SaveTheDate\Component
 */
class Template
{


    protected static $_table = '';


    protected $_record = [];


    /**
     * Get a record
     * @param int $id Record ID
     * @throws InvalidRecordException if the record could not be found
     */
    public function __construct($id = 0)
    {

        $this->_setUp();

        $result = Db::build()->table(static::$_table)->where('id', $id)->select();

        if (isset($result[$id]['id']) AND $result[$id]['id'] == $id)
        {
            $this->_record = $result[$id];
        }

        else
        {
            throw new InvalidRecordException('Invalid ID');
        }

    }


    /**
     * Set up any dependencies of the object, such as a database table
     */
    protected function _setUp() {}


    /**
     * Get a field from the record
     * @param  string $name Field name
     * @return mixed        Field value
     */
    public function __get($name = '')
    {

        if (isset($this->_record[$name]))
        {
            return $this->_record[$name];
        }

        throw new InvalidArgumentException('Invalid field \'' . $name . '\'');

    }


    /**
     * Update a field on the record
     * @param string $name  Field name
     * @param string $value Field value
     */
    public function __set($name = '', $value = '')
    {

        if (!isset($this->_record[$name]))
        {
            throw new InvalidArgumentException('Invalid field \'' . $name . '\'');
        }

        if (strtolower($name) == 'id')
        {
            throw new InvalidArgumentException('Cannot update the record ID');
        }

        Db::build()->table(static::$_table)->where('id', $this->_record['id'])->data($name, $value)->update();

        $this->_record[$name] = $value;

    }


    /**
     * Delete the record
     */
    public function delete()
    {

        Db::build()->table(static::$_table)->where('id', $this->_record['id'])->delete();

    }


}