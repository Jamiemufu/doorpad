<?php


namespace Whiskey\Bourbon\Logging\Engine;


use Psr\Log\AbstractLogger;
use Whiskey\Bourbon\Logging\LoggerInterface;
use Whiskey\Bourbon\Templating\Engine\Ice\Renderer as Ice;


/**
 * Widget logging class
 * @package Whiskey\Bourbon\Logging\Logger
 */
class Widget extends AbstractLogger implements LoggerInterface
{


    protected $_error_messages  = [];
    protected $_log_level_count = [];


    /**
     * Get the logger name
     * @return string Logger name
     */
    public function getName()
    {

        return 'widget';

    }


    /**
     * Log an error message
     * @param  string $log_level  Logging level
     * @param  string $message    Error message
     * @param  array  $context    Context array
     * @param  array  $additional Array of additional information
     * @return null
     */
    public function log($log_level = 'info', $message = '', array $context = [], array $additional = [])
    {

        if (isset($this->_log_level_count[$log_level]))
        {
            $this->_log_level_count[$log_level]++;
        }

        else
        {
            $this->_log_level_count[$log_level] = 1;
        }

        $message = $this->_interpolate($message, $context);

        /*
         * Override the full error message with the error string if available
         */
        if (isset($additional['error_string']))
        {
            $message = $this->_interpolate($additional['error_string'], $context);
        }

        $this->_error_messages[] = compact('log_level',
                                           'message',
                                           'additional');

    }


    /**
     * Convert the raw log level name to a friendly name
     * @param  string $log_level Raw log level name
     * @return string            Friendly log level name
     */
    protected function _getFriendlyLevelName($log_level = '')
    {

        switch ($log_level)
        {

            case 'emergency': return 'Emergency'; break;
            case 'alert':     return 'Alert'; break;
            case 'critical':  return 'Critical'; break;
            case 'error':     return 'Error'; break;
            case 'warning':   return 'Warning'; break;
            case 'notice':    return 'Notice'; break;
            case 'info':      return 'Info'; break;
            case 'debug':     return 'Debug'; break;

        }

        return 'Unknown';

    }


    /**
     * Get a HTML colour code based on a raw log level name
     * @param  string $log_level Raw log level name
     * @return string            HTML colour code
     */
    protected function _getLevelColour($log_level = '')
    {

        switch ($log_level)
        {

            case 'emergency': return '#c9302c'; break;
            case 'alert':     return '#c9302c'; break;
            case 'critical':  return '#c9302c'; break;
            case 'error':     return '#ec971f'; break;
            case 'warning':   return '#ec971f'; break;
            case 'notice':    return '#31b0d5'; break;
            case 'info':      return '#31b0d5'; break;
            case 'debug':     return '#31b0d5'; break;

        }

        return '#666666';

    }


    /**
     * Get log stats
     * @return array Array of log stats
     */
    protected function _getStats()
    {

        $result = [];

        foreach ($this->_log_level_count as $log_level => $value)
        {

            $log_level_name = $this->_getFriendlyLevelName($log_level);

            $result[] =
                [
                    'value'  => $log_level_name . ' (' . number_format($value) . ')',
                    'colour' => $this->_getLevelColour($log_level)
                ];

        }

        $result[] =
            [
                'value'  => 'PHP ' . PHP_VERSION,
                'colour' => '#666666'
            ];

        $result = array_reverse($result);

        return $result;

    }


    /**
     * Get an Ice renderer
     * @return Ice Ice object
     */
    protected function _getIceRenderer()
    {

        $template_dir = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        $ice          = new Ice();

        $ice->addBaseDirectory($template_dir);

        return $ice;

    }


    /**
     * Generate a HTML error widget
     * @return string HTML error widget
     */
    public function __toString()
    {

        $errors = $this->_error_messages;

        $allowed_additional =
            [
                '$_REQUEST',
                '$_SERVER',
                'backtrace'
            ];

        $log_level_stats = $this->_getStats();

        if (empty($errors))
        {
            return '';
        }

        $variables = compact('errors', 'allowed_additional', 'log_level_stats');

        return $this->_getIceRenderer()->render('widget.ice.php', $variables, true);

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