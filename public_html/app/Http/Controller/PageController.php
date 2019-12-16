<?php


namespace Whiskey\Bourbon\App\Http\Controller;


use Whiskey\Bourbon\App\Http\MainController;
use Whiskey\Bourbon\App\Facade\Input;
use Itg\DoorPad\Visitor;

/**
 * PageController class
 * @package Whiskey\Bourbon\App\Http\Controller
 */
class PageController extends MainController
{

    public $visitors = array();

    /**
     * Homepage
     */
    public function home() {

    }


    public function login() {

        if ($this->_request->method == 'POST')
        {

            $data['first_name'] = Input::post('firstname');
            $data['last_name'] = Input::post('lastname');
            $data['badge'] = Input::post('badge');
            $data['company'] = Input::post('company');
            $data['visiting'] = Input::post('visiting');
            $data['carReg'] = Input::post('carReg');
            
            $visitor = visitor::create($data);

            $this->_render(false);
            // $this->_message('Thank you for signing in.');
            $this->_response->body = $visitor->getId();
        }

    }


    public function logout() {

        if ($this->_request->method == 'POST')
        {
            $id = Input::post('id');

            if (isset($_POST['id']))
            {
                Visitor::signOut($id);
            }

            $this->_render(false);
            // $this->_message('Thank you for signing out.');
            $this->_response->body = $id;
        }

        $visitors = Visitor::getAll('ASC');
        $this->_setVariable(compact('visitors'));
    }

    public function signout($id)
    {

    }


    /**
     * 404 page
     */
    public function four_oh_four() {}


    /**
     * 500 page
     */
    public function five_hundred() {}


}