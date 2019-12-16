<?php


namespace Whiskey\Bourbon\Server;


use Exception;


/**
 * Server Cpu class
 * @package Whiskey\Bourbon\Server
 */
class Cpu
{


    protected $_info  = null;
    protected $_cores = null;


    /**
     * Get CPU model names
     * @return array Array of CPU model names
     */
    protected function _names()
    {

        if (!is_null($this->_info))
        {
            return $this->_info;
        }

        $this->_info = [];

        if (is_readable('/proc/cpuinfo'))
        {

            $cpu_info = file('/proc/cpuinfo');
            
            foreach ($cpu_info as $value)
            {

                if (mb_substr($value, 0, 10) == 'model name')
                {
                    $cpu_info_array = explode(':', $value);
                    $this->_info[]  = trim($cpu_info_array[1]);
                }

            }

        }

        return $this->_info;

    }
  

    /**
     * Get number of CPU cores
     * @return int Number of CPU cores
     */
    protected function _cores()
    {

        if (!is_null($this->_cores))
        {
            return $this->_cores;
        }
    
        try
        {
            $this->_cores = (int)exec('cat /proc/cpuinfo | grep processor | wc -l');
        }
        
        catch (Exception $exception)
        {
            $this->_cores = 0;
        }

        return $this->_cores;

    }

  
    /**
     * Get CPU figure
     * @param  string $figure Type of figure to return
     * @return int            CPU figure
     */
    public function figure($figure = 'user')
    {

        if (is_readable('/proc/stat'))
        {

            $result = file_get_contents('/proc/stat');
            $result = trim(ltrim($result, 'cpu  '));
            $result = explode(' ', $result);

            if ($figure == 'user')
            {
                return (int)trim($result[0]);
            }

            else if ($figure == 'nice')
            {
                return (int)trim($result[1]);
            }

            else if ($figure == 'system')
            {
                return (int)trim($result[2]);
            }

            else if ($figure == 'idle')
            {
                return (int)trim($result[3]);
            }

        }

        return 0;

    }

  
    /**
     * Get CPU load average
     * @param  int   $period Period over which to get figure (1, 5 or 15)
     * @return float         Load average
     */
    public function load($period = 5)
    {

        try
        {
            $cpu = sys_getloadavg();
        }

        catch (Exception $exception)
        {
            return 0;
        }

        if ($period == 5)
        {
            return $cpu[1];
        }

        else if ($period == 15)
        {
            return $cpu[2];
        }

        else
        {
            return $cpu[0];
        }

    }


    /**
     * Get a dynamic Cpu property
     * @param  string $name Name of property to get
     * @return mixed        Property value
     */
    public function __get($name = '')
    {

        switch ($name)
        {

            case 'names':
                return $this->_names();
                break;

            case 'cores':
                return $this->_cores();
                break;

        }

        return null;

    }


}