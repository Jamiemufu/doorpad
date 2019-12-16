<?php


namespace Whiskey\Bourbon\Html;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Security\Csrf as Csrf;


/**
 * FormBuilder class
 * @package Whiskey\Bourbon\Html
 */
class FormBuilder
{


    const _NO_REUSE = true;


    protected $_dependencies = null;
    protected $_output       = [];


    /**
     * Instantiate a new FormBuilder object
     * @param Csrf   $csrf       Csrf object
     * @param string $method     Form method (GET/POST)
     * @param string $action     Form action URL
     * @param array  $attributes Array of key/value attribute pairs
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Csrf $csrf, $method = 'POST', $action = '', array $attributes = [])
    {

        if (!isset($csrf))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies       = new stdClass();
        $this->_dependencies->csrf = $csrf;

        $this->_output[] = '<form action="' . $action . '" method="' . $method . '"' .
                           $this->_compileAttributeString($attributes) .
                           '>';

    }


    /**
     * Compile an attribute string from an array of key/value pairs
     * @param  array  $attributes Array of key/value attribute pairs
     * @return string             Compiled attribute string
     */
    protected function _compileAttributeString(array $attributes = [])
    {

        $result = '';

        foreach ($attributes as $attribute_name => $attribute_value)
        {
            $result .= ' ' . $attribute_name . '="' . str_replace('"', '&quot;', $attribute_value) . '"';
        }

        return $result;

    }


    /**
     * Compile a self-closing form tag string
     * @param  string $name      Method/extension name
     * @param  array  $arguments Method arguments
     * @return self              Builder object for chaining
     */
    public function __call($name = '', array $arguments = [])
    {

        $non_self_closing = ['textarea', 'button'];

        array_unshift($arguments, $name);

        /*
         * Non-self-closing tags
         */
        if (in_array(strtolower($name), $non_self_closing))
        {
            $this->_output[] = call_user_func_array([$this, '_compileClosingTag'], $arguments);
        }

        /*
         * Self-closing tags
         */
        else
        {
            $this->_output[] = call_user_func_array([$this, '_compileSelfClosingTag'], $arguments);
        }

        return $this;

    }


    /**
     * Compile a <select> tag string
     * @param  array  $options      Array of <option> tag data
     * @param  array  $attributes   Array of key/value attribute pairs
     * @param  string $active_value Value whose option will be marked as 'selected'
     * @return self                 Builder object for chaining
     */
    public function select(array $options = [], array $attributes = [], $active_value = '')
    {

        $result       = '<select' . $this->_compileAttributeString($attributes) . '>';
        $found_active = false;

        foreach ($options as $key => $value)
        {

            /*
             * Deal with <optgroup> tags
             */
            if (is_array($value))
            {

                $result .= '<optgroup label="' . str_replace('"', '&quot;', $key) . '">';

                foreach ($value as $key_2 => $value_2)
                {

                    $option_value = str_replace('"', '&quot;', $key_2);
                    $selected     = '';

                    if (!$found_active AND $active_value == $key_2)
                    {
                        $selected     = ' selected="selected"';
                        $found_active = true;
                    }

                    $result .= '<option value="' . $option_value . '"' . $selected . '>' . str_replace('"', '&quot;', $value_2) . '</option>';

                }

                $result .= '</optgroup>';

            }

            /*
             * Regular <option> tags
             */
            else
            {

                $option_value = str_replace('"', '&quot;', $key);
                $selected     = '';

                if (!$found_active AND $active_value == $key)
                {
                    $selected     = ' selected="selected"';
                    $found_active = true;
                }

                $result .= '<option value="' . $option_value . '"' . $selected . '>' . str_replace('"', '&quot;', $value) . '</option>';

            }

        }

        $result .= '</select>';

        $this->_output[] = $result;

        return $this;

    }


    /**
     * Compile a non-self-closing form tag string
     * @param  string $tag        Tag name
     * @param  string $value      Tag value
     * @param  array  $attributes Array of key/value attribute pairs
     * @return string             Compiled tag string
     */
    protected function _compileClosingTag($tag = '', $value = '', array $attributes = [])
    {

        return '<' . strtolower($tag) . $this->_compileAttributeString($attributes) . '>' . str_replace('"', '&quot;', $value) . '</' . strtolower($tag) . '>';

    }


    /**
     * Compile a self-closing form tag string
     * @param  string $tag        Tag name
     * @param  array  $attributes Array of key/value attribute pairs
     * @return string             Compiled tag string
     */
    protected function _compileSelfClosingTag($tag = '', array $attributes = [])
    {

        return '<' . strtolower($tag) . $this->_compileAttributeString($attributes) . ' />';

    }


    /**
     * Add a hidden CSRF token tag to the form
     * @return self Builder object for chaining
     */
    public function csrf()
    {

        $csrf_token      = $this->_dependencies->csrf->generateToken();
        $arguments       = ['input', ['type'  => 'hidden', 'name' => 'csrf_token', 'value' => $csrf_token]];
        $this->_output[] = call_user_func_array([$this, '_compileSelfClosingTag'], $arguments);

        return $this;

    }


    /**
     * Return the compiled form string
     * @return string Compiled HTML form string
     */
    public function compile()
    {

        $compiled = $this->getElements();
        $compiled = implode('', $compiled);

        return $compiled;

    }


    /**
     * Get an array of all form elements
     * @return array Array of form elements
     */
    public function getElements()
    {

        $elements   = $this->_output;
        $elements[] = '</form>';

        return $elements;

    }


    /**
     * Get a set (or the last) element added with the form builder
     * @param  string $name Optional element name to look for
     * @return string       HTML string of requested (or last) element
     */
    public function element($name = '')
    {

        $elements = $this->getElements();
        $name     = str_replace('"', '&quot;', $name);
        $result   = '';

        /*
         * Look for a certain element
         */
        if ($name != '')
        {

            foreach ($elements as $element)
            {

                if (preg_match("\"name=\\\"" . preg_quote($name) . "\\\"\"", $element))
                {

                    $result = $element;

                    break;

                }

            }

        }

        /*
         * Retrieve the last element that was added
         */
        else
        {

            /*
             * Remove the '</form>' element first
             */
            array_pop($elements);

            $result = end($elements);
            $result = (string)$result;

        }

        return $result;

    }


}