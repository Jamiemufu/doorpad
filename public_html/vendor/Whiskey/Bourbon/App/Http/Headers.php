<?php


namespace Whiskey\Bourbon\App\Http;


use stdClass;
use Whiskey\Bourbon\Exception\App\Http\ImmutableHeadersException;


/**
 * HTTP Headers class
 * @package Whiskey\Bourbon\App\Http
 */
class Headers
{


    const _NO_REUSE = true;


    protected $_headers   = [];
    protected $_immutable = false;


    /**
     * Get the components of a header string
     * @param  string $header Header string
     * @return object         Object containing the header name and value
     */
    public function getComponents($header = '')
    {

        $result = new stdClass();
        $header = explode(':', $header);

        if (count($header) > 1)
        {
            $result->name  = trim(array_shift($header));
            $result->value = trim(implode(':', $header));
        }

        else
        {
            $result->name  = '';
            $result->value = '';
        }

        return $result;

    }


    /**
     * Add a custom header
     * @param  string|array $header Custom header string (or array of header strings)
     * @param  bool         $unique Whether to overwrite any existing headers with the same name
     * @return self                 Headers object, for chaining
     * @throws ImmutableHeadersException if the object is immutable
     */
    public function set($header = '', $unique = true)
    {

        if ($this->_immutable)
        {
            throw new ImmutableHeadersException('Cannot set header on an immutable Headers object');
        }

        /*
         * If an array of header strings have been passed
         */
        if (is_array($header))
        {

            foreach ($header as $individual_header)
            {
                $this->set($individual_header, $unique);
            }

        }

        /*
         * If a single string header has been passed
         */
        else
        {

            $header = $this->getComponents($header);
            $name   = $header->name;
            $value  = $header->value;

            if ($name != '' AND $value != '')
            {

                if ($unique)
                {
                    unset($this->_headers[$name]);
                }

                if (!isset($this->_headers[$name]) OR
                    !in_array($value, $this->_headers[$name])
                )
                {
                    $this->_headers[$name][] = $value;
                }

            }

        }

        return $this;

    }


    /**
     * Add a custom header (alias of set() method)
     * @param  string|array $header Custom header string (or array of header strings)
     * @param  bool         $unique Whether to overwrite any existing headers with the same name
     * @return self                 Headers object, for chaining
     * @throws ImmutableHeadersException if the object is immutable
     * @see self::set()
     */
    public function add($header = '', $unique = true)
    {

        if ($this->_immutable)
        {
            throw new ImmutableHeadersException('Cannot set header on an immutable Headers object');
        }

        return call_user_func_array([$this, 'set'], func_get_args());

    }


    /**
     * Replace all headers
     * @param  array $headers   Multidimensional array of header names/values
     * @param  bool  $immutable Whether to make the object immutable
     * @return self             Headers object, for chaining
     * @throws ImmutableHeadersException if the object is immutable
     */
    public function setAll(array $headers = [], $immutable = false)
    {

        if ($this->_immutable)
        {
            throw new ImmutableHeadersException('Cannot set header on an immutable Headers object');
        }

        /*
         * Remove any existing headers
         */
        $this->_headers = [];

        foreach ($headers as $name => $value)
        {

            /*
             * Many values for same header type
             */
            if (is_array($value))
            {

                foreach ($value as $key => $individual_value)
                {
                    $this->set($name . ': ' . $individual_value, false);
                }

            }

            /*
             * Single value for header type
             */
            else
            {
                $this->set($name . ': ' . $value, false);
            }

        }

        $this->_immutable = !!$immutable;

        return $this;

    }


    /**
     * Get all headers of a certain type
     * @param  string $type Header type name
     * @return array        Array of header values
     */
    public function get($type = '')
    {

        $type = trim($type);

        if (isset($this->_headers[$type]))
        {
            return $this->_headers[$type];
        }

        return [];

    }


    /**
     * Get all headers
     * @return array Multidimensional array of header names/values
     */
    public function getAll()
    {

        return $this->_headers;

    }


    /**
     * Remove a header
     * @param  string|array $header Header to remove (or array of header strings)
     * @return self                 Headers object, for chaining
     * @throws ImmutableHeadersException if the object is immutable
     */
    public function remove($header = '')
    {

        if ($this->_immutable)
        {
            throw new ImmutableHeadersException('Cannot set header on an immutable Headers object');
        }

        /*
         * If an array of header strings have been passed
         */
        if (is_array($header))
        {

            foreach ($header as $individual_header)
            {
                $this->remove($individual_header);
            }

        }

        /*
         * If a single string header has been passed
         */
        else
        {

            $header = $this->getComponents($header);
            $name   = $header->name;
            $value  = $header->value;

            if (isset($this->_headers[$name]))
            {

                /*
                 * Remove the applicable header if found
                 */
                foreach ($this->_headers[$name] as $header_key => $header_value)
                {

                    if ($value == $header_value)
                    {
                        unset($this->_headers[$name][$header_key]);
                    }

                }

                /*
                 * Remove the corresponding header group if it is now empty
                 */
                if (!count($this->_headers[$name]))
                {
                    unset($this->_headers[$name]);
                }

            }

        }

        return $this;

    }


    /**
     * Remove all headers of a certain type
     * @param  string $type Name of header type to remove
     * @return self         Headers object, for chaining
     * @throws ImmutableHeadersException if the object is immutable
     */
    public function removeType($type = '')
    {

        if ($this->_immutable)
        {
            throw new ImmutableHeadersException('Cannot set header on an immutable Headers object');
        }

        $type = trim($type);

        unset($this->_headers[$type]);

        return $this;

    }


    /**
     * Output all headers
     * @throws ImmutableHeadersException if the object is immutable
     */
    public function output()
    {

        if ($this->_immutable)
        {
            throw new ImmutableHeadersException('Cannot set header on an immutable Headers object');
        }

        foreach ($this->_headers as $name => $values)
        {

            foreach ($values as $value)
            {
                header($name . ': ' . $value, false);
            }

        }

    }


}