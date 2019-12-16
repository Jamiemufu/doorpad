<?php


namespace Whiskey\Bourbon\Storage\Database;


use Whiskey\Bourbon\Storage\Database\Mysql\QueryBuilder;


/**
 * Pagination class
 * @package Whiskey\Bourbon\Storage\Database
 */
class Pagination
{


    protected $_paginations      = [];
    protected $_pagination_count = 0;


    /**
     * Get information for a database query
     * @param  QueryBuilder $db_build QueryBuilder object
     * @return int                    Page offset
     */
    public function getInfoForDbQuery(QueryBuilder $db_build)
    {

        $count  = ++$this->_pagination_count;
        $offset = isset($_GET['_offset_' . $count]) ? (int)$_GET['_offset_' . $count] : 0;

        $this->_paginations[$count] = ['db_build' => $db_build,
                                       'limit'    => $db_build->getPaginateLimit(),
                                       'offset'   => $offset];

        return $offset;

    }


    /**
     * Get the offset of the page
     * @param  int $pagination Which pagination set to use
     * @return int             Page offset
     */
    public function getOffset($pagination = 1)
    {

        return $this->_paginations[$pagination]['offset'];

    }


    /**
     * Get the limit of the page
     * @param  int $pagination Which pagination set to use
     * @return int             Page limit
     */
    public function getLimit($pagination = 1)
    {

        return $this->_paginations[$pagination]['limit'];

    }


    /**
     * Get the total records in the last Db::build() query to use paginate()
     * @param  int $pagination Which pagination set to use
     * @return int             Record count
     */
    public function getTotal($pagination = 1)
    {

        if (isset($this->_paginations[$pagination]['db_build']))
        {

            $max_limit = QueryBuilder::MAX_LIMIT;

            /*
             * Alter the query to span all records
             */
            $this->_paginations[$pagination]['db_build']->startAt(0);
            $this->_paginations[$pagination]['db_build']->fetch($max_limit);

            return $this->_paginations[$pagination]['db_build']->count();

        }

        return 0;

    }


    /**
     * Get information about pages
     * @param  int    $pagination Which pagination set to use
     * @return object             Object of page information
     */
    public function getInformation($pagination = 1)
    {

        $max           = $this->getTotal($pagination);
        $limit         = $this->getLimit($pagination);
        $offset        = $this->getOffset($pagination);
        $count         = 0;
        $temp_previous = 0;
        $previous      = 0;
        $next          = 0;
        $current_num   = null;
        $current_page  = ($offset > 0) ? (floor($offset / $limit) + 1) : 1;
        $pages         = [];

        while ($count < $max)
        {

            /*
             * Current page
             */
            $page_number = (int)(floor($count / $limit) + 1);
            $is_current  = ($offset >= $count AND $offset < ($count + $limit));
            
            if ($is_current)
            {
                $current_num = $count;
                $previous    = $temp_previous;
            }

            /*
             * Store 'prev' and 'next' page offsets
             */
            if (!is_null($current_num) AND $current_num < $count)
            {
                $next        = $page_number;
                $current_num = $max;
            }
            $temp_previous = $page_number;

            /*
             * Individual page
             */
            $pages[$page_number] = $count;

            $count += $limit;

        }

        end($pages);
        $last_page = key($pages);
        reset($pages);

        $result = new \stdClass();

        $result->max           = $max;
        $result->limit         = $limit;
        $result->offset        = $offset;
        $result->previous_page = $previous;
        $result->next_page     = $next;
        $result->current_page  = $current_page;
        $result->last_page     = $last_page;
        $result->pages         = $pages;

        return $result;

    }


    /**
     * Get a URL to a specific page, using the current page as a base
     * @param  int    $pagination Which pagination set to use
     * @param  int    $offset     Target offset URL
     * @return string             Full target page URL
     */
    protected function _getOffsetUrl($pagination = 1, $offset = 0)
    {

        $old_fragment = '_offset_' . $pagination . '=' . $this->getOffset($pagination);
        $new_fragment = '_offset_' . $pagination . '=' . $offset;

        /*
         * Update (or add) the '_offset_' URL fragment to the page URL
         */
        $url = 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($_SERVER['REQUEST_URI'], '/');
        $url = str_replace('?' . $old_fragment, '?' . $new_fragment, $url);
        $url = str_replace('&amp;' . $old_fragment, '&amp;' . $new_fragment, $url);

        if (stristr($url, '?' . $new_fragment) === false AND
            stristr($url, '&amp;' . $new_fragment) === false)
        {

            if (stristr($url, '?'))
            {
                $url .= '&amp;' . $new_fragment;
            }

            else
            {
                $url .= '?' . $new_fragment;
            }

        }

        return $url;

    }


