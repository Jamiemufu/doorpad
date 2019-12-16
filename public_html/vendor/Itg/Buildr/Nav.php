<?php

namespace Itg\Buildr;


use Exception;
use stdClass;
use Whiskey\Bourbon\Storage\Session;
use Whiskey\Bourbon\App\Http\MainController;
use Whiskey\Bourbon\App\Facade\AppEnv;


/**
 * Nav class
 * @package Itg\Buildr
 */
class Nav
{


    protected $_dependencies = null;
    protected $_nav_groups   = [];
    protected $_active_group = 'Home';
    protected $_active_item  = 'Dashboard';
    
    
    /**
     * Instantiate the NavInstance object
     * @param Session        $session    Session object
     * @param MainController $controller MainController object
     * @throws Exception if dependencies are not provided
     */
    public function __construct(Session $session, MainController $controller)
    {

        if (!isset($session) OR
            !isset($controller))
        {
            throw new Exception('Dependencies not provided');
        }

        $this->_dependencies             = new stdClass();
        $this->_dependencies->session    = $session;
        $this->_dependencies->controller = $controller;

    }


    /**
     * Begin the process of adding a nav item by returning a NavItem instance
     * @return NavItem NavItem instance for chaining
     */
    public function build()
    {

        $nav_item = new NavItem($this);

        return $nav_item;

    }


    /**
     * Add a new navigation item
     * @param array $nav_group Array of navigation group/item information
     * @throws Exception if the navigation group array is empty or incomplete
     */
    public function add(array $nav_group = [])
    {

        if (empty($nav_group))
        {
            throw new Exception('Navigation entry is not complete');
        }

        $name = key($nav_group);

        $item = reset($nav_group);
        $item = $item['items'];

        /*
         * Merge with an existing group
         */
        if (isset($this->_nav_groups[$name]))
        {
            $this->_nav_groups[$name]['items'] = array_merge($this->_nav_groups[$name]['items'], $item);
        }

        /*
         * Create the group
         */
        else
        {
            $this->_nav_groups = array_merge($this->_nav_groups, $nav_group);
        }

        $this->_determineActiveLink();

    }


    /**
     * Determine which link was last clicked on and see if the current URL is a new candidate
     */
    protected function _determineActiveLink()
    {

        $nav          = $this->_nav_groups;
        $last_clicked = $this->_dependencies->session->read('_last_clicked_nav');
        $slugs        = empty(AppEnv::slugs()) ? '' : implode('/', AppEnv::slugs());
        $current_url  = $this->_dependencies->controller->_link(AppEnv::controller(), AppEnv::action(), $slugs);
        $active_group = '';
        $active_item  = '';

        foreach ($nav as $group_key => $group)
        {

            foreach ($group['items'] as $item_key => $item)
            {

                if (!is_null($item['target']))
                {

                    /*
                     * Clear any old 'active' flags
                     */
                    unset($nav[$group_key]['active']);
                    unset($nav[$group_key]['items'][$item_key]['active']);

                    $item_url = call_user_func_array([$this->_dependencies->controller, '_link'], $item['target']);

                    /*
                     * Check if we just clicked on this one
                     */
                    if ($item_url == $current_url)
                    {
                        $last_clicked = $item_url;
                        $this->_dependencies->session->write('_last_clicked_nav', $last_clicked);
                    }

                    /*
                     * Check to see if the current page is likely a sub-page
                     */
                    if ($last_clicked == $item_url)
                    {
                        $active_group = $group_key;
                        $active_item  = $item_key;
                    }

                }

            }

        }

        /*
         * Make a note of which item looks likely to be currently active
         */
        if (isset($nav[$active_group]['items'][$active_item]))
        {
            $nav[$active_group]['active']                        = true;
            $nav[$active_group]['items'][$active_item]['active'] = true;
            $this->_active_group                                  = $active_group;
            $this->_active_item                                   = $active_item;
        }

        /*
         * Save the updated list
         */
        $this->_nav_groups = $nav;

    }


    /**
     * Get an object of the raw navigation array
     * @return object Object of navigation groups and items
     */
    protected function _getRawDetails()
    {

        return json_decode(json_encode($this->_nav_groups));

    }


    /**
     * Get an object of the navigation array, prepared for front end use
     * @return object Object of navigation groups and items
     */
    public function getPreparedDetails()
    {

        $nav = $this->_getRawDetails();

        foreach ($nav as $group_key => &$group)
        {

            foreach ($group->items as $item_key => &$item)
            {

                /*
                 * Check that a target has been set
                 */
                if (!is_null($item->target))
                {

                    /*
                     * Check that we are allowed to see it in the nav
                     */
                    if ($item->visible)
                    {

                        /*
                         * Convert the target into a URL
                         */
                        $item->target = call_user_func_array(array($this->_dependencies->controller, '_link'), $item->target);

                    }

                    /*
                     * Remove the entry/group if we are not allowed to see it
                     */
                    else
                    {

                        unset($group->items->{$item_key});

                        if (count(get_object_vars($group->items)) == 0)
                        {
                            unset($nav->$group_key);
                        }

                    }

                }

            }

        }

        return $nav;

    }


    /**
     * Get the navigation HTML
     * @return string Navigation HTML
     */
    public function getHtml()
    {

        $nav = $this->getPreparedDetails();

        return $this->_dependencies->controller->_renderBlock('nav.ice.php', compact('nav'), true);

    }


    /**
     * Get the name of the current active item
     * @return string Name of active item
     */
    public function getActiveItem()
    {

        return $this->_active_item;

    }


    /**
     * Get the name of the current active group
     * @return string Name of active group
     */
    public function getActiveGroup()
    {

        return $this->_active_group;

    }


}