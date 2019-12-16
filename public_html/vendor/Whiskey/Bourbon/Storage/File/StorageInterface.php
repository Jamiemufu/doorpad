<?php

namespace Whiskey\Bourbon\Storage\File;


use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;


/**
 * StorageInterface interface
 * @package Whiskey\Bourbon\Storage\File
 */
interface StorageInterface
{


    /**
     * Check whether the engine has been successfully initialised
     * @return bool Whether the engine is active
     */
    public function isActive();


    /**
     * Get the name of the storage engine
     * @return string Name of the storage engine
     */
    public function getName();


    /**
     * Put a file into storage
     * @param  string           $source   Path to source file
     * @param  string           $filename Filename to use for saved file
     * @param  bool             $raw_data Whether raw data is being passed
     * @return StorageFile|bool           StorageFile object of new file or FALSE on fail
     * @throws EngineNotInitialisedException if the store has not been initialised
     * @throws InvalidArgumentException if a source file was not specified
     * @throws InvalidArgumentException if the destination name is not valid
     */
    public function put($source, $filename, $raw_data);


    /**
     * Get a StorageFile object
     * @param  string      $id File ID
     * @return StorageFile     StorageFile object
     * @throws EngineNotInitialisedException if the store has not been initialised
     * @throws InvalidArgumentException if the file ID is not valid
     */
    public function get($id);


    /**
     * Get details of all files in the store
     * @return array Array of StorageFile objects
     * @throws EngineNotInitialisedException if the store has not been initialised
     */
    public function getAll();


    /**
     * Get a list of all groups
     * @return array Array of group names
     */
    public function getGroups();


    /**
     * Output the file to the browser
     * @param StorageFile $file StorageFile object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function output(StorageFile $file);


    /**
     * Get the file contents
     * @param  StorageFile $file StorageFile object
     * @return string|bool       File contents or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function getContent(StorageFile $file);


    /**
     * Get the group name
     * @param  StorageFile $file StorageFile object
     * @return string            Group name
     */
    public function getGroup(StorageFile $file);


    /**
     * Check whether the file [still] exists on disk
     * @param  StorageFile $file StorageFile object
     * @return bool              Whether the file exists
     */
    public function exists(StorageFile $file);


    /**
     * Delete the file
     * @param  StorageFile $file StorageFile object
     * @return bool              Whether the file was successfully deleted
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function delete(StorageFile $file);


    /**
     * Copy the file
     * @param  StorageFile      $file   StorageFile object
     * @param  bool             $shared Whether the copied file will be shared
     * @param  string           $group  Group the copied file will belong to
     * @return StorageFile|bool         StorageFile object of copied file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function copyTo(StorageFile $file, $shared, $group);


    /**
     * Duplicate the file within the same group
     * @param  StorageFile      $file StorageFile object
     * @return StorageFile|bool       StorageFile object of duplicated file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function duplicate(StorageFile $file);


    /**
     * Move the file
     * @param  StorageFile      $file   StorageFile object
     * @param  bool             $shared Whether the moved file will be shared
     * @param  string           $group  Group the moved file will belong to
     * @return StorageFile|bool         StorageFile object of moved file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function moveTo(StorageFile $file, $shared, $group);


    /**
     * Rename the file
     * @param  StorageFile      $file     StorageFile object
     * @param  string           $filename New filename
     * @return StorageFile|bool           StorageFile object of renamed file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     * @throws InvalidArgumentException if the destination filename is not valid
     */
    public function rename(StorageFile $file, $filename);


}