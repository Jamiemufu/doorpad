<?php


namespace Whiskey\Bourbon\Helper\Colour;


/**
 * RgbTemplate class
 * @package Whiskey\Bourbon\Helper\Colour
 */
class RgbTemplate
{


    protected $_red   = 0;
    protected $_green = 0;
    protected $_blue  = 0;


    /**
     * Instantiate an RgbTemplate object
     * @param int $red   Red value
     * @param int $green Green value
     * @param int $blue  Blue value
     */
    public function __construct($red = 0, $green = 0, $blue = 0)
    {

        $this->_red   = (int)$red;
        $this->_green = (int)$green;
        $this->_blue  = (int)$blue;

    }


    /**
     * Get the red value
     * @return int Red value
     */
    public function getRed()
    {

        return $this->_red;

    }


    /**
     * Get the green value
     * @return int Red value
     */
    public function getGreen()
    {

        return $this->_green;

    }


    /**
     * Get the blue value
     * @return int Red value
     */
    public function getBlue()
    {

        return $this->_blue;

    }


}