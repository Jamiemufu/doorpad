<?php


namespace Whiskey\Bourbon\Logging\Engine;


use Psr\Log\AbstractLogger;
use Whiskey\Bourbon\Exception\DependencyNotInitialisedException;
use Whiskey\Bourbon\Logging\LoggerInterface;


/**
 * File logging class
 * @package Whiskey\Bourbon\Logging\Logger
 */
class File extends AbstractLogger implements LoggerInterface
{


    protected $_logging_dir = null;


    /**
     * Get the logger name
     * @return string Logger name
     */
    public function getName()
    {

        return 'file';

    }


    /**
     * Check whether the logging directory is writable
     * @return bool Whether the logging directory is writable
     */
    protected function _isActive()
    {

        if (!is_null($this->_logging_dir) AND
            is_dir($this->_logging_dir) AND
            is_writable($this->_logging_dir))
        {
            return true;
        }

        return false;

    }


    /**
     * Set the logging directory
     * @param string $directory Full directory path
     */
    public function setDirectory($directory = '')
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (is_dir($directory) AND
            is_writable($directory))
        {
            $this->_logging_dir = $directory;
        }

    }


    /**
     * Log an error message
     * @param  string $log_level  Logging level
     * @param  string $message    Error message
     * @param  array  $context    Context array
     * @param  array  $additional Array of additional information
     * @throws DependencyNotInitialisedException if the logging directory is not writable
     * @return null
     */
    public function log($log_level = 'info', $message = '', array $context = array(), array $additional = array())
    {

        if (!$this->_isActive())
        {
            throw new DependencyNotInitialisedException('File logger has not been initialised');
        }

        $month = date('Y-m');

        if (!is_dir($this->_logging_dir . $month))
        {
            mkdir($this->_logging_dir . $month);
        }

        if (!is_readable($this->_logging_dir . $month . DIRECTORY_SEPARATOR . 'index.html'))
        {
            file_put_contents($this->_logging_dir . $month . DIRECTORY_SEPARATOR . 'index.html', '');
        }

        $filename         = $this->_logging_dir . $month . DIRECTORY_SEPARATOR . $log_level . '.log';
        $filename_all     = $this->_logging_dir . $month . DIRECTORY_SEPARATOR . 'all.log';
        $compiled_message = $this->_interpolate($message, $context) . "\n\n";

        file_put_contents($filename, $compiled_message, FILE_APPEND | LOCK_EX);
        file_put_contents($filename_all, $compiled_message, FILE_APPEND | LOCK_EX);

    }


    /**
     * Interpolates context values into the message placeholders
     * @param  string $message Message body
     * @param  array  $context Array of strings to interpolate
     * @return string          Interpolated string
     */
    protected function _interpolate($message = '', array $context = [])
    {

        $replace = [];

        foreach ($context as $key => $value)
        {
            $replace['{' . $key . '}'] = $value;
        }

        return strtr($message, $replace);

    }


}