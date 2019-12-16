<?php


namespace Whiskey\Bourbon\Server;


use InvalidArgumentException;


/**
 * Server Info class
 * @package Whiskey\Bourbon\Server
 */
class Info
{


    protected $_disk   = null;
    protected $_memory = null;
    protected $_cpu    = null;
    
    
    protected $_who_am_i = null;


    /**
     * Instantiate a server Info object
     * @param Disk   $disk   Disk object
     * @param Memory $memory Memory object
     * @param Cpu    $cpu    Cpu object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Disk $disk, Memory $memory, Cpu $cpu)
    {
        
        if (!isset($disk) OR
            !isset($memory) OR
            !isset($cpu))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_disk   = $disk;
        $this->_memory = $memory;
        $this->_cpu    = $cpu;

    }


    /**
     * Get Disk object
     * @return Disk Disk object
     */
    public function disk()
    {

        return $this->_disk;

    }


    /**
     * Get Memory object
     * @return Memory Memory object
     */
    public function memory()
    {

        return $this->_memory;

    }


    /**
     * Get Cpu object
     * @return Cpu Cpu object
     */
    public function cpu()
    {

        return $this->_cpu;

    }


    /**
     * Find out the Unix user
     * @return string Unix user name
     */
    public function whoAmI()
    {

        if (!is_null($this->_who_am_i))
        {
            return $this->_who_am_i;
        }

        $this->_who_am_i = trim(shell_exec('whoami'));

        return $this->_who_am_i;

    }


}