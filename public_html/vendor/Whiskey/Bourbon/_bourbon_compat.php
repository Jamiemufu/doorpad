<?php


/*
 * Add fallback for getallheaders() function, which might be missing on some
 * servers if running from the command-line
 */
if (!function_exists('getallheaders'))
{

    /**
     * Get all request headers from the $_SERVER superglobal
     * @return array Array of headers
     */
    function getallheaders()
    {

        $headers = [];

        foreach ($_SERVER as $name => $value)
        {

            if (strtoupper(mb_substr($name, 0, 5)) == 'HTTP_')
            {
                $name           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', mb_substr($name, 5)))));
                $headers[$name] = $value;
            }

            else if (strtoupper($name) == 'CONTENT_TYPE')
            {
                $headers['Content-Type'] = $value;
            }

            else if (strtoupper($name) == 'CONTENT_LENGTH')
            {
                $headers['Content-Length'] = $value;
            }

        }

        return $headers;

    }

}