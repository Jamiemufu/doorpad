<?php


namespace Itg\Cms\Http\Controller;


use Exception;
use Itg\Buildr\Facade\User;
use Itg\Buildr\Facade\Me;
use Whiskey\Bourbon\App\Facade\Input;
use Whiskey\Bourbon\App\Facade\Validation;
use Whiskey\Bourbon\App\Http\MainController;


/**
 * AccountController class
 * @package Itg\Cms\Http\Controller
 */
class AccountController extends MainController
{


    protected $_layout_file = 'cms.ice.php';


    /**
     * Show the logged-in user's 'My Account' page
     */
    public function my_account()
    {

        /*
         * Update the user details
         */
        if (isset($_POST['email']))
        {

            $validator = Validation::build();

            $validator->add('email')->type('EMAIL')->errorMessage('E-mail address is not valid')->required();

            if (Input::post('password') != '')
            {
                $validator->add('password')->type('MIN_LENGTH')->compare(User::PASSWORD_MIN_LENGTH)->errorMessage('Password is too short &#40;' . User::PASSWORD_MIN_LENGTH . ' character minimum&#41;')->required();
                $validator->add('password_2')->type('IS')->compare(Input::post('password'))->errorMessage('Passwords do not match')->required();
            }

            if ($validator->failed())
            {
                $validator->showErrors();
                $this->_with($_POST)->_response->redirect(AccountController::class, 'my_account');
            }

            $password = Input::post('password');
            $email    = Input::post('email');

            try
            {

                Me::setEmail($email);

                if (Input::post('password') != '')
                {
                    Me::updatePassword($password);
                    $this->_model->attemptLogin(Me::getUsername(), Input::post('password'));
                }

                $this->_message('Account details successfully updated');
                $this->_response->redirect(AccountController::class, 'my_account');

            }

            catch (Exception $exception)
            {
                $this->_message('Error updating account details: ' . $exception->getMessage(), false);
                $this->_with($_POST)->_response->redirect(AccountController::class, 'my_account');
            }

        }

    }


    /**
     * View the profile of a user
     * @param int $id ID of user to view profile of
     */
    public function view_user($id = 0)
    {

        $user = $this->_model->checkViewableUser($id);

        if ($user === false)
        {
            $this->_message('Invalid user', false);
            $this->_response->redirect(AdminController::class, 'users');
        }

        else
        {
            $this->_setVariable(compact('user'));
        }

    }


    /**
     * Tell the server that we are still logged in and should show up on the
     * 'Online Users' list
     */
    public function ping()
    {

        /*
         * Make a note that we were here
         */
        Me::setLastOnline(time());

        /*
         * Get a list of others who are also online and output them
         */
        $this->_render(false);
        $this->_response->setContentType('json');

        $this->_response->body = json_encode(Me::getOnline());

    }


}