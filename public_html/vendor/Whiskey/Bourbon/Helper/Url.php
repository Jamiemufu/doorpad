<?php


namespace Whiskey\Bourbon\Helper;


/**
 * Url class
 * @package Whiskey\Bourbon\Helper
 */
class Url
{


    protected $_canonical_domain = '';
    protected $_doc_root         = '';


    /**
     * Instantiate the Url object
     */
    public function __construct()
    {

        $this->_doc_root = rtrim($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_NAME']));

    }


    /**
     * Get the document root, as set in the constructor
     * @return string Document root
     */
    public function getDocRoot()
    {

        return $this->_doc_root;

    }


    /**
     * Set the canonical domain
     * @param string $domain Canonical domain
     */
    public function setCanonicalDomain($domain = '')
    {

        $this->_canonical_domain = rtrim($domain, '/');

    }


    /**
     * Return the current URL up to the domain
     * @return string Domain fragment of URL
     */
    public function domain()
    {

        return 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

    }


    /**
     * Return the current URL up to the script's base path
     * @return string Domain/base path fragment of URL
     */
    public function path()
    {

        return rtrim($this->domain() . $this->_doc_root, '/');

    }


    /**
     * Return the query string, based on the current action and $_GET arguments
     * @return string Query string
     */
    protected function _getRequestUrl()
    {

        return '/' . ltrim($_SERVER['REQUEST_URI'], '/');

    }


    /**
     * Return the current URL
     * @return string Current URL
     */
    public function full()
    {

        $page_url = $this->_getRequestUrl();

        return $this->domain() . $page_url;
    
    }


    /**
     * Return the canonical domain
     * @return string Canonical domain (or current domain if not set)
     */
    public function canonicalDomain()
    {

        if ($this->_canonical_domain != '')
        {
            return $this->_canonical_domain;
        }

        return $this->domain();

    }


    /**
     * Return a canonical URL, taking routing rules into consideration
     * @return string Full canonical URL
     */
    public function canonical()
    {

        $page_url = $this->_getRequestUrl();

        return $this->canonicalDomain() . $page_url;

    }


}