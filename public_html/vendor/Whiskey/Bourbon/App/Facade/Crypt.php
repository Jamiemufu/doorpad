<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Crypt façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Crypt extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Security\Crypt::class;

    }


}