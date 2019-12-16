<?php


namespace Whiskey\Bourbon\Logging\Engine;


use Psr\Log\AbstractLogger;
use Whiskey\Bourbon\Logging\LoggerInterface;


/**
 * BlackHole logging class
 * @package Whiskey\Bourbon\Logging\Logger
 */
class BlackHole extends AbstractLogger implements LoggerInterface
{


    /**
     * Get the logger name
     * @return string Logger name
     */
    public function getName()
    {

        return 'blackhole';

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

        /*
         * Silently drop error log
         */

    }


}