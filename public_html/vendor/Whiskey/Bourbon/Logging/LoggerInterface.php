<?php


namespace Whiskey\Bourbon\Logging;


interface LoggerInterface
{


    /**
     * Get the logger name
     * @return string Logger name
     */
    public function getName();


    /**
     * Log an error message
     * @param string $log_level  Logging level
     * @param string $message    Error message
     * @param array  $context    Context array
     * @param array  $additional Array of additional information
     */
    public function log($log_level, $message, array $context, array $additional);


}