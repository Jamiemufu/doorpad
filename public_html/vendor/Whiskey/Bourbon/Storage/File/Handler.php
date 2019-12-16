<?php


namespace Whiskey\Bourbon\Storage\File;


use Whiskey\Bourbon\App\DefaultHandlerAbstract;


/**
 * File storage Handler class
 * @package Whiskey\Bourbon\Storage\File
 */
class Handler extends DefaultHandlerAbstract
{


    /**
     * Set the typehint for the handler
     */
    public function __construct()
    {

        $this->_type_hint = StorageInterface::class;

    }


}