<?php


namespace Whiskey\Bourbon\App\Http;


use Whiskey\Bourbon\Validation\Handler as Validator;


/**
 * MainModel base model class
 * @package Whiskey\Bourbon\App\Http
 */
class MainModel
{


    protected $_request  = null;
    protected $_response = null;


    /**
     * Instantiate the main model
     * @param Request  $request  HTTP Request object
     * @param Response $response HTTP Response object
     */
    public function __construct(Request $request, Response $response)
    {

        $this->_request  = $request;
        $this->_response = $response;

    }


    /**
     * Extendable method to execute before the controller is invoked
     */
    public function _before() {}


    /**
     * Extendable method to execute after the controller is invoked
     */
    public function _after() {}


    /**
     * Model-level validation -- called before controller logic; to be set in
     * extended Model objects
     * @param Validator $validator Validator object
     * @param string    $action    Requested controller action
     * @param array     $slugs     Array of URL slugs
     */
    public function _validate($validator, $action = '', array $slugs = []) {}


    /**
     * Logic to carry out if validation from _validate() fails; to be set in
     * extended Model objects
     * @param Validator $validator Validator object
     * @param string    $action    Requested controller action
     * @param array     $slugs     Array of URL slugs
     */
    public function _onValidationFail($validator, $action = '', array $slugs = []) {}


}