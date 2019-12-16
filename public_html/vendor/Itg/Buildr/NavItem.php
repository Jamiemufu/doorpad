<?php


namespace Itg\Buildr;


use Exception;
use stdClass;


/**
 * NavItem class
 * @package Itg\Buildr
 */
class NavItem
{


    const _NO_REUSE = true;


    protected $_dependencies = null;
    protected $_group        = null;
    protected $_item         = null;
    protected $_target       = null;
    protected $_visible      = true;

    
    /**
     * Instantiate a NavItem object
     * @param Nav $nav Nav object
     * @throws Exception if dependencies are not provided
     */
    public function __construct(Nav $nav)
    {

        if (!isset($nav))
        {
            throw new Exception('Dependencies not provided');
        }

        $this->_dependencies      = new stdClass();
        $this->_dependencies->nav = $nav;

    }


    /**
     * Set the group details
     * @param  string $name Group name
     * @param  string $icon Group 'Font Awesome' icon
     * @return self         NavItem object
     * @throws Exception if the group name is not valid
     */
    public function group($name = '', $icon = '')
    {

        if ($name == '')
        {
            throw new Exception('Invalid navigation group name');
        }

        $this->_group = new stdClass();

        $this->_group->name = $name;
        $this->_group->icon = $icon;

        return $this;

    }


    /**
     * Set the item details
     * @param  string $name Item name
     * @param  string $icon Item 'Font Awesome' icon
     * @return self         NavItem object
     * @throws Exception if the item name is not valid
     */
    public function item($name = '', $icon = '')
    {

        if ($name == '')
        {
            throw new Exception('Invalid navigation item name');
        }

        $this->_item = new stdClass();

        $this->_item->name = $name;
        $this->_item->icon = $icon;

        return $this;

    }


    /**
     * Set the item target
     * @param  string ... Multiple strings representing controller, view and slugs
     * @return self       NavItem object
     */
    public function target()
    {

        $this->_target = func_get_args();

        return $this;

    }


    /**
     * Set the navigation item visibility
     * @param  bool $hide Whether or not the item should be hidden
     * @return self       NavItem object
     */
    public function hide($hide = true)
    {

        $this->_visible = !$hide;

        return $this;

    }


    /**
     * Compile the navigation entry and pass it to Nav to be added
     * @throws Exception if any required details have not been provided
     */
    public function add()
    {

        if ($this->_group  === null OR
            $this->_item   === null OR
            $this->_target === null)
        {
            throw new Exception('Missing navigation information');
        }

        $nav_item =
            [
                'name'    => $this->_item->name,
                'icon'    => $this->_item->icon,
                'target'  => $this->_target,
                'visible' => $this->_visible
            ];

        $nav_item_compiled = [$this->_item->name => $nav_item];

        $nav_group =
            [
                'name'  => $this->_group->name,
                'icon'  => $this->_group->icon,
                'items' => $nav_item_compiled
            ];

        $compiled_nav_group = [$this->_group->name => $nav_group];

        $this->_dependencies->nav->add($compiled_nav_group);

    }


}