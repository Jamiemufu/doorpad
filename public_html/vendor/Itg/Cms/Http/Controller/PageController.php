<?php


namespace Itg\Cms\Http\Controller;


use Exception;
use Itg\Buildr\Search;
use Itg\Buildr\User\User;
use Itg\Cms\Http\Model\AccountModel;
use Itg\Cms\Http\Model\PageModel;
use Itg\DoorPad\Visitor;
use Whiskey\Bourbon\App\Http\MainController;
use Whiskey\Bourbon\App\Facade\Auth;
use Whiskey\Bourbon\App\Facade\Hooks;
use Whiskey\Bourbon\App\Facade\Input;
use Whiskey\Bourbon\App\Facade\Session;
use Whiskey\Bourbon\Instance;


/**
 * PageController class
 * @package Itg\Cms\Http\Controller
 */
class PageController extends MainController
{


    protected $_layout_file = 'cms.ice.php';


    /**
     * Show a login (or unlock) page
     */
    public function login()
    {

        /*
         * Redirect users who are already logged in
         */
        if (Auth::isLoggedIn())
        {
            $this->_response->redirect(PageController::class, 'dashboard');
        }

        /*
         * See who was last logged in and show them a page to re-log in
         */
        $user_id = Session::read('last_logged_in_user_id');

        if ($user_id)
        {

            try
            {
                $user = Instance::_retrieve(User::class);
                $user->setId($user_id);
                $this->_render(null, 'unlock.ice.php');
                $this->_setVariable(compact('user'));
            }

            catch (Exception $exception)
            {
                $this->_render(null, 'login.ice.php');
            }

        }

        /*
         * Otherwise just show the normal login page
         */
        else
        {
            $this->_render(null, 'login.ice.php');
        }

    }


    /**
     * Attempt a login
     */
    public function login_attempt()
    {

        $this->_render(false);

        $username = Input::post('username');
        $password = Input::post('password');

        $account_model = Instance::_retrieve(AccountModel::class);

        $success = $account_model->attemptLogin($username, $password);

        if (!$success AND !is_null($username) AND !is_null($password))
        {
            $this->_message('Invalid username or password', false);
        }

        $this->_response->redirect(PageController::class, 'dashboard');

    }


    /**
     * Log out the logged-in user
     */
    public function logout()
    {

        $this->_render(false);

        Auth::logOut();

        $this->_response->redirect(PageController::class, 'login');

    }


    /**
     * Show the dashboard
     */
    public function dashboard() {}


    /**
     * Perform a search based upon the contents of $_GET['keywords'] using
     * hooked search listeners
     */
    public function search()
    {

        $search_terms = Input::get('keywords', false);
        $search_terms = trim($search_terms);

        if (is_null($search_terms) OR $search_terms == '')
        {
            $this->_message('Please enter some search terms and try again', false);
            $this->_response->redirect(PageController::class, 'dashboard');
        }

        $results = new Search($this, $search_terms);

        Hooks::broadcast('search', $results);

        $this->_setVariable(compact('results'));

    }

    /**
     * Show sign out visitors page
     */
    public function sign_out() {}


    /**
     * Show reports page
     */
    public function reports() {}


    /**
     * Download reports
     */
    public function downloadReports()
    {

        $this->_render(false);

        if ($_POST)
        {
            $start_date = Input::post('start_date');
            $end_date = Input::post('end_date');

            if($start_date > $end_date || $end_date < $start_date)
            {
                $this->_message('Please enter a valid date range', false);
                $this->_response->redirect(PageController::class, 'reports');
            }
            else
            {
                 // get visitors from dates
                $data = Visitor::getVisitorsDate($start_date, $end_date);

                if(!empty($data))
                {   
                    $page_model = Instance::_retrieve(PageModel::class);
                    $page_model->downloadCSV($data);
                    $this->_response->redirect(PageController::class, 'reports');
                }
                else
                {
                    $this->_message('There are no visitors from the currently selected dates', false);
                    $this->_response->redirect(PageController::class, 'reports');
                }   
            }
        }
        
    }


    /**
     * Sign out all currently signed in visitors
     */
    public function signOutAll()
    {

        $this->_render(false);
        Visitor::signOutAllVisitors();

        $this->_message('All signed in visitors have been signed out', false);
        $this->_response->redirect(PageController::class, 'sign_out');

    }

    /**
     * Show not found error 404 page
     */
    public function four_oh_four()
    {

        $this->_response->notFound(false);
        $this->_render(null, 'error.ice.php');

    }


    /**
     * Show internal error 500 page
     */
    public function five_hundred()
    {

        $this->_response->fatalError(false);
        $this->_render(null, 'error.ice.php');

    }


}