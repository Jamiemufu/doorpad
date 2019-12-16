<?php


namespace Whiskey\Bourbon\Helper;


use Whiskey\Bourbon\Exception\Helper\TempFile\InvalidPassthroughMethodException;
use Whiskey\Bourbon\Exception\Helper\TempFile\TemporaryDirectoryWriteException;


/**
 * TempFile class
 * @package Whiskey\Bourbon\Helper
 */
class TempFile
{


    const _NO_REUSE = true;


    protected $filename = null;
    protected $handle   = null;


    /**
     * Instantiate a TempFile object and create a temporary file on disk
     * @throws TemporaryDirectoryWriteException if a temporary directory cannot be found
     */
    public function __construct()
    {

        $temp_dir = sys_get_temp_dir();

        if (!is_readable($temp_dir) OR
            !is_dir($temp_dir) OR
            !is_writable($temp_dir))
        {
            throw new TemporaryDirectoryWriteException('No temporary directory available');
        }

        $this->filename = tempnam(sys_get_temp_dir(), '_bourbon_tmp_');
        $this->handle   = fopen($this->filename, 'w+');

    }


    /**
     * Delete the temporary file when no longer needed
     */
    public function __destruct()
    {

        if (!is_null($this->handle))
        {
            fclose($this->handle);
        }

        if (!is_null($this->filename) AND
            is_readable($this->filename))
        {
            @unlink($this->filename);
        }

    }


    /**
     * Get the absolute filename of the temporary file
     * @return string Absolute filename of temporary file
     */
    public function getPath()
    {

        return $this->filename;

    }


    /**
     * Get the resource handle of the temporary file
     * @return resource Resource handle of temporary file
     */
    public function getHandle()
    {

        return $this->handle;

    }


    /**
     * @param  string $name      Name of function to call
     * @param  array  $arguments Arguments to pass
     * @return mixed             Return result from passthrough function
     * @throws InvalidPassthroughMethodException if the requested function is not supported
     */
    public function __call($name = '', array $arguments = [])
    {

        $methods = ['eof', 'flush', 'getc', 'getcsv', 'gets', 'getss',
                    'lock', 'passthru', 'putcsv', 'puts', 'read', 'scanf',
                    'seek', 'stat', 'tell', 'truncate', 'write', 'rewind'];

        $name = strtolower($name);

        array_unshift($arguments, $this->handle);

        if (in_array($name, $methods))
        {

            if ($name != 'rewind')
            {
                $name = 'f' . $name;
            }

            return call_user_func_array($name, $arguments);

        }

        throw new InvalidPassthroughMethodException('Invalid passthrough file method \'' . $name . '\'');

    }


}