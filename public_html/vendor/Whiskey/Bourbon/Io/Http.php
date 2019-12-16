<?php


namespace Whiskey\Bourbon\Io;


use InvalidArgumentException;
use CURLFile;
use Whiskey\Bourbon\Exception\MissingDependencyException;


/**
 * Http class
 * @package Whiskey\Bourbon\Io
 */
class Http
{


    protected $_certificate_bundle_path = '';
    protected $_builder_url             = '';
    protected $_builder_headers         = [];
    protected $_builder_params          = [];


    /**
     * Return a fresh Http object to build
     * @return Http Http object
     */
    public function build()
    {

        return clone $this;

    }


    /**
     * Clear the builder configuration upon cloning
     */
    public function __clone()
    {

        $this->_builder_url     = '';
        $this->_builder_headers = [];
        $this->_builder_params  = [];

    }


    /**
     * Set the builder request URL
     * @param  string $url Request URL
     * @return Http        Http object for chaining
     * @throws InvalidArgumentException if a URL is not provided
     */
    public function setUrl($url = '')
    {

        if ($url == '')
        {
            throw new InvalidArgumentException('URL not provided');
        }

        $this->_builder_url = $url;

        return $this;

    }


    /**
     * Add a header
     * @param  string $value Header value
     * @return Http          Http object for chaining
     * @throws InvalidArgumentException if the header value is not provided
     */
    public function addHeader($value = '')
    {

        /*
         * Array of headers
         */
        if (is_array($value))
        {

            foreach ($value as $header_value)
            {
                $this->addHeader($header_value);
            }

        }

        /*
         * Individual header
         */
        else
        {

            if ($value == '')
            {
                throw new InvalidArgumentException('Header value not provided');
            }

            $this->_builder_headers[] = $value;

        }

        return $this;

    }


    /**
     * Add a parameter
     * @param  string $key   Parameter name
     * @param  string $value Parameter value
     * @return Http          Http object for chaining
     * @throws InvalidArgumentException if the parameter name is not provided
     */
    public function addParameter($key = '', $value = '')
    {

        /*
         * Array of parameters
         */
        if (is_array($key))
        {

            foreach ($key as $param_name => $param_value)
            {
                $this->addParameter($param_name, $param_value);
            }

        }

        /*
         * Individual parameter
         */
        else
        {

            if ($key == '')
            {
                throw new InvalidArgumentException('Parameter name not provided');
            }

            $this->_builder_params[$key] = $value;

        }

        return $this;

    }


    /**
     * Add a file
     * @param  string $key  Parameter name
     * @param  string $path File path
     * @return Http         Http object for chaining
     * @throws InvalidArgumentException if the parameter name is not provided
     * @throws InvalidArgumentException if the file cannot be read
     */
    public function addFile($key = '', $path = '')
    {

        /*
         * Array of files
         */
        if (is_array($key))
        {

            foreach ($key as $param_name => $file_path)
            {
                $this->addFile($param_name, $file_path);
            }

        }

        /*
         * Individual file
         */
        else
        {

            if ($key == '')
            {
                throw new InvalidArgumentException('Parameter name not provided');
            }

            if ($path == '' OR !is_readable($path))
            {
                throw new InvalidArgumentException('Invalid file path');
            }

            $this->_builder_params[$key] = new CURLFile($path);

        }

        return $this;

    }


    /**
     * Set the path to the root certificate bundle
     * @param string $path File path
     */
    public function setCertificateBundlePath($path = '')
    {

        if (is_readable($path))
        {
            $this->_certificate_bundle_path = $path;
        }

    }


    /**
     * Get the default cURL resource
     * @param  string   $url     Target URL
     * @param  array    $headers Header array
     * @return resource          cURL resource
     */
    protected function _getDefaultCurl($url = '', array $headers = [])
    {

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL,            $url);
        curl_setopt($handle, CURLOPT_SAFE_UPLOAD,    true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_AUTOREFERER,    true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);

        if (!empty($headers))
        {
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        }

        /*
         * Fall back to Mozilla root CA bundle if necessary
         */
        if (mb_strlen(ini_get('curl.cainfo')) === 0)
        {
            curl_setopt($handle, CURLOPT_CAINFO, $this->_certificate_bundle_path);
        }

