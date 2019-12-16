<?php


namespace Whiskey\Bourbon\Config\Type;


use Whiskey\Bourbon\Config\AbstractTemplateSingle;


/**
 * Class to define class aliases
 * @package Whiskey\Bourbon\Config
 */
class ClassAlias extends AbstractTemplateSingle
{


    /**
     * Get the name of the configuration class
     * @return string Name of the configuration class
     */
    public function getName()
    {

        return 'class_aliases';

    }


}