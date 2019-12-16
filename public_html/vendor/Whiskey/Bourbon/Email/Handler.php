<?php


namespace Whiskey\Bourbon\Email;


use Whiskey\Bourbon\App\DefaultHandlerAbstract;


/**
 * Email Handler class
 * @package Whiskey\Bourbon\Email
 */
class Handler extends DefaultHandlerAbstract
{


    /**
     * Set the typehint for the handler
     */
    public function __construct()
    {

        $this->_type_hint = EmailInterface::class;

    }


}