        return $handle;

    }


    /**
     * Make a POST request with cURL
     * @param  string $url     URL to request
     * @param  array  $params  Array of POST values
     * @param  array  $headers Array of optional headers
     * @return string          Response from server
     * @throws MissingDependencyException if cURL is missing
     */
    public function post($url = '', array $params = [], array $headers = [])
    {

        if (!extension_loaded('curl'))
        {
            throw new MissingDependencyException('cURL extension missing');
        }

        /*
         * Set up builder requests
         */
        if ($this->_builder_url != '')
        {
            $url     = $this->_builder_url;
            $params  = $this->_builder_params;
            $headers = $this->_builder_headers;
        }

        /*
         * Make the request
         */
        $handle = $this->_getDefaultCurl($url, $headers);

        curl_setopt($handle, CURLOPT_POST,       true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $params);

        $result = curl_exec($handle);

        curl_close($handle);

        return $result;

    }


    /**
     * Make a GET request with cURL
     * @param  string $url     URL to request
     * @param  array  $headers Array of optional headers
     * @return string          Response from server
     * @throws MissingDependencyException if cURL is missing
     */
    public function get($url = '', array $headers = [])
    {

        if (!extension_loaded('curl'))
        {
            throw new MissingDependencyException('cURL extension missing');
        }

        /*
         * Set up builder requests
         */
        if ($this->_builder_url != '')
        {
            $url     = $this->_builder_url;
            $headers = $this->_builder_headers;
        }

        /*
         * Make the request
         */
        $handle = $this->_getDefaultCurl($url, $headers);
        $result = curl_exec($handle);
        
        curl_close($handle);
        
        return $result;

    }


    /**
     * Make a PUT request with cURL
     * @param  string $url     URL to request
     * @param  array  $params  Array of PUT values
     * @param  array  $headers Array of optional headers
     * @return string          Response from server
     * @throws MissingDependencyException if cURL is missing
     */
    public function put($url = '', array $params = [], array $headers = [])
    {

        if (!extension_loaded('curl'))
        {
            throw new MissingDependencyException('cURL extension missing');
        }

        /*
         * Set up builder requests
         */
        if ($this->_builder_url != '')
        {
            $url     = $this->_builder_url;
            $params  = $this->_builder_params;
            $headers = $this->_builder_headers;
        }

        /*
         * Make the request
         */
        $headers = array_merge($headers, ['Content-Type: application/x-www-form-urlencoded']);
        $handle  = $this->_getDefaultCurl($url, $headers);

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($handle, CURLOPT_PUT,           true);
        
        /*
         * Build the parameters into a query string and store it in a temporary
         * file
         */
        $param_string    = http_build_query($params);
        $param_temp_file = tmpfile();
        
        fwrite($param_temp_file, $param_string);
        fseek($param_temp_file, 0);
        
        /*
         * Include the parameters as a 'file'
         */
        curl_setopt($handle, CURLOPT_INFILE,     $param_temp_file);
        curl_setopt($handle, CURLOPT_INFILESIZE, mb_strlen($param_string));

        $result = curl_exec($handle);
        
        curl_close($handle);

        /*
         * Ensure that the temporary parameter file is deleted
         */
        if (!empty($params))
        {
            fclose($param_temp_file);
        }
        
        return $result;

    }


    /**
     * Make a DELETE request with cURL
     * @param  string $url     URL to request
     * @param  array  $headers Array of optional headers
     * @return string          Response from server
     * @throws MissingDependencyException if cURL is missing
     */
    public function delete($url = '', array $headers = [])
    {

        if (!extension_loaded('curl'))
        {
            throw new MissingDependencyException('cURL extension missing');
        }

        /*
         * Set up builder requests
         */
        if ($this->_builder_url != '')
        {
            $url     = $this->_builder_url;
            $headers = $this->_builder_headers;
        }

        /*
         * Make the request
         */
        $handle = $this->_getDefaultCurl($url, $headers);

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $result = curl_exec($handle);
        
        curl_close($handle);
        
        return $result;

    }


}