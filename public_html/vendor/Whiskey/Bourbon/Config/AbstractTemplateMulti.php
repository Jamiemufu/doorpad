<?php


namespace Whiskey\Bourbon\Config;


use InvalidArgumentException;


/**
 * Abstract configuration template class, to be extended by individual
 * configuration files -- will collect all values passed with the same name,
 * rather than overwriting them
 * @package Whiskey\Bourbon\Config
 */
abstract class AbstractTemplateMulti extends AbstractTemplateSingle implements TemplateInterface
{


    /**
     * Set a configuration value
     * @param string $name  Name of value to set
     * @param mixed  $value Value to set
     * @throws InvalidArgumentException if a name is not passed
     */
    public function set($name = null, $value = '')
    {

        if (is_null($name))
        {
            throw new InvalidArgumentException('Missing configuration entry name');
        }

        $this->_values[$name][] = $value;

    }


}