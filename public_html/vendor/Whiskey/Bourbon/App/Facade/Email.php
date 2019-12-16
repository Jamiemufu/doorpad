<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Email façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Email extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Email\Handler::class;

    }


}