    /**
     * Get a page offset from the page number
     * @param  int $pagination Which pagination set to use
     * @param  int $page       Page number
     * @return int             Page offset
     */
    protected function _getOffsetFromPage($pagination = 1, $page = 0)
    {

        $pagination_info = $this->getInformation($pagination);

        return (($page * $pagination_info->limit) - $pagination_info->limit);

    }


    /**
     * Get Bootstrap-compatible HTML page links
     * @param  int    $pagination       Which pagination set to use
     * @param  bool   $first_last       Whether to show first/last links
     * @param  bool   $prev_next        Whether to show previous/next links
     * @param  bool   $middle           Whether to show middle links
     * @param  int    $flank_link_limit Number of pages to link to either side of the active page
     * @return string                   Page links
     */
    public function getPageLinks($pagination = 1, $first_last = true, $prev_next = true, $middle = true, $flank_link_limit = 4)
    {

        if (!isset($this->_paginations[$pagination]))
        {
            return '';
        }

        $pagination_info = $this->getInformation($pagination);
        $result          = '<ul class="pagination">';

        /*
         * Increase the flank link limit by one so it is inclusive of the set number
         */
        $flank_link_limit++;

        /*
         * Previous page
         */
        if ($prev_next)
        {

            $previous_offset = $this->_getOffsetFromPage($pagination, $pagination_info->previous_page);

            if ($pagination_info->current_page != 1)
            {
                $result .= '<li><a href="' . $this->_getOffsetUrl($pagination, $previous_offset) . '">Prev</a></li>';
            }

            else
            {
                $result .= '<li class="disabled"><a href="javascript:void(0);">Prev</a></li>';
            }

        }

        /*
         * First page
         */
        $first_page = '<li><a href="' . $this->_getOffsetUrl($pagination, 0) . '">1</a></li>';

        if ($first_last AND ($pagination_info->current_page - $flank_link_limit) >= 1)
        {
            $result .= $first_page;
        }

        if ($middle)
        {

            /*
             * Middle pages
             */
            foreach ($pagination_info->pages as $page_number => $offset)
            {

                /*
                 * Only include a set number of page links either side of the
                 * active page
                 */
                if (($page_number > ($pagination_info->current_page - $flank_link_limit)) AND
                    ($page_number < ($pagination_info->current_page + $flank_link_limit)))
                {
                    $current_page = ($page_number == $pagination_info->current_page) ? ' class="active"' : '';
                    $result      .= '<li' . $current_page . '><a href="' . $this->_getOffsetUrl($pagination, $offset) . '">' . number_format($page_number, 0) . '</a></li>';
                }

            }

        }

        /*
         * Last page
         */
        $last_offset = $this->_getOffsetFromPage($pagination, $pagination_info->last_page);
        $last_page   = '<li><a href="' . $this->_getOffsetUrl($pagination, $last_offset) . '">' . number_format($pagination_info->last_page, 0) . '</a></li>';
        
        if ($first_last AND ($pagination_info->current_page + $flank_link_limit) <= $pagination_info->last_page)
        {
            $result .= $last_page;
        }

        /*
         * Next page
         */
        if ($prev_next)
        {

            $next_offset = $this->_getOffsetFromPage($pagination, $pagination_info->next_page);

            if ($pagination_info->current_page != $pagination_info->last_page AND
                count($pagination_info->pages))
            {
                $result .= '<li><a href="' . $this->_getOffsetUrl($pagination, $next_offset) . '">Next</a></li>';
            }

            else
            {
                $result .= '<li class="disabled"><a href="javascript:void(0);">Next</a></li>';
            }

        }

        $result .= '</ul>';

        return $result;

    }


    /**
     * Get previous/next HTML meta tags
     * @param  int    $pagination Which pagination set to use
     * @return string             Meta tag string
     */
    public function getMetaTags($pagination = 1)
    {

        if (!isset($this->_paginations[$pagination]))
        {
            return '';
        }

        $pagination_info = $this->getInformation($pagination);

        $result  = '';

        /*
         * Previous page
         */
        if ($pagination_info->current_page != 1)
        {
            $previous_offset  = (($pagination_info->previous_page * $pagination_info->limit) - $pagination_info->limit);
            $result          .= '<link rel="prev" href="' . $this->_getOffsetUrl($pagination, $previous_offset) . '" />';
        }

        /*
         * Next page
         */
        if ($pagination_info->current_page != $pagination_info->last_page)
        {
            $next_offset  = (($pagination_info->next_page * $pagination_info->limit) - $pagination_info->limit);
            $result      .= '<link rel="next" href="' . $this->_getOffsetUrl($pagination, $next_offset) . '" />';
        }

        return $result;

    }


}