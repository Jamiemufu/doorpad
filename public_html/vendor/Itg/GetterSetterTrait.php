<?php


namespace Itg;


use Exception;
use Whiskey\Bourbon\App\Facade\Db;


/**
 * GetterSetterTrait trait
 * @package Itg
 */
trait GetterSetterTrait
{


    protected $_property_table = '';
    protected $_primary_key    = '';
    protected $_record_id      = '';
    protected $_property_array = [];


    /**
     * Populate $_property_array with a database record
     * @param  string    $table       Table name
     * @param  string    $primary_key Name of primary key
     * @param  string    $id          ID of record to retrieve
     * @throws Exception if arguments are missing or query could not be performed
     */
    public function _populateFromDatabase($table = '', $primary_key = '', $id = '')
    {

        $error_message = 'Could not instantiate \'' . __CLASS__ . '\' object';

        if ($table == '' OR $primary_key == '' OR $id == '')
        {
            throw new Exception($error_message);
        }

        $this->_property_table = $table;
        $this->_primary_key    = $primary_key;
        $this->_record_id      = $id;

        try
        {

            $record = Db::build()->table($table)
                                 ->where($primary_key, $id)
                                 ->select();

            if (is_array($record) AND isset($record[$id][$primary_key]))
            {
                $this->_property_array = reset($record);
            }

            else
            {
                throw new Exception($error_message);
            }

        }

        catch (Exception $exception)
        {
            throw new Exception($error_message);
        }

    }


    /**
     * Take care of getter and setter calls
     * @param  string $name      Name of called method
     * @param  array  $arguments Method arguments
     * @return mixed             Property value or setter success
     * @throws Exception if property does not exist or could not be set
     */
    public function __call($name, array $arguments = [])
    {

        $action   = substr($name, 0, 3);
        $property = substr($name, 3);
        $property = lcfirst($property);
        $property = preg_replace_callback('#\B[A-Z]#', function($matches)
        {
            return '_' . strtolower($matches[0]);
        }, $property);

        if (!isset($this->_property_array[$property]))
        {
            throw new Exception('Property \'' . $property . '\' does not exist');
        }

        /*
         * Getter
         */
        if ($action == 'get')
        {
            return $this->_property_array[$property];
        }

        /*
         * Setter
         */
        else if ($action == 'set')
        {

            $value = reset($arguments);

            try
            {

                Db::build()->table($this->_property_table)
                           ->where($this->_primary_key, $this->_record_id)
                           ->data($property, $value)
                           ->update();

                $this->_property_array[$property] = $value;

                return true;

            }

            catch (Exception $exception)
            {
                return false;
            }

        }

        return null;

    }


    /**
     * Delete the record
     * @return bool Whether the record was successfully deleted
     */
    public function delete()
    {

        try
        {

            Db::build()->table($this->_property_table)
                       ->where($this->_primary_key, $this->_record_id)
                       ->delete();

            return true;

        }

        catch (Exception $exception)
        {
            return false;
        }

    }


    /**
     * Check whether the object has been linked to a database entry
     * @return bool Whether the object has been linked
     */
    public function _isLinked()
    {

        if ($this->_property_table == '' OR
            $this->_primary_key    == '' OR
            $this->_record_id      == '')
        {
            return false;
        }

        return true;

    }


    /**
     * Get the property table name
     * @return string Property table name
     */
    public function _getPropertyTable()
    {

        return $this->_property_table;

    }


    /**
     * Get the primary key name
     * @return string Primary key name
     */
    public function _getPrimaryKey()
    {

        return $this->_primary_key;

    }


    /**
     * Get the record ID
     * @return string Record ID
     */
    public function _getRecordId()
    {

        return $this->_record_id;

    }


}