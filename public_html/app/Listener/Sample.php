<?php


namespace Whiskey\Bourbon\App\Listener;


/**
 * Sample listener class
 * @package Whiskey\Bourbon\App\Listener
 */
class Sample extends Listener
{


    protected $_hook = 'APP_POST_ROUTING';


    /**
     * Action to be executed when the listened-for event is broadcast
     * @param mixed ... Optional broadcast arguments
     */
    public function run()
    {

        // Code to execute goes here

    }


}