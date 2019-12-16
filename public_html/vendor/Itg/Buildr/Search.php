<?php


namespace Itg\Buildr;


use Exception;
use stdClass;
use Whiskey\Bourbon\App\Http\MainController;


/**
 * Search class
 * @package Itg\Buildr
 */
class Search
{


    const _NO_REUSE = true;


    protected $_dependencies   = null;
    protected $_search_terms   = [];
    protected $_search_results = [];
    protected $_search_tags    = [];


    /**
     * Instantiate the Search object
     * @param MainController $controller   MainController object
     * @param string         $search_terms Search term string
     * @throws Exception if dependencies are not provided
     */
    public function __construct(MainController $controller, $search_terms = '')
    {

        if (!isset($controller))
        {
            throw new Exception('Dependencies not provided');
        }

        $this->_dependencies             = new stdClass();
        $this->_dependencies->controller = $controller;

        $this->_search_terms = array_filter(explode(' ', trim($search_terms)));

    }


    /**
     * Get the array of search terms
     * @return array Array of search terms
     */
    public function getSearchTerms()
    {

        return $this->_search_terms;

    }


    /**
     * Get the search terms as a string
     * @return string Search term string
     */
    public function getSearchTermsString()
    {

        return implode(' ', $this->getSearchTerms());

    }


    /**
     * Get the search results
     * @return array Array of search results
     */
    public function getResults()
    {

        return $this->_search_results;

    }


    /**
     * Add a search result
     * @param string       $header Search result header
     * @param string       $body   Search result text
     * @param array|string $route  Search result route array or URL
     * @param string       $tag    Section tag
     * @throws Exception if the header text is not valid
     */
    public function add($header = '', $body = '', $route = [], $tag = '')
    {

        if ($header == '')
        {
            throw new Exception('Invalid search result header');
        }

        $url = '';

        if (!empty($route))
        {

            if (is_array($route))
            {
                $url = call_user_func_array([$this->_dependencies->controller, '_link'], $route);
            }

            else if (is_string($route))
            {
                $url = $route;
            }

        }

        $search_result      = (object)compact('header', 'body', 'url', 'tag');
        $search_result_hash = hash('sha512', json_encode($search_result));

        $this->_search_results[$search_result_hash] = $search_result;

        if ($tag != '' AND !in_array($tag, $this->_search_tags))
        {
            $this->_search_tags[] = $tag;
        }

    }


    /**
     * Get an array of search tags
     * @return array Array of search tags
     */
    public function getSearchTags()
    {

        natcasesort($this->_search_tags);

        return $this->_search_tags;

    }


}