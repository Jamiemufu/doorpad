<?php


namespace Whiskey\Bourbon\Storage;


/**
 * DataStorageInterface interface
 * @package Whiskey\Bourbon\Storage
 */
interface DataStorageInterface
{


    /**
     * Retrieve a stored variable
     * @param  string $key Name of variable
     * @return mixed       Variable value
     */
    public function read($key);


    /**
     * Store a variable
     * @param  string $key   Name of variable
     * @param  mixed  $value Variable value
     * @return bool          Whether the write was successful
     */
    public function write($key, $value);


    /**
     * Unset a variable
     * @param  string $key Name of variable
     * @return bool        Whether the variable was successfully cleared
     */
    public function clear($key);


    /**
     * Unset variables that begin with a certain string
     * @param  string $key_fragment Initial fragment of key name
     * @return bool                 Whether the variables were successfully cleared
     */
    public function prefixClear($key_fragment);


}