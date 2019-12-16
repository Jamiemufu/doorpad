<?php


namespace Whiskey\Bourbon\Helper\Component\Captcha;


use Whiskey\Bourbon\Exception\EngineNotInitialisedException;


/**
 * CaptchaInterface interface
 * @package Whiskey\Bourbon\Helper\Component\Captcha
 */
interface CaptchaInterface
{


    /**
     * Get the engine name
     * @return string Engine name
     */
    public function getName();


    /**
     * Get the name of the CAPTCHA value input field
     * @return string Name of input field
     */
    public function getInputName();


    /**
     * Check whether the CAPTCHA engine has been successfully initialised
     * @return bool Whether the CAPTCHA engine is active
     */
    public function isActive();


    /**
     * Display a CAPTCHA form
     * @return string CAPTCHA HTML
     * @throws EngineNotInitialisedException if the engine has not been initialised
     */
    public function display();


    /**
     * Check whether a CAPTCHA challenge has passed
     * @return bool Whether a CAPTCHA challenge has passed
     * @throws EngineNotInitialisedException if the engine has not been initialised
     */
    public function isValid();


}