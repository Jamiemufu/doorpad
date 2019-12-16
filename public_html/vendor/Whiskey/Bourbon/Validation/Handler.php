<?php


namespace Whiskey\Bourbon\Validation;


use stdClass;
use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\MissingDependencyException;
use Whiskey\Bourbon\Html\FlashMessage;
use Whiskey\Bourbon\Helper\Component\Captcha\Handler as Captcha;


/**
 * Validation Handler class
 * @package Whiskey\Bourbon\Validation
 */
class Handler
{


    const _NO_REUSE = true;


    protected $_dependencies  = null;
    protected $_validators    = [];
    protected $_form_array    = [];
    protected $_form_elements = [];
    protected $_errors        = [];
    protected $_failed        = [];
    protected $_custom_errors = [];
    protected $_data_hash     = '';
    protected $_dummy_field   = '';
    protected $_result        = true;


    protected static $_custom_validators = [];


    protected $_validator_map =
        [
            'NUM' =>
                [
                    'validator' => 'validateNum',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' contains non-numeric characters'
                ],
            'ALPHA' =>
                [
                    'validator' => 'validateAlpha',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' contains non-alphabetic characters'
                ],
            'ALPHA_SPACES' =>
                [
                    'validator' => 'validateAlphaSpaces',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' contains non-alphabetic characters'
                ],
            'ALPHA_NUM' =>
                [
                    'validator' => 'validateAlphaNum',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' contains non-alphanumeric characters'
                ],
            'ALPHA_NUM_SPACES' =>
                [
                    'validator' => 'validateAlphaNumSpaces',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' contains non-alphanumeric characters'
                ],
            'EMAIL' =>
                [
                    'validator' => 'validateEmail',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' is not a valid e-mail address'
                ],
            'URL' =>
                [
                    'validator' => 'validateUrl',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' is not a URL with valid DNS records'
                ],
            'TRUE' =>
                [
                    'validator' => 'validateTrue',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' is not true'
                ],
            'FALSE' =>
                [
                    'validator' => 'validateFalse',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' is not false'
                ],
            'REGEX' =>
                [
                    'validator' => 'validateRegex',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' does not match the pattern {option_value}'
                ],
            'EARLIER_THAN' =>
                [
                    'validator' => 'validateEarlierThan',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' is not earlier than {option_value}'
                ],
            'LATER_THAN' =>
                [
                    'validator' => 'validateLaterThan',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' is not later than {option_value}'
                ],
            'MATCHES_DATE' =>
                [
                    'validator' => 'validateMatchesDate',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' does not match {option_value}'
                ],
            'IS_DATE' =>
                [
                    'validator' => 'validateIsDate',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' does not look like a valid date'
                ],
            'MIN_LENGTH' =>
                [
                    'validator' => 'validateMinimumLength',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' does not meet the minimum length of {option_value}'
                ],
            'MAX_LENGTH' =>
                [
                    'validator' => 'validateMaximumLength',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' does not meet the maximum length of {option_value}'
                ],
            'LESS_THAN' =>
                [
                    'validator' => 'validateLessThan',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' is not less than {option_value}'
                ],
            'GREATER_THAN' =>
                [
                    'validator' => 'validateGreaterThan',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' is not greater than {option_value}'
                ],
            'CARD_NUMBER' =>
                [
                    'validator' => 'validateCardNumber',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' is not a valid card number'
                ],
            'IS' =>
                [
                    'validator' => 'validateMatch',
                    'options'   => true,
                    'message'   => 'Input \'{key_name}\' is not a match for \'{option_value}\''
                ],
            'HAS_CONTENT' =>
                [
                    'validator' => 'validateHasContent',
                    'options'   => false,
                    'message'   => 'Input \'{key_name}\' is empty'
                ]
        ];


