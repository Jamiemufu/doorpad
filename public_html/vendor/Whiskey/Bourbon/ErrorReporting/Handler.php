<?php


namespace Whiskey\Bourbon\ErrorReporting;


use stdClass;
use Exception;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\Logging\LoggerInterface;
use Whiskey\Bourbon\Logging\Handler as Logger;


/**
 * Error reporting Handler class
 * @package Whiskey\Bourbon\ErrorReporting
 */
class Handler
{


    protected $_dependencies       = null;
    protected $_displayed          = false;
    protected $_include_backtraces = false;


    /**
     * Instantiate the Instance object
     * @param Logger  $logging Logger object
     * @param Bourbon $bourbon Bourbon object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Logger $logging, Bourbon $bourbon)
    {

        if (!isset($logging) OR
            !isset($bourbon))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies          = new stdClass();
        $this->_dependencies->logging = $logging;
        $this->_dependencies->bourbon = $bourbon;

        /*
         * Register handlers for standard error logging
         */
        set_error_handler([$this, 'log']);
        register_shutdown_function([$this, 'errorShutdown']);

    }


    /**
     * Get the logger handler object
     * @return Logger Logger object
     */
    public function getLoggerHandler()
    {

        return $this->_dependencies->logging;

    }


    /**
     * Map an error type to a logger
     * @param string                $type   Error type
     * @param LoggerInterface|array $logger LoggerInterface object (or array of LoggerInterface objects)
     */
    public function mapErrorType($type = 'info', $logger = [])
    {

        if (!is_array($logger))
        {
            $logger = [$logger];
        }

        foreach ($logger as $engine)
        {
            $this->_dependencies->logging->addLogger($engine);
            $this->_dependencies->logging->mapErrorType($type, $engine->getName());
        }

    }


    /**
     * Toggle whether backtraces will be included in error reports
     * @param bool $state Whether backtraces will be included in error reports
     */
    public function includeBacktraces($state = true)
    {

        $this->_include_backtraces = !!$state;

    }


    /**
     * Convert error number to equivalent PHP constant name
     * @param  int    $number Error number
     * @return string         PHP error constant name
     */
    protected function _errorNumberConvert($number = 0)
    {

        switch ($number)
        {

            case E_ERROR:             return 'E_ERROR';
            case E_WARNING:           return 'E_WARNING';
            case E_PARSE:             return 'E_PARSE';
            case E_NOTICE:            return 'E_NOTICE';
            case E_CORE_ERROR:        return 'E_CORE_ERROR';
            case E_CORE_WARNING:      return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:     return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:   return 'E_COMPILE_WARNING';
            case E_USER_ERROR:        return 'E_USER_ERROR';
            case E_USER_WARNING:      return 'E_USER_WARNING';
            case E_USER_NOTICE:       return 'E_USER_NOTICE';
            case E_STRICT:            return 'E_STRICT';
            case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:        return 'E_DEPRECATED';
            case E_USER_DEPRECATED:   return 'E_USER_DEPRECATED';
            case E_ALL:               return 'E_ALL';

            default:                  return 'E_UNKNOWN';

        }

    }


    /**
     * Convert error number to a PSR-3 logging level
     * @param  int    $number Error number
     * @return string         PSR-3 logging level
     */
    protected function _resolveErrorType($number = 0)
    {

        switch ($number)
        {

            case E_ERROR:             return LogLevel::EMERGENCY;
            case E_WARNING:           return LogLevel::ERROR;
            case E_PARSE:             return LogLevel::ERROR;
            case E_NOTICE:            return LogLevel::NOTICE;
            case E_CORE_ERROR:        return LogLevel::EMERGENCY;
            case E_CORE_WARNING:      return LogLevel::ERROR;
            case E_COMPILE_ERROR:     return LogLevel::EMERGENCY;
            case E_COMPILE_WARNING:   return LogLevel::ERROR;
            case E_USER_ERROR:        return LogLevel::EMERGENCY;
            case E_USER_WARNING:      return LogLevel::ERROR;
            case E_USER_NOTICE:       return LogLevel::NOTICE;
            case E_STRICT:            return LogLevel::WARNING;
            case E_RECOVERABLE_ERROR: return LogLevel::EMERGENCY;
            case E_DEPRECATED:        return LogLevel::WARNING;
            case E_USER_DEPRECATED:   return LogLevel::WARNING;
            case E_ALL:               return LogLevel::DEBUG;

            default:                  return LogLevel::CRITICAL;

        }

    }


    /**
     * Custom error logging method for development mode
     * @param  int     $number  Error code/number
     * @param  string  $string  Error message
     * @param  string  $file    Name of file error occurred in
     * @param  int     $line    Line error occurred on
     * @param  array   $context Active variable array
     */
    public function log($number = 0, $string = '', $file = '', $line = 0, array $context = [])
    {

        $number = (int)$number;
        $string = (string)$string;
        $file   = (string)$file;
        $line   = (int)$line;

        /*
         * Ignore suppressed errors
         */
        if (error_reporting())
        {

            /*
             * Get the backtrace if required
             */
            $backtrace = ($this->_include_backtraces) ? $this->_dependencies->logging->getBacktrace() : [];

            /*
             * Get the affected line
             */
            $affected_line = '';

            if (is_readable($file))
            {
                $affected_line = file($file);
                $affected_line = (string)$affected_line[($line - 1)];
            }

            /*
             * Compile additional information
             */
            $error_code = $this->_errorNumberConvert($number);

            $additional =
                [
                    '$_REQUEST'     => $_REQUEST,
                    '$_SERVER'      => $_SERVER,
                    'error_code'    => $error_code,
                    'error_string'  => $string,
                    'file'          => $file,
                    'line_number'   => $line,
                    'affected_line' => $affected_line,
                    'backtrace'     => $backtrace,
                    'context'       => $context
                ];

            $message = '[{date}] {error_code}:  {error_string} in {file_path} on line {line_number}';

            $message_context =
                [
                    'error_code'   => $error_code,
                    'line_number'  => $line,
                    'file_path'    => $file,
                    'error_string' => $string,
                    'date'         => date('d-m-Y H:i:s e')
                ];

            $log_level = $this->_resolveErrorType($number);

            /*
             * Dispatch the above to the error logger(s)
             */
            $this->_dependencies->logging->log($log_level, $message, $message_context, $additional);

        }

    }


    /**
     * Return HTML-formatted representation of errors
     * @return string HTML block detailing errors
     */
    public function getWidget()
    {

        $this->_displayed = true;

        try
        {

            if ($this->_dependencies->bourbon->runningFromCli())
            {
                return $this->_dependencies->logging->getLogger('cli');
            }

            else
            {
                return $this->_dependencies->logging->getLogger('widget');
            }

        }

        catch (Exception $exception)
        {
            return '';
        }

    }


    /**
     * Inspect for fatal errors on shutdown and output error widget if necessary
     */
    public function errorShutdown()
    {

        $last_error      = error_get_last();
        $error_type      = $last_error['type'];
        $fatal_error     = false;
        $ajax_connection = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
                            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

        /*
         * These error types won't be caught by the error handler, so need to be
         * logged manually
         */
        if ($error_type == E_ERROR OR
            $error_type == E_PARSE OR
            $error_type == E_CORE_ERROR OR
            $error_type == E_CORE_WARNING OR
            $error_type == E_COMPILE_ERROR OR
            $error_type == E_COMPILE_WARNING)
        {

            $this->log($last_error['type'],
                       $last_error['message'],
                       $last_error['file'],
                       $last_error['line']);

                       $fatal_error = true;

        }

        /*
         * Output the error widget if there are errors to report, unless
         * accessing with AJAX
         */
        if (($fatal_error OR !$this->_displayed) AND !$ajax_connection)
        {
            echo $this->getWidget();
        }

    }


}