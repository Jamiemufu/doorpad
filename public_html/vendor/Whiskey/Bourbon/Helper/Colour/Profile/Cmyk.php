<?php


namespace Whiskey\Bourbon\Helper\Colour\Profile;


use stdClass;
use Whiskey\Bourbon\Helper\Colour\RgbTemplate;
use Whiskey\Bourbon\Helper\Colour\ProfileInterface;


/**
 * Cmyk colour class
 * @package Whiskey\Bourbon\Helper\Colour\Profile
 */
class Cmyk implements ProfileInterface
{


    /**
     * Get the name of the colour profile
     * @return string Name of colour profile
     */
    public function getName()
    {

        return 'cmyk';

    }


    /**
     * Convert the colour value to its RGB counterpart
     * @param  float       $cyan    Cyan value
     * @param  float       $magenta Magenta value
     * @param  float       $yellow  Yellow value
     * @param  float       $key     Key value
     * @return RgbTemplate          RgbTemplate object
     */
    public function toRgb($cyan = 0.0, $magenta = 0.0, $yellow = 0.0, $key = 0.0)
    {

        $cyan    = min(1, max(0, $cyan));
        $magenta = min(1, max(0, $magenta));
        $yellow  = min(1, max(0, $yellow));
        $key     = min(1, max(0, $key));

        $red   = (255 * (1 - $cyan)    * (1 - $key));
        $green = (255 * (1 - $magenta) * (1 - $key));
        $blue  = (255 * (1 - $yellow)  * (1 - $key));

        $red   = round($red,   0, PHP_ROUND_HALF_UP);
        $green = round($green, 0, PHP_ROUND_HALF_UP);
        $blue  = round($blue,  0, PHP_ROUND_HALF_UP);

        return new RgbTemplate($red, $green, $blue);

    }


    /**
     * Get a colour value from its RGB counterpart
     * @param  RgbTemplate $rgb_template RgbTemplate object
     * @return object                    Object with 'c', 'm', 'y' and 'k' colour values
     */
    public function fromRgb(RgbTemplate $rgb_template)
    {

        $red   = min(255, max(0, $rgb_template->getRed()));
        $green = min(255, max(0, $rgb_template->getGreen()));
        $blue  = min(255, max(0, $rgb_template->getBlue()));

        $red   = $red   / 255;
        $green = $green / 255;
        $blue  = $blue  / 255;

        $key     = (1 - max([$red, $green, $blue]));
        $cyan    = ($key === 1) ? 0 : ((1 - $red - $key)   / (1 - $key));
        $magenta = ($key === 1) ? 0 : ((1 - $green - $key) / (1 - $key));
        $yellow  = ($key === 1) ? 0 : ((1 - $blue - $key)  / (1 - $key));

        $result    = new stdClass();
        $result->c = round($cyan,    2, PHP_ROUND_HALF_UP);
        $result->m = round($magenta, 2, PHP_ROUND_HALF_UP);
        $result->y = round($yellow,  2, PHP_ROUND_HALF_UP);
        $result->k = round($key,     2, PHP_ROUND_HALF_UP);

        return $result;

    }


}