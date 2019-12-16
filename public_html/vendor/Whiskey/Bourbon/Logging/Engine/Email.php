<?php


namespace Whiskey\Bourbon\Logging\Engine;


use stdClass;
use InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Whiskey\Bourbon\Logging\LoggerInterface;
use Whiskey\Bourbon\Email\Handler as EmailHandler;


/**
 * Email logging class
 * @package Whiskey\Bourbon\Logging\Engine
 */
class Email extends AbstractLogger implements LoggerInterface
{


    protected $_dependencies = null;
    protected $_to_address   = null;
    protected $_smtp         = null;


    /**
     * Instantiate the Email logger object
     * @param EmailHandler $email Email object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(EmailHandler $email)
    {

        if (!isset($email))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies        = new stdClass();
        $this->_dependencies->email = $email;

    }


    /**
     * Get the logger name
     * @return string Logger name
     */
    public function getName()
    {

        return 'email';

    }


    /**
     * Provide SMTP login credentials
     * @param bool   $ssl      Whether to use an SSL connection
     * @param string $server   Server address
     * @param int    $port     Server SMTP port
     * @param string $username Email account username
     * @param string $password Email account password
     */
    public function smtp($ssl = false, $server = '', $port = 25, $username = '', $password = '')
    {

        $this->_smtp = ['ssl'      => (bool)$ssl,
                        'server'   => (string)$server,
                        'port'     => (int)$port,
                        'username' => (string)$username,
                        'password' => (string)$password];

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

        if (!is_null($this->_to_address))
        {

            $site_url         = 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($_SERVER['REQUEST_URI'], '/');
            $compiled_message = $this->_interpolate($message, $context);

            $email = $this->_dependencies->email->create();

            if (!is_null($this->_smtp))
            {

                $email->smtp($this->_smtp['ssl'],
                             $this->_smtp['server'],
                             $this->_smtp['port'],
                             $this->_smtp['username'],
                             $this->_smtp['password']);

            }

            $email->to($this->_to_address)
                  ->from($this->_to_address)
                  ->subject('Error Report from ' . $site_url)
                  ->body($compiled_message)
                  ->send();

        }

    }


    /**
     * Set the 'to' address to e-mail error logs to
     * @param string $to_address E-mail address to send error log to
     */
    public function setToAddress($to_address = '')
    {

        if (filter_var($to_address, FILTER_VALIDATE_EMAIL))
        {
            $this->_to_address = $to_address;
        }

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