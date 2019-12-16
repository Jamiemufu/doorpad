<?php


namespace Whiskey\Bourbon\App\Http;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Routing\Handler as Router;
use Whiskey\Bourbon\Storage\Session;


/**
 * HTTP Response class
 * @package Whiskey\Bourbon\App\Http
 */
class Response
{


    public $headers = null;
    public $body    = '';


    protected $_dependencies = null;


    /**
     * Instantiate the Response object and populate with preexisting headers
     * @param Headers $headers Headers object
     * @param Router  $router  Router object
     * @param Session $session Session object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Headers $headers, Router $router, Session $session)
    {

        if (!isset($headers) OR
            !isset($router) OR
            !isset($session))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        /*
         * Store dependencies
         */
        $this->_dependencies          = new stdClass();
        $this->_dependencies->router  = $router;
        $this->_dependencies->session = $session;

        /*
         * Store all headers that have already been set by PHP
         */
        $this->headers    = $headers;
        $existing_headers = [];

        foreach (headers_list() as $header)
        {

            $header = $headers->getComponents($header);
            $name   = $header->name;
            $value  = $header->value;

            $existing_headers[$name] = $value;

            /*
             * Remove the header so it is not sent twice
             */
            header_remove($name);

        }

        $headers->setAll($existing_headers);

    }


    /**
     * Store an array of variables to be set for the next rendered template
     * @param  array $array Array of variables to store
     * @return self         Response object
     */
    public function with(array $array = [])
    {

        $existing_variables = $this->_dependencies->session->read('_bourbon_redirect_variables');

        if (!is_array($existing_variables))
        {
            $existing_variables = [];
        }

        $array = array_merge($existing_variables, $array);

        $this->_dependencies->session->write('_bourbon_redirect_variables', $array);

        return $this;

    }


    /**
     * Set a content type header
     * @param string $type     Content type key
     * @param string $filename Optional filename
     */
    public function setContentType($type = '', $filename = '')
    {

        $type = strtolower($type);

        $content_types =
            [
                'text'       => 'text/plain',
                'txt'        => 'text/plain',
                'html'       => 'text/html',
                'xhtml'      => 'application/xhtml+xml',
                'xml'        => 'application/xml',
                'rss'        => 'application/rss+xml',
                'atom'       => 'application/atom+xml',
                'json'       => 'application/json',
                'soap'       => 'application/soap+xml',
                'csv'        => 'text/csv',
                'javascript' => 'application/javascript',
                'js'         => 'application/javascript',
                'css'        => 'text/css',
                'pdf'        => 'application/pdf',
                'gif'        => 'image/gif',
                'jpeg'       => 'image/jpeg',
                'jpg'        => 'image/jpeg',
                'jpe'        => 'image/jpeg',
                'png'        => 'image/png',
                'svg'        => 'image/svg+xml',
                'zip'        => 'application/zip',
                'gzip'       => 'application/gzip',
                'binary'     => 'application/octet-stream',
                'download'   => 'application/octet-stream'
            ];

        if (isset($content_types[$type]))
        {

            $this->headers->set('Content-Type: ' . $content_types[$type]);

            if ($type == 'download')
            {

                $this->headers->set('Content-Transfer-Encoding: binary');
                $this->headers->set('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                $this->headers->set('Cache-Control: post-check=0, pre-check=0', false);
                $this->headers->set('Pragma: no-cache');

                if ($filename != '')
                {
                    $this->headers->set('Content-Disposition: attachment; filename="' . $filename . '"');
                }

            }

        }

        else
        {
            $this->headers->set('Content-Type: ' . $type);
        }

    }


    /**
     * Issue an immediate redirect header
     * @param string ... Multiple strings representing fully-qualified controller class, action name and slugs, or single string representing external URL
     */
    public function redirect()
    {

        $arguments = func_get_args();

        /*
         * Actual URL to redirect to
         */
        if (count($arguments) == 1)
        {
            $redirect_url = reset($arguments);
        }

        /*
         * Route to redirect to
         */
        else
        {
            $redirect_url = call_user_func_array([$this->_dependencies->router, 'generateUrl'], $arguments);
        }

        header('Location: ' . $redirect_url, true, 302);

        exit;

    }


    /**
     * Issue an immediate 401, 403 or 451 header
     * @param int  $code HTTP error code
     * @param bool $exit Whether to exit the script
     */
    public function deny($code = 403, $exit = true)
    {

        if ($code == 401)
        {
            $this->abort($code, 'Unauthorized', $exit);
        }

        else if ($code == 403)
        {
            $this->abort($code, 'Forbidden', $exit);
        }
        
        else if ($code == 451)
        {
            $this->abort($code, 'Unavailable For Legal Reasons', $exit);
        }

    }


    /**
     * Issue an immediate 404 header
     * @param bool $exit Whether to exit the script
     */
    public function notFound($exit = true)
    {

        $this->abort(404, 'Not Found', $exit);

    }


    /**
     * Issue an immediate 4XX header
     * @param int    $code    HTTP error code
     * @param string $message HTTP error message
     * @param bool   $exit    Whether to exit the script
     */
    public function abort($code = 404, $message = '', $exit = true)
    {

        $code  = (string)$code;
        $codes = ['400' => 'Bad Request', '401' => 'Unauthorized', '402' => 'Payment Required', '403' => 'Forbidden', '404' => 'Not Found',
                  '405' => 'Method Not Allowed', '406' => 'Not Acceptable', '407' => 'Proxy Authentication Required', '408' => 'Request Timeout', '409' => 'Conflict',
                  '410' => 'Gone', '411' => 'Length Required', '412' => 'Precondition Failed', '413' => 'Request Entity Too Large', '414' => 'Request-URI Too Long',
                  '415' => 'Unsupported Media Type', '416' => 'Requested Range Not Satisfiable', '417' => 'Expectation Failed', '418' => 'I\'m a teapot',
                  '451' => 'Unavailable For Legal Reasons'];

        if (isset($codes[$code]))
        {

            if (empty($message))
            {
                $message = $codes[$code];
            }

            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $message);

            if ($exit)
            {
                exit;
            }

        }

    }


    /**
     * Issue an immediate 500 header
     * @param bool $exit Whether to exit the script
     */
    public function fatalError($exit = true)
    {

        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');

        if ($exit)
        {
            exit;
        }

    }


    /**
     * Output all headers and body to the client
     */
    public function output()
    {

        $this->headers->output();

        echo $this->body;

    }


}