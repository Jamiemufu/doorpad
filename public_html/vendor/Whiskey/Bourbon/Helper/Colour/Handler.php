<?php


namespace Whiskey\Bourbon\Helper\Colour;


use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\EngineNotRegisteredException;
use Whiskey\Bourbon\Helper\Colour\Profile\Cmyk;
use Whiskey\Bourbon\Helper\Colour\Profile\Hex;
use Whiskey\Bourbon\Helper\Colour\Profile\Rgb;
use Whiskey\Bourbon\Helper\Colour\Profile\Hsl;
use Whiskey\Bourbon\Helper\Colour\Profile\Hsv;


/**
 * Colour Handler class
 * @package Whiskey\Bourbon\Helper\Colour
 */
class Handler
{


    protected $_profiles     = [];
    protected $_rgb_template = null;


    /**
     * Instantiate the colour Handler object
     * @param Cmyk $cmyk Cmyk object
     * @param Hex  $hex  Hex object
     * @param Rgb  $rgb  Rgb object
     * @param Hsl  $hsl  Hsl object
     * @param Hsv  $hsv  Hsv object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Cmyk $cmyk, Hex $hex, Rgb $rgb, Hsl $hsl, Hsv $hsv)
    {

        if (!isset($cmyk) OR
            !isset($hex) OR
            !isset($rgb) OR
            !isset($hsl) OR
            !isset($hsv))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_profiles[strtolower($cmyk->getName())] = $cmyk;
        $this->_profiles[strtolower($hex->getName())]  = $hex;
        $this->_profiles[strtolower($rgb->getName())]  = $rgb;
        $this->_profiles[strtolower($hsl->getName())]  = $hsl;
        $this->_profiles[strtolower($hsv->getName())]  = $hsv;

    }


    /**
     * Add a new colour profile
     * @param ProfileInterface $profile Colour profile object, implementing ProfileInterface
     */
    public function addProfile(ProfileInterface $profile)
    {

        $profile_name                   = strtolower($profile->getName());
        $this->_profiles[$profile_name] = $profile;

    }


    /**
     * Create an instance of the colour Handler
     * @param  string $profile Name of profile of source colour
     * @param  mixed  ...      Value(s) representing the colour
     * @return self            Colour Handler object
     * @throws EngineNotRegisteredException if the colour profile does not exist
     */
    public function create($profile = '')
    {

        $profile   = strtolower($profile);
        $arguments = func_get_args();

        if (isset($this->_profiles[$profile]))
        {

            $colour = clone $this;

            call_user_func_array([$colour, '_set'], $arguments);

            return $colour;

        }

        throw new EngineNotRegisteredException('Colour profile \'' . $profile . '\' does not exist');

    }


    /**
     * Set the RGB colour
     * @param  mixed  ...      Name of profile of source colour and value(s) representing the colour
     * @return self            Colour Handler object
     * @throws EngineNotRegisteredException if the colour profile does not exist
     */
    protected function _set()
    {

        $arguments = func_get_args();
        $profile   = strtolower(array_shift($arguments));

        if (!isset($this->_profiles[$profile]))
        {
            throw new EngineNotRegisteredException('Colour profile \'' . $profile . '\' does not exist');
        }

        $profile_object      = $this->_profiles[$profile];
        $this->_rgb_template = call_user_func_array([$profile_object, 'toRgb'], $arguments);

    }


    /**
     * Get the colour as represented by a profile
     * @param  string  $profile Name of profile of target colour
     * @return Handler          Colour Handler object
     * @throws EngineNotInitialisedException if the colour has not been set
     * @throws EngineNotRegisteredException if the colour profile does not exist
     */
    public function get($profile = '')
    {

        $profile = strtolower($profile);

        if (is_null($this->_rgb_template))
        {
            throw new EngineNotInitialisedException('Colour has not been set');
        }

        if (!isset($this->_profiles[$profile]))
        {
            throw new EngineNotRegisteredException('Colour profile \'' . $profile . '\' does not exist');
        }

        return $this->_profiles[$profile]->fromRgb($this->_rgb_template);

    }


}