    /**
     * Instantiate a new Handler object
     * @param Validators   $validators    Validators object
     * @param FlashMessage $flash_message FlashMessage object
     * @param Builder      $builder       Builder object
     * @param Captcha      $captcha       Captcha object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Validators $validators, FlashMessage $flash_message, Builder $builder, Captcha $captcha)
    {

        if (!isset($validators) OR
            !isset($flash_message) OR
            !isset($builder) OR
            !isset($captcha))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies                = new stdClass();
        $this->_dependencies->validators    = $validators;
        $this->_dependencies->flash_message = $flash_message;
        $this->_dependencies->builder       = $builder;
        $this->_dependencies->captcha       = $captcha;

        /*
         * Add custom validators that are not in the Validators object
         */
        $this->_setUpCustomValidators();

        /*
         * Set up a dummy field for internal use
         */
        $this->_dummy_field = '_bourbon_dummy_field_' . md5(microtime(true));

        /*
         * Fallback
         */
        $this->_form_array                      = &$_POST;
        $this->_form_array[$this->_dummy_field] = '1';

    }


    /*
    * Set up custom validators that are not in the Validators object
    */
    protected function _setUpCustomValidators()
    {

        /*
         * CAPTCHA
         */
        $captcha = $this->_dependencies->captcha;

        $this->addCustomValidator('CAPTCHA', function() use ($captcha)
        {
            return $captcha->isValid();
        }, 'did not contain a successful CAPTCHA response');

        /*
         * At least one field must have content
         */
        $this->addCustomValidator('ONE_MUST_HAVE_CONTENT', function($input = '', array $comparison = [])
        {

            foreach ($comparison as $field)
            {

                if (isset($this->_form_array[$field]) AND
                    $this->_dependencies->validators->validateRequiredCheck($this->_form_array[$field], true))
                {
                    return true;
                }

            }

            return false;

        }, 'belongs to a group of inputs from which no content was provided');

    }


    /**
     * Reset the validator
     * @return self Validation object for chaining
     */
    public function reset()
    {

        $this->_validators                      = [];
        $this->_form_array                      = &$_POST;
        $this->_form_array[$this->_dummy_field] = '1';
        $this->_form_elements                   = [];
        $this->_errors                          = [];
        $this->_failed                          = [];
        $this->_custom_errors                   = [];
        $this->_data_hash                       = '';
        $this->_result                          = true;

        return $this;

    }


    /**
     * Add a custom validator
     * @param string  $type     Validator type name
     * @param Closure $callback Closure to execute to validate input
     * @param string  $message  Default error message
     * @throws InvalidArgumentException if the closure is invalid
     * @throws InvalidArgumentException if a type name is not provided
     */
    public function addCustomValidator($type = '', Closure $callback, $message = 'is not valid')
    {

        if (!(is_object($callback) AND ($callback instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid custom validator closure passed');
        }

        if ($type == '')
        {
            throw new InvalidArgumentException('Name for custom validator type not provided');
        }

        $type = strtoupper($type);

        static::$_custom_validators[$type] =
            [
                'type'     => $type,
                'callback' => $callback,
                'message'  => $message
            ];

    }


    /**
     * Set the input array
     * @param  array $array Array to use
     * @return self         Validation object for chaining
     */
    public function setInputArray(array &$array = [])
    {

        $this->_form_array                      = $array;
        $this->_form_array[$this->_dummy_field] = '1';

        return $this;

    }


    /**
     * Add a CAPTCHA validator
     * @param string $message Error message
     */
    public function captcha($message = 'CAPTCHA challenge was not successfully completed')
    {

        $builder       = (clone $this->_dependencies->builder);
        $captcha_field = $this->_dependencies->captcha->getInputName();

        $builder->field($captcha_field);
        $builder->type('CAPTCHA');
        $builder->errorMessage($message);
        $builder->required();

        $this->_validators[] = $builder;

    }


    /**
     * Add a validator for a group of fields in which at least one field must contain content
     * @param array  $fields  Names of fields to include in the group
     * @param string $message Error message
     */
    public function oneMustHaveContent(array $fields = [], $message = 'At least one field from the group must contain content')
    {

        $builder = (clone $this->_dependencies->builder);

        $builder->field($this->_dummy_field);
        $builder->compare($fields);
        $builder->type('ONE_MUST_HAVE_CONTENT');
        $builder->errorMessage($message);
        $builder->required();

        $this->_validators[] = $builder;

    }


    /**
     * Add a new validator
     * @param  string|array $name Field/key name (or array of names)
     * @return Builder            Builder object for chaining
     * @throws MissingDependencyException if dependencies are not provided
     */
    public function add($name = '')
    {

        if (!isset($this->_dependencies->validators))
        {
            throw new MissingDependencyException('Dependencies not provided');
        }

        /*
         * If only one name has been passed, put it into an array
         */
        if (!is_array($name))
        {
            $name = [$name];
        }

        $builder             = clone $this->_dependencies->builder;
        $this->_validators[] = $builder;

        $builder->field($name);

        return $builder;

    }


    /**
     * Add a custom error
     * @param string|array $keys    Field/key name (or array of names)
     * @param string       $message Error message
     * @throws InvalidArgumentException if the key does not exist in the input array
     */
    public function addError($keys = '', $message = '')
    {

        /*
         * If only one key has been passed, place it in an array
         */
        if (!is_array($keys))
        {
            $keys = [$keys];
        }

        foreach ($keys as $key)
        {

            if (isset($this->_form_array[$key]))
            {

                $this->_custom_errors[$key][] =
                    [
                        'value'   => $this->_form_array[$key],
                        'message' => $message
                    ];

            }

            else
            {
                throw new InvalidArgumentException('Input element \'' . $key . '\' could not be found');
            }

        }

    }


    /**
     * Set up the validators that have been added
     */
    protected function _setUpValidators()
    {

        /*
         * Reset everything
         */
        $this->_form_elements = [];
        $this->_errors        = [];
        $this->_failed        = [];

        /*
         * Add each validator
         */
        foreach ($this->_validators as $validator)
        {

            $details = $validator->getDetails();

            foreach ($details['field'] as $field)
            {

                $details_to_pass = [];

                $details_to_pass[] = $field;
                $details_to_pass[] = $details['type'];
                $details_to_pass[] = $details['message'];

                if (!is_null($details['compare']))
                {
                    $details_to_pass[] = $details['compare'];
                }

                $details_to_pass[] = $details['required'];

                call_user_func_array([$this, '_addCheck'], $details_to_pass);

            }

        }

        /*
         * Add custom errors
         */
        foreach ($this->_custom_errors as $key => $errors)
        {

            foreach ($errors as $error)
            {
                $this->_failed[$key][] = $error;
            }

        }

    }


    /**
     * Add a key to be validated
     * @param  mixed  $input            Input key name
     * @param  int    $type             Validator to use (one of the Handler constants)
     * @param  string $message          Custom error message
     * @param  mixed  $required_options Whether the input is required to not be blank (or additional options)
     * @param  bool   $required         Whether the input is required to not be blank (if additional options are provided)
     * @return bool                     Whether the validation rule was successfully added
     * @throws InvalidArgumentException if a validation type is not provided
     */
    protected function _addCheck($input = '', $type = null, $message = null, $required_options = true, $required = null)
    {

        /*
         * Decide which argument will be used for the 'required' field -- if the
         * fifth argument is missing, use the fourth argument in its place
         */
        if (is_null($required))
        {
            $required = $required_options;
        }

        if ($type)
        {

            $input_array =
                [
                    'type'     => $type,
                    'required' => $required,
                    'options'  => $required_options,
                    'message'  => $message
                ];

            if (isset($this->_form_array[$input]))
            {

                $input_array['value']   = $this->_form_array[$input];
                $input_array['key']     = $input;
                $this->_form_elements[] = $input_array;

                return true;

            }

            $message                 = 'Input \'' . $input . '\' was not provided';
            $this->_failed[$input][] = ['value' => null, 'message' => $message];

            return false;

        }

        throw new InvalidArgumentException('No validation type provided');

    }


    /**
     * Check whether input passes a custom validation rule
     * @param  Closure $callback Closure to execute to perform validation
     * @param  string  $input    Input to check
     * @param  string  $options  Additional options/information
     * @param  bool    $required Whether the input is required to not be blank
     * @return bool              Whether the input passes validation
     */
    protected function _validateCustom(Closure $callback, $input = '', $options = '', $required = true)
    {

        $filter_result = $callback($input, $options) ? true : false;

        return $this->_dependencies->validators->validateCheck($input, $required, $filter_result);

    }


    /**
     * Determine whether the validator needs to be rechecked since the last call to this method
     * @return bool Whether the validator needs to be rechecked
     */
    protected function _needsRecheck()
    {

        $new_hash =
            [
                'input'     => $this->_form_array,
                'checks'    => $this->_form_elements,
                'prefailed' => $this->_custom_errors
            ];

        $new_hash = json_encode($new_hash);
        $new_hash = hash('sha512', $new_hash);

        $result = ($this->_data_hash !== $new_hash);

        /*
         * Store the hash, for checking next time
         */
        $this->_data_hash = $new_hash;

        return $result;

    }


    /**
     * Check all validation rules
     * @return bool Whether all rules passed validation
     * @throws MissingDependencyException if dependencies are not provided
     */
    protected function _check()
    {

        if (!isset($this->_dependencies->validators))
        {
            throw new MissingDependencyException('Dependencies not provided');
        }

        /*
         * If we've already got a good result, use it
         */
        if (!$this->_needsRecheck())
        {
            return $this->_result;
        }

        $this->_setUpValidators();

        $result        = true;
        $this->_errors = $this->_failed;

        if (!empty($this->_errors))
        {
            $result = false;
        }

        foreach ($this->_form_elements as $element)
        {

            $element_type = strtoupper($element['type']);

            /*
             * Try stock validators
             */
            if (isset($this->_validator_map[$element_type]))
            {

                $validator = $this->_validator_map[$element_type];
                $arguments = $validator['options'] ? [$element['value'], $element['options'], $element['required']] : [$element['value'], $element['required']];

                if (!call_user_func_array([$this->_dependencies->validators, $validator['validator']], $arguments))
                {
                    $result                = false;
                    $key                   = $element['key'];
                    $options               = $element['options'];
                    $message               = $element['message'] ? $element['message'] : str_replace('{option_value}', $options, str_replace('{key_name}', $key, $validator['message']));
                    $this->_errors[$key][] = ['value' => $element['value'], 'message' => $message];
                }

            }

            /*
             * Try custom validators
             */
            else if (isset(static::$_custom_validators[$element_type]))
            {
                if (!$this->_validateCustom(static::$_custom_validators[$element_type]['callback'], $element['value'], $element['options'], $element['required']))
                {
                    $result                = false;
                    $key                   = $element['key'];
                    $message               = $element['message'] ? $element['message'] : 'Input \'' . $key . '\' ' . static::$_custom_validators[$element_type]['message'];
                    $this->_errors[$key][] = ['value' => $element['value'], 'message' => $message];
                }
            }

        }

        $this->_result = $result;

        return $this->_result;

    }


    /**
     * Get an array of errors
     * @return array Array of errors
     */
    public function getErrors()
    {

        $this->_check();

        return $this->_errors;

    }


    /**
     * Display any errors as a flash message
     */
    public function showErrors()
    {

        $errors = $this->getErrors();

        if (!empty($errors))
        {

            $compiled_errors = [];

            foreach ($errors as $error_messages)
            {

                foreach ($error_messages as $error_details)
                {
                    $compiled_errors[] = $error_details['message'];
                }

            }

            $compiled_errors = implode('<br />', $compiled_errors);

            $this->_dependencies->flash_message->set($compiled_errors, false);

        }

    }


    /**
     * Check whether all rules will pass validation
     * @return bool Whether all rules passed validation
     */
    public function passed()
    {

        return $this->_check();

    }


    /**
     * Check whether validation of any rules will fail
     * @return bool Whether any rules failed validation
     */
    public function failed()
    {

        return !$this->_check();

    }


}