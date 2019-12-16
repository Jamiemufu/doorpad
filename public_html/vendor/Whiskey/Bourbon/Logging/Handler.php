<?php


namespace Whiskey\Bourbon\Logging;


use Closure;
use stdClass;
use InvalidArgumentException;
use Psr\Log\NullLogger;
use Whiskey\Bourbon\Exception\Logging\InvalidListenerException;


class Handler
{


    protected $_mappings     = [];
    protected $_listeners    = [];
    protected $_dependencies = null;


    /**
     * Instantiate the logging Handler object
     * @param NullLogger $null_logger NullLogger class
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(NullLogger $null_logger)
    {

        if (!isset($null_logger))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies              = new stdClass();
        $this->_dependencies->loggers     = new stdClass();
        $this->_dependencies->null_logger = $null_logger;

    }


    /**
     * Map an error type to a logger
     * @param string       $type   Error type
     * @param string|array $logger Name of logger to utilise
     */
    public function mapErrorType($type = '', $logger = '')
    {

        $type = strtolower($type);

        if (!is_array($logger))
        {
            $logger = [$logger];
        }

        foreach ($logger as $logger_name)
        {
            $this->_mappings[$type][] = strtolower($logger_name);
        }

    }


    /**
     * Add a logger
     * @param LoggerInterface $logger Logger instance
     */
    public function addLogger(LoggerInterface $logger)
    {

        $name = strtolower($logger->getName());

        $this->_dependencies->loggers->$name = $logger;

    }


    /**
     * Resolve an error type to an array of loggers
     * @param  string $type Error type
     * @return array        Array of logger instances implementing LoggerInterface
     */
    public function resolveLogger($type = '')
    {

        $result = [];
        $type   = strtolower($type);

        /*
         * Get the logger from the type
         */
        if (isset($this->_mappings[$type]))
        {

            $loggers = $this->_mappings[$type];

            foreach ($loggers as $logger)
            {

                /*
                 * Return the logger if it exists
                 */
                if (isset($this->_dependencies->loggers->$logger))
                {
                    $result[] = $this->_dependencies->loggers->$logger;
                }

            }

        }

        /*
         * If no appropriate logger is available, fall back to a null logger
         */
        if (empty($result))
        {
            $result[] = $this->_dependencies->null_logger;
        }

        return $result;

    }


    /**
     * Get a logger by name
     * @param  string          $name Logger name
     * @return LoggerInterface       Logger instance
     * @throws InvalidArgumentException if the requested logger does not exist
     */
    public function getLogger($name = '')
    {

        $name = strtolower($name);

        /*
         * Return the logger if it exists
         */
        if (isset($this->_dependencies->loggers->$name))
        {
            return $this->_dependencies->loggers->$name;
        }

        throw new InvalidArgumentException('Logger \'' . $name . '\' does not exist or has not been registered');

    }


    /**
     * Log an error message
     * @param string $log_level  Logging level
     * @param string $message    Error message
     * @param array  $context    Context array
     * @param array  $additional Array of additional information
     */
    public function log($log_level = 'info', $message = '', array $context = [], array $additional = [])
    {

        $loggers = $this->resolveLogger($log_level);

        foreach ($loggers as $logger)
        {
            $logger->log($log_level, $message, $context, $additional);
        }

        $this->_broadcast($log_level, $message, $context, $additional);

    }


    /**
     * Generate a backtrace
     * @return array Backtrace
     */
    public function getBacktrace()
    {

        ob_start();
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $backtrace = ob_get_clean();
        $backtrace = explode("\n", $backtrace);

        /*
         * Strip out empty lines
         */
        foreach ($backtrace as $key => &$value)
        {

            $value = trim($value);

            if ($value == '')
            {
                unset($backtrace[$key]);
            }

        }

        return $backtrace;

    }


    /**
     * Trigger a log message
     * @param string $message   Error message
     * @param string $log_level Logging level
     * @param array  $context   Context array
     */
    public function trigger($message = '', $log_level = 'info', array $context = [])
    {

        $backtrace = debug_backtrace();

        /*
         * Decide which part of the backtrace to look at (to skip past calls to
         * the logging handler and debug_backtrace())
         */
        $backtrace_entry = 3;

        $file          = '';
        $line          = '';
        $affected_line = '';

        /*
         * Get the affected file, line number and actual line string
         */
        if (isset($backtrace[$backtrace_entry]['file']))
        {

            $file = $backtrace[$backtrace_entry]['file'];
            $line = $backtrace[$backtrace_entry]['line'];

            if (is_readable($file))
            {
                $affected_line = file($file);
                $affected_line = (string)$affected_line[($line - 1)];
            }

        }

        $additional = ['$_REQUEST'    => $_REQUEST,
                       '$_SERVER'     => $_SERVER,
                       'error_code'    => ucwords(strtolower($log_level)),
                       'error_string'  => $message,
                       'file'          => $file,
                       'line_number'   => $line,
                       'affected_line' => $affected_line,
                       'backtrace'     => $this->getBacktrace(),
                       'context'       => []];

        $message .= "\nStack trace:\n" . implode("\n", $this->getBacktrace()) . "\n  thrown";

        $this->log($log_level, $message, $context, $additional);

    }


    /**
     * Add an error listener
     * @param Closure $closure Error listener closure
     * @throws InvalidListenerException if the listener closure is not valid
     */
    public function addListener(Closure $closure)
    {

        if (!(is_object($closure) AND ($closure instanceof Closure)))
        {
            throw new InvalidListenerException('Invalid error listener closure');
        }

        $this->_listeners[] = $closure;

    }


    /**
     * Broadcast a log message
     * @param string $log_level  Logging level
     * @param string $message    Error message
     * @param array  $context    Context array
     * @param array  $additional Array of additional information
     */
    protected function _broadcast($log_level = 'info', $message = '', array $context = [], array $additional = [])
    {

        foreach ($this->_listeners as $listener)
        {
            $listener($log_level, $message, $context, $additional);
        }

    }


}