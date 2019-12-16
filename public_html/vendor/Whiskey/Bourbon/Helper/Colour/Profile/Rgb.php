<?php


namespace Whiskey\Bourbon\Helper\Colour\Profile;


use stdClass;
use Whiskey\Bourbon\Helper\Colour\RgbTemplate;
use Whiskey\Bourbon\Helper\Colour\ProfileInterface;


/**
 * Rgb colour class
 * @package Whiskey\Bourbon\Helper\Colour\Profile
 */
class Rgb implements ProfileInterface
{


    /**
     * Get the name of the colour profile
     * @return string Name of colour profile
     */
    public function getName()
    {

        return 'rgb';

    }


    /**
     * Convert the colour value to its RGB counterpart
     * @param  int         $red   Red value
     * @param  int         $green Green value
     * @param  int         $blue  Blue value
     * @return RgbTemplate        RgbTemplate object
     */
    public function toRgb($red = 0, $green = 0, $blue = 0)
    {

        return new RgbTemplate($red, $green, $blue);

    }


    /**
     * Get a colour value from its RGB counterpart
     * @param  RgbTemplate $rgb_template RgbTemplate object
     * @return object                    Object with 'r', 'g' and 'b' colour values
     */
    public function fromRgb(RgbTemplate $rgb_template)
    {

        $result    = new stdClass();
        $result->r = $rgb_template->getRed();
        $result->g = $rgb_template->getGreen();
        $result->b = $rgb_template->getBlue();

        return $result;

    }


}