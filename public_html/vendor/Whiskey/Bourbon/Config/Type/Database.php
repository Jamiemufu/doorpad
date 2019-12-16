<?php


namespace Whiskey\Bourbon\Config\Type;


use Whiskey\Bourbon\Config\AbstractTemplateSingle;


/**
 * Class to define database connection settings
 * @package Whiskey\Bourbon\Config
 */
class Database extends AbstractTemplateSingle
{


    /**
     * Get the name of the configuration class
     * @return string Name of the configuration class
     */
    public function getName()
    {

        return 'database';

    }


}