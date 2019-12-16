<?php


namespace Whiskey\Bourbon\Server;


use Whiskey\Bourbon\Exception\Server\InvalidDiskException;


/**
 * Server Disk class
 * @package Whiskey\Bourbon\Server
 */
class Disk
{


    protected $_disk  = '/';


    /**
     * Instantiate a server Disk object
     * @param string $disk Path to disk root
     * @throws InvalidDiskException if the target disk/directory is not readable
     */
    public function __construct($disk = '/')
    {

        if (!is_readable($disk))
        {
            throw new InvalidDiskException('Target disk (or directory) not readable');
        }

        $this->_disk = $disk;

    }


    /**
     * Get the disk path
     * @return string Path to disk
     */
    protected function _path()
    {

        return $this->_disk;

    }


    /**
     * Get total disk space
     * @return int Total size of disk, in bytes
     */
    protected function _total()
    {

        return disk_total_space($this->_disk);

    }

  
    /**
     * Get free disk space
     * @return int Size of free disk space, in bytes
     */
    protected function _free()
    {

        return disk_free_space($this->_disk);

    }

  
    /**
     * Get used disk space
     * @return int Size of used disk space, in bytes
     */
    protected function _used()
    {

        return ($this->_total() - $this->_free());

    }


    /**
     * Get a dynamic Disk property
     * @param  string $name Name of property to get
     * @return mixed        Property value
     */
    public function __get($name = '')
    {

        switch ($name)
        {

            case 'total':
                return $this->_total();
                break;

            case 'free':
                return $this->_free();
                break;

            case 'used':
                return $this->_used();
                break;

            case 'path':
                return $this->_path();
                break;

        }

        return null;

    }


}