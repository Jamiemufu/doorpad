<?php


namespace Whiskey\Bourbon\Email;


use Whiskey\Bourbon\Exception\Email\RecipientDetailsNotProvidedException;


/**
 * EmailInterface interface
 * @package Whiskey\Bourbon\Email
 */
interface EmailInterface
{


    /**
     * Get the engine name
     * @return string Engine name
     */
    public function getName();


    /**
     * Check whether the e-mail engine has been successfully initialised
     * @return bool Whether the e-mail engine is active
     */
    public function isActive();


    /**
     * Send the e-mail
     * @return bool Whether the e-mail was successfully sent
     * @throws RecipientDetailsNotProvidedException if sender or recipient details were not provided
     */
    public function send();


}