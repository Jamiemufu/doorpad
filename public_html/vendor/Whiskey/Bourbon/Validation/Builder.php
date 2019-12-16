<?php


namespace Whiskey\Bourbon\Validation;


/**
 * Validation Builder class
 * @package Whiskey\Bourbon\Validation
 */
class Builder
{


    const _NO_REUSE = true;


    protected $_field    = [];
    protected $_type     = null;
    protected $_message  = null;
    protected $_compare  = null;
    protected $_required = false;


    /**
     * Set the field
     * @param  string|array $field Field name (or array of names)
     * @return self                Builder object for chaining
     */
    public function field($field = '')
    {

        /*
         * If only one field has been passed, put it into an array
         */
        if (!is_array($field))
        {
            $field = [$field];
        }

        $this->_field = $field;

        return $this;

    }


    /**
     * Set the type
     * @param  string $type Type name
     * @return self         Builder object for chaining
     */
    public function type($type = '')
    {

        $this->_type = $type;

        return $this;

    }


    /**
     * Set an alternative error message
     * @param  string $message Message text
     * @return self            Builder object for chaining
     */
    public function errorMessage($message = '')
    {

        $this->_message = $message;

        return $this;

    }


    /**
     * Set the value to compare against
     * @param  mixed $value Comparison value
     * @return self         Builder object for chaining
     */
    public function compare($value = '')
    {

        $this->_compare = $value;

        return $this;

    }


    /**
     * Set the 'required' value to TRUE
     * @return self Builder object for chaining
     */
    public function required()
    {

        $this->_required = true;

        return $this;

    }


    /**
     * Get an array of the object's properties
     * @return array Array of Builder object properties
     */
    public function getDetails()
    {

        return
            [
                'field'    => $this->_field,
                'type'     => $this->_type,
                'message'  => $this->_message,
                'compare'  => $this->_compare,
                'required' => $this->_required
            ];

    }


}