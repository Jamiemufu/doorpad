<?php


namespace Whiskey\Bourbon\Storage\Meta;


use Whiskey\Bourbon\App\DefaultHandlerAbstract;


/**
 * Meta storage Handler class
 * @package Whiskey\Bourbon\Storage\Meta
 */
class Handler extends DefaultHandlerAbstract
{


    /**
     * Set the typehint for the handler
     */
    public function __construct()
    {

        $this->_type_hint = MetaInterface::class;

    }


}