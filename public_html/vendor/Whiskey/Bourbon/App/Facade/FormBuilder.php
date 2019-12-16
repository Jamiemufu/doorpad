<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * FormBuilder façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class FormBuilder extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Html\FormBuilder::class;

    }


}