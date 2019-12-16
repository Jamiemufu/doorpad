<?php


namespace Whiskey\Bourbon\Server;


/**
 * Server Memory class
 * @package Whiskey\Bourbon\Server
 */
class Memory
{


    /**
     * Get memory information
     * @return array Array of memory information
     */
    protected function _getInfo()
    {

        if (is_readable('/proc/meminfo'))
        {
        
            $info   = file('/proc/meminfo');
            $result = [];
            
            foreach ($info as $value)
            {

                $entry        = explode(':', $value);
                $value        = ((int)trim(mb_substr($entry[1], 0, -2)) * 1024);
                $key          = trim($entry[0]);
                $result[$key] = $value;

            }

            return $result;
        
        }
        
        return [];

    }

  
    /**
     * Get total memory
     * @return int Total size of memory, in bytes
     */
    protected function _total()
    {

        $info = $this->_getInfo();
        
        return $info ? $info['MemTotal'] : 0;

    }


    /**
     * Get free memory
     * @return int Size of free memory, in bytes
     */
    protected function _free()
    {

        $info = $this->_getInfo();
        
        return $info ? ($info['MemFree'] + $info['Buffers'] + $info['Cached']) : 0;

    }


    /**
     * Get used memory
     * @return int Size of used memory, in bytes
     */
    protected function _used()
    {

        return ($this->_total() - $this->_free());

    }


    /**
     * Get a dynamic Memory property
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

        }

        return null;

    }


}