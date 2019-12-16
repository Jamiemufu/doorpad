<?php


namespace Whiskey\Bourbon\Helper\Component\Captcha;


use Whiskey\Bourbon\App\DefaultHandlerAbstract;


/**
 * Captcha Handler class
 * @package Whiskey\Bourbon\Helper\Component\Captcha
 */
class Handler extends DefaultHandlerAbstract
{


    /**
     * Set the typehint for the handler
     */
    public function __construct()
    {

        $this->_type_hint = CaptchaInterface::class;

    }


}