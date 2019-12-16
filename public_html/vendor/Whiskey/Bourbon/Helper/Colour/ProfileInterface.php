<?php


namespace Whiskey\Bourbon\Helper\Colour;


/**
 * ProfileInterface interface
 * @package Whiskey\Bourbon\Helper\Colour
 */
interface ProfileInterface
{


    /**
     * Get the name of the colour profile
     * @return string Name of colour profile
     */
    public function getName();


    /**
     * Convert the colour value to its RGB counterpart
     * @param  mixed       ... Value(s) representing the colour
     * @return RgbTemplate     RgbTemplate object
     */
    public function toRgb();


    /**
     * Get a colour value from its RGB counterpart
     * @param  RgbTemplate $rgb_template RgbTemplate object
     * @return mixed                     String or object representing the colour
     */
    public function fromRgb(RgbTemplate $rgb_template);


}