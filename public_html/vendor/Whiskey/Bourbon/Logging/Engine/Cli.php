<?php


namespace Whiskey\Bourbon\Logging\Engine;


use Whiskey\Bourbon\Logging\LoggerInterface;


/**
 * Cli logging class
 * @package Whiskey\Bourbon\Logging\Logger
 */
class Cli extends Widget implements LoggerInterface
{


    /**
     * Get the logger name
     * @return string Logger name
     */
    public function getName()
    {

        return 'cli';

    }


    /**
     * Get a terminal colour code based on a raw log level name
     * @param  string $log_level Raw log level name
     * @return string            Terminal colour code
     */
    protected function _getLevelColour($log_level = '')
    {

        switch ($log_level)
        {

            case 'emergency': return '31'; break;
            case 'alert':     return '31'; break;
            case 'critical':  return '31'; break;
            case 'error':     return '33'; break;
            case 'warning':   return '33'; break;
            case 'notice':    return '36'; break;
            case 'info':      return '36'; break;
            case 'debug':     return '36'; break;

        }

        return '37';

    }


    /**
     * Generate a terminal error message
     * @return string Terminal error message
     */
    public function __toString()
    {

        $errors          = $this->_error_messages;
        $log_level_stats = $this->_getStats();

        /*
         * Remove unused PHP information from the beginning of the array
         */
        array_shift($log_level_stats);

        if (empty($errors))
        {
            return '';
        }

        $variables = compact('errors', 'log_level_stats');

        return $this->_getIceRenderer()->render('cli.ice.php', $variables, true);

    }


}