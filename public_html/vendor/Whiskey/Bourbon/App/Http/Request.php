<?php


namespace Whiskey\Bourbon\App\Http;


use stdClass;
use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Helper\Component\SafeString;
use Whiskey\Bourbon\Helper\Component\UploadedFile;
use Whiskey\Bourbon\Storage\Session;


/**
 * HTTP Request class
 * @package Whiskey\Bourbon\App\Http
 */
class Request
{


    protected $_properties   = null;
    protected $_dependencies = null;


    protected static $_put_variables = [];


    /**
     * Instantiate the Request object and populate with the various request
     * information from the client
     * @param Headers    $headers     Headers object
     * @param CookieJar  $cookie_jar  CookieJar object
     * @param SafeString $safe_string SafeString object
     * @param Session    $session     Session object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Headers $headers, CookieJar $cookie_jar, SafeString $safe_string, Session $session)
    {

        if (!isset($headers) OR
            !isset($cookie_jar) OR
            !isset($safe_string) OR
            !isset($session))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies              = new stdClass();
        $this->_dependencies->safe_string = $safe_string;
        $this->_dependencies->session     = $session;

        /*
         * HTTP properties and user input
         */
        $this->_properties             = new stdClass();
        $this->_properties->method     = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->_properties->protocol   = 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '');
        $this->_properties->domain     = $_SERVER['HTTP_HOST'];
        $this->_properties->url        = $this->_properties->protocol . '://' . $this->_properties->domain . '/' . ltrim($_SERVER['REQUEST_URI'], '/');
        $this->_properties->ip         = $_SERVER['REMOTE_ADDR'];
        $this->_properties->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->_properties->headers    = $headers;
        $this->_properties->get        = $this->_sanitiseArray($_GET);
        $this->_properties->post       = $this->_sanitiseArray($_POST);
        $this->_properties->request    = $this->_sanitiseArray($_REQUEST);
        $put_array                     = $this->_getPutVariables();
        $this->_properties->put        = $this->_sanitiseArray($put_array);
        $this->_properties->files      = $this->_reorderAndPopulateFilesArray();
        $this->_properties->cookies    = $cookie_jar;
        $this->_properties->server     = $this->_sanitiseArray($_SERVER);
        $this->_properties->env        = $this->_sanitiseArray($_ENV);

        /*
         * Headers
         */
        $headers->setAll(getallheaders(), true);

    }


    /**
     * Sanitise an array utilising SafeString::sanitise()
     * @param  array $array Array to sanitise
     * @return array        Sanitised array
     */
    protected function _sanitiseArray(array $array = [])
    {

        $result = $array;

        array_walk_recursive($result, function(&$value, $key)
        {
            $value = $this->_dependencies->safe_string->sanitise($value);
        });

        return $result;

    }


    /**
     * Reorder $_FILES array and populate with UploadedFile objects
     * @return array Reordered $_FILES array with files as UploadedFile objects
     */
    protected function _reorderAndPopulateFilesArray()
    {

        /*
         * Function to reorder $_FILES elements as the array is traversed,
         * tidying the mess that PHP makes of the superglobal
         */
        $files_reorderer = function(array $array = [], $array_key = '', Closure $files_reorderer)
        {

            $result = [];

            foreach ($array as $key => $value)
            {

                if (is_array($value))
                {
                    $result[$key] = $files_reorderer($value, $array_key, $files_reorderer);
                }

                else
                {
                    $result[$key][$array_key] = $value;
                }

            }

            return $result;

        };

        /*
         * Function to identify leaf array nodes and replace them with
         * UploadedFile objects
         */
        $uploaded_file_instantiator = function(array $array = [], Closure $callback)
        {

            foreach ($array as $key => &$value)
            {

                if (isset($value['error']) AND !is_array($value['error']))
                {
                    $value = new UploadedFile($value);
                }

                else
                {
                    $value = $callback($value, $callback);
                }

            }

            return $array;

        };

        /*
         * Leave the original $_FILES array untouched and just create a
         * copy that is reordered
         */
        $files = [];

        /*
         * Traverse the $_FILES array, recursing with $files_reorderer() when
         * encountering a nested array
         */
        foreach ($_FILES as $key => $value)
        {

            if (!isset($files[$key]))
            {
                $files[$key] = [];
            }

            if (isset($value['error']) AND !is_array($value['error']))
            {
                $files[$key] = $value;
            }

            else
            {

                foreach ($value as $sub_key => $sub_value)
                {
                    $files[$key] = array_replace_recursive($files[$key], $files_reorderer($sub_value, $sub_key, $files_reorderer));
                }

            }

        }

        /*
         * Traverse the reordered array, converting leaf array nodes to
         * UploadedFile objects
         */
        $files = $uploaded_file_instantiator($files, $uploaded_file_instantiator);

        return $files;

    }


    /**
     * Parse and return PUT variables from the input stream
     */
    protected function _getPutVariables()
    {

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'PUT' AND
            empty(static::$_put_variables))
        {
            parse_str(file_get_contents('php://input'), static::$_put_variables);
        }

        return static::$_put_variables;

    }


    /**
     * Get an array of any variables stored by the last Response object
     * @return null|array Array of variables (or NULL if none have been set)
     */
    public function getRedirectVariables()
    {

        return $this->_dependencies->session->read('_bourbon_redirect_variables');

    }


    /**
     * Fallback getter for properties, to ensure that the object is essentially
     * immutable
     * @param  string $name Name of property
     * @return mixed        Value of property
     */
    public function __get($name = '')
    {

        return $this->_properties->$name;

    }


    /**
     * Fallback setter, to prevent immutable properties from being overwritten
     * @param string $name  Name of property
     * @param string $value Value of property
     */
    public function __set($name = '', $value = '') {}


}