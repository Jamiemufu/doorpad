<?php


namespace Whiskey\Bourbon\Helper;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Storage\Session;


/**
 * SplitTest class
 * @package Whiskey\Bourbon\Helper
 */
class SplitTest
{


    protected $_dependencies  = null;
    protected $_values        = [];
    protected $_set_id        = '';
    protected $_session_value = '';


    /**
     * Instantiate a SplitTest instance with two default values
     * @param Session $session Session instance
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Session $session)
    {

        if (!isset($session))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies          = new stdClass();
        $this->_dependencies->session = $session;

        /*
         * Generate a default pair of values unique to the installation
         */
        $values = str_split(hash('sha512', __DIR__), 64);

        /*
         * If the values are the same, differentiate them with an integer
         */
        if ($values[0] == $values[1])
        {
            $values[0] .= '1';
            $values[1] .= '2';
        }

        $this->setValues($values);

    }


    /**
     * Set values for the split test
     * @param array $values Array of values
     * @throws InvalidArgumentException if an array is not provided or less than two values are provided
     */
    public function setValues(array $values = [])
    {

        if (!is_array($values) OR count($values) < 2)
        {
            throw new InvalidArgumentException('Invalid split test value list');
        }

        /*
         * Normalise the entries and the array that holds them
         */
        $this->_values = array_values(array_map('strval', $values));

        /*
         * Hash the entries, so the set can be identified and not clash if
         * multiple value sets are used
         */
        $this->_set_id = md5(json_encode($this->_values));

    }


    /**
     * Get a value for the session
     */
    public function get()
    {

        $session       = $this->_dependencies->session;
        $session_key   = '_bourbon_split_test_' . $this->_set_id;
        $session_value = $session->read($session_key);

        if (is_null($session_value))
        {

            $session_value = $this->_values[mt_rand(0, count($this->_values) - 1)];

            $session->write($session_key, $session_value);

        }

        return $session_value;

    }


}