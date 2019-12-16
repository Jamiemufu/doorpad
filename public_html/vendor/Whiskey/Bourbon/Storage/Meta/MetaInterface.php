<?php


namespace Whiskey\Bourbon\Storage\Meta;


use Whiskey\Bourbon\Exception\EngineNotInitialisedException;


/**
 * MetaInterface interface
 * @package Whiskey\Bourbon\Storage\Meta
 */
interface MetaInterface
{


    /**
     * Check whether the engine has been successfully initialised
     * @return bool Whether the engine is active
     */
    public function isActive();


    /**
     * Get the name of the meta storage engine
     * @return string Name of the meta storage engine
     */
    public function getName();


    /**
     * Read a meta value
     * @param  string $key Key of meta storage item
     * @return mixed       Value of meta storage item (or NULL if it does not exist)
     * @throws EngineNotInitialisedException if the meta storage engine has not been initialised
     */
    public function read($key);


    /**
     * Write a meta value
     * @param  string $key   Key of meta storage item
     * @param  mixed  $value Value to write
     * @return bool          Whether the meta value was successfully stored
     * @throws EngineNotInitialisedException if the meta storage engine has not been initialised
     */
    public function write($key, $value);


    /**
     * Clear a meta value
     * @param  string $key Key of meta storage item
     * @return bool        Whether the meta value was successfully cleared
     * @throws EngineNotInitialisedException if the meta storage engine has not been initialised
     */
    public function clear($key);


    /**
     * Clear meta values whose keys begin with a certain string
     * @param  string $key_fragment Initial fragment of key name
     * @return bool                 Whether the meta values were successfully cleared
     * @throws EngineNotInitialisedException if the meta storage engine has not been initialised
     */
    public function prefixClear($key_fragment);


}