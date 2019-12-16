<?php


namespace Whiskey\Bourbon\Auth\Acl;


use InvalidArgumentException;
use stdClass;
use Whiskey\Bourbon\Auth\Acl\Handler as Acl;


/**
 * ACL Group class
 * @package Whiskey\Bourbon\Auth\Acl
 */
class Group
{


    protected $_dependencies = null;
    protected $_fields       = [];


    /**
     * Instantiate the Group object
     * @param Acl $acl Acl object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Acl $acl)
    {

        if (!isset($acl))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies      = new stdClass();
        $this->_dependencies->acl = $acl;

    }


    /**
     * Add field/value pairs to the group
     * @param  array $values   Array of fields/values
     * @param  array $granules Optional array of granular permitted permissions
     * @return self            Group object for chaining
     * @throws InvalidArgumentException if an array of fields/values was not provided
     */
    public function add(array $values = [], array $granules = [])
    {

        if (empty($values))
        {
            throw new InvalidArgumentException('A field/value array was not provided');
        }

        /*
         * If no granularity is specified, we want to return 'true'
         */
        $granules = empty($granules) ? true : $granules;

        $this->_fields[] = ['values' => $values, 'granules' => $granules];

        return $this;

    }


    /**
     * Get an array of fields/values that the group contains
     * @return array Array of fields/values that the group contains
     */
    public function getFields()
    {

        return $this->_fields;

    }


    /**
     * Assign the group to the ACL handler
     * @param  string $controller Controller name
     * @param  string $action     Controller action name
     * @return self               Group object for chaining
     */
    public function assign($controller = '', $action = '')
    {

        $this->_dependencies->acl->allowGroup($controller, $action, $this);

        return $this;

    }


}