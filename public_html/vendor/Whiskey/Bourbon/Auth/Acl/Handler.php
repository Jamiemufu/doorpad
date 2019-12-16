<?php


namespace Whiskey\Bourbon\Auth\Acl;


use stdClass;
use InvalidArgumentException;


/**
 * ACL Handler class
 * @package Whiskey\Bourbon\Auth\Acl
 */
class Handler
{


    protected $_route_rules = [];


    /**
     * Get a hash to represent a route
     * @param  string $controller Controller name
     * @param  string $action     Controller action name
     * @return string             Hashed route key
     */
    protected function _getRouteHash($controller = '', $action = '')
    {

        return hash('sha512', json_encode(compact('controller', 'action')));

    }


    /**
     * Add a 'group' entry for a controller action
     * @param string $controller Controller name
     * @param string $action     Controller action name
     * @param Group  $group      Group object
     */
    public function allowGroup($controller = '', $action = '', Group $group)
    {

        foreach ($group->getFields() as $fields)
        {

            $granules = (is_array($fields['granules']) ? $fields['granules'] : []);

            $this->allowFields($controller, $action, $fields['values'], $granules);

        }

    }


    /**
     * Add a 'fields' entry for a controller action
     * @param string $controller Controller name
     * @param string $action     Controller action name
     * @param array  $values     Array of fields/values
     * @param array  $granules   Optional array of granular permitted permissions
     * @throws InvalidArgumentException if an array of fields/values was not provided
     */
    public function allowFields($controller = '', $action = '', array $values = [], array $granules = [])
    {

        if (empty($values))
        {
            throw new InvalidArgumentException('A field/value array was not provided');
        }

        $route = $this->_getRouteHash($controller, $action);

        /*
         * If a granularity is not specified, return 'true'
         */
        $granules = empty($granules) ? true : $granules;

        $this->_route_rules[$route][] = ['values' => $values, 'granules' => $granules];

    }


    /**
     * Check whether user details match an array of whitelisted fields
     * @param  array    $fields  Array of whitelisted fields
     * @param  stdClass $details Object of user details
     * @return bool              Whether the user's details were found in the whitelisted fields array
     */
    protected function _checkDetails(array $fields = [], stdClass $details)
    {

        foreach ($fields as $entry)
        {

            $result = true;

            foreach ($entry['values'] as $field => $value)
            {

                /*
                 * Multiple 'or' values
                 */
                if (is_array($value))
                {

                    $or_result = false;

                    foreach ($value as $individual_value)
                    {

                        if (isset($details->$field) AND $details->$field == $individual_value)
                        {

                            $or_result = true;

                            break;

                        }

                    }

                    if ($or_result == false)
                    {

                        $result = false;

                        break;

                    }

                }

                /*
                 * Single values
                 */
                else if (!isset($details->$field) OR $details->$field != $value)
                {

                    $result = false;

                    break;

                }

            }

            if ($result)
            {
                return $entry['granules'];
            }

        }

        return false;

    }


    /**
     * Check whether a user's details permit access to a route
     * @param  string     $controller Controller name
     * @param  string     $action     Controller action name
     * @param  stdClass   $details    Object of user details
     * @return bool|array             Whether the user's details permit access to the route (or array of granular permissions)
     */
    public function isAllowed($controller = '', $action = '', stdClass $details)
    {

        $route        = $this->_getRouteHash($controller, $action);
        $field_result = false;

        if (isset($this->_route_rules[$route]))
        {
            $field_result = $this->_checkDetails($this->_route_rules[$route], $details);
        }

        if ($field_result !== false)
        {
            return $field_result;
        }

        return false;

    }


    /**
     * Check whether a user's details permit access to a route
     * @param  string   $controller Controller name
     * @param  string   $action     Controller action name
     * @param  stdClass $details    Object of user details
     * @param  string   $granule    Name of granule to check the permission of
     * @return bool                 Whether the user's details permit access to the route and specified granule
     */
    public function isPermissionAllowed($controller = '', $action = '', stdClass $details, $granule = '')
    {

        $result = $this->isAllowed($controller, $action, $details);

        /*
         * No match
         */
        if ($result === false)
        {
            return false;
        }

        /*
         * Wildcard match
         */
        if ($result === true)
        {
            return true;
        }

        /*
         * Match, with specific granules
         */
        if (is_array($result))
        {

            foreach ($result as $permission)
            {

                if ($permission == $granule)
                {
                    return true;
                }

            }

        }

        return false;

    }


    /**
     * Instantiate and return an empty Group object
     * @return Group Empty Group object
     */
    public function createGroup()
    {

        return new Group($this);

    }


}