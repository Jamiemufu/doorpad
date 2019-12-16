<?php


namespace Itg\Cms\Http\Controller;


use Exception;
use Whiskey\Bourbon\Instance;
use Itg\Buildr\Facade\Me;
use Itg\Buildr\Facade\User;
use Itg\Buildr\Facade\DbBackup;
use Itg\Buildr\Facade\FullBackup;
use Whiskey\Bourbon\App\Http\MainController;
use Whiskey\Bourbon\App\Facade\Migration;
use Whiskey\Bourbon\App\Facade\Input;
use Itg\Cms\Http\Model\AccountModel;
use Whiskey\Bourbon\App\Facade\Validation;


/**
 * AdminController class
 * @package Itg\Cms\Http\Controller
 */
class AdminController extends MainController
{


    protected $_layout_file = 'cms.ice.php';


    /**
     * Show a list of users that the current user can interact with
     */
    public function users()
    {

        $users = json_decode(json_encode(Me::getPaginated()));

        $this->_setVariable(compact('users'));

    }


    /**
     * Show the 'create user' form (and create a user if it has been submitted)
     */
    public function create_user()
    {

        /*
         * Create a user
         */
        if (isset($_POST['username']))
        {

            /*
             * First, check that the user isn't trying to elevate their privileges
             */
            $account_model = Instance::_retrieve(AccountModel::class);

            if (!$account_model->isUserRelated(Input::post('parent_id')))
            {
                $this->_message('Invalid parent user', false);
                $this->_response->redirect(AdminController::class, 'users');
            }

            /*
             * Then check to see whether the user already exists
             */
            try
            {
                $user_already_exists = User::getByUsername(Input::post('username'));
            }

            catch (Exception $exception)
            {
                $user_already_exists = false;
            }

            $validator = Validation::build();

            $validator->add('username')->type('MIN_LENGTH')->compare(4)->errorMessage('Username is too short &#40;4 character minimum&#41;')->required();
            if ($user_already_exists !== false)
            {
                $validator->add('username')->type('IS')->compare('')->errorMessage('User \'' . Input::post('username') . '\' already exists')->required();
            }
            $validator->add('email')->type('EMAIL')->errorMessage('E-mail address is not valid')->required();
            $validator->add('password')->type('MIN_LENGTH')->compare(User::PASSWORD_MIN_LENGTH)->errorMessage('Password is too short &#40;' . User::PASSWORD_MIN_LENGTH . ' character minimum&#41;')->required();
            $validator->add('role')->type('NUM')->errorMessage('Invalid user role selection')->required();
            $validator->add('role')->type('GREATER_THAN')->compare(Me::getRole())->errorMessage('Invalid user role selection')->required();
            $validator->add('parent_id')->type('NUM')->errorMessage('Invalid parent user selection')->required();

            if ($validator->failed())
            {
                $validator->showErrors();
                $this->_with($_POST)->_response->redirect(AdminController::class, 'create_user');
            }

            $username  = Input::post('username');
            $password  = Input::post('password');
            $email     = Input::post('email');
            $role      = Input::post('role');
            $parent_id = Input::post('parent_id');

            try
            {

                $user = User::create($username);

                $user->updatePassword($password);
                $user->setEmail($email);
                $user->setRole($role);
                $user->setParentId($parent_id);

                $this->_message('User &#39;' . $username . '&#39; successfully created');
                $this->_response->redirect(AdminController::class, 'users');

            }

            catch (Exception $exception)
            {
                $this->_message('Error creating user: ' . $exception->getMessage(), false);
                $this->_with($_POST)->_response->redirect(AdminController::class, 'create_user');
            }

        }

        /*
         * Not creating a user, so just get a list of users and roles to show
         * the form
         */
        $users      = Me::getRelatedUsers();
        $user_roles = Me::getRoles();

        $this->_setVariable(compact('users', 'user_roles'));

    }


    /**
     * Show the 'edit user' form (and edit the user if it has been submitted)
     * @param int $id ID of user to edit
     */
    public function edit_user($id = 0)
    {

        /*
         * Check that we're allowed to be here
         */
        $account_model = Instance::_retrieve(AccountModel::class);
        $can_edit_user = $account_model->checkViewableUser($id);

        if ($can_edit_user === false)
        {
            $this->_message('Invalid user', false);
            $this->_response->redirect(AdminController::class, 'users');
        }

        /*
         * Update the user details
         */
        if (isset($_POST['email']) AND $id)
        {

            /*
             * First, check that the user isn't trying to elevate their privileges
             */
            if (!$account_model->isUserRelated(Input::post('parent_id'), [$id]))
            {
                $this->_message('Invalid parent user', false);
                $this->_response->redirect(AdminController::class, 'users');
            }

            $validator = Validation::build();

            $validator->add('email')->type('EMAIL')->errorMessage('E-mail address is not valid')->required();
            if (Input::post('password') != '')
            {
                $validator->add('password')->type('MIN_LENGTH')->compare(User::PASSWORD_MIN_LENGTH)->errorMessage('Password is too short &#40;' . User::PASSWORD_MIN_LENGTH . ' character minimum&#41;')->required();
            }
            $validator->add('role')->type('NUM')->errorMessage('Invalid user role selection')->required();
            $validator->add('role')->type('GREATER_THAN')->compare(Me::getRole())->errorMessage('Invalid user role selection')->required();
            $validator->add('parent_id')->type('NUM')->errorMessage('Invalid parent user selection')->required();

            if ($validator->failed())
            {
                $validator->showErrors();
                $this->_with($_POST)->_response->redirect(AdminController::class, 'edit_user', $id);
            }

            $password  = Input::post('password');
            $email     = Input::post('email');
            $role      = Input::post('role');
            $parent_id = Input::post('parent_id');

            try
            {

                $user = $can_edit_user;

                $user->setEmail($email);
                $user->setRole($role);
                $user->setParentId($parent_id);

                if (Input::post('password') != '')
                {
                    $user->updatePassword($password);
                }

                $this->_message('User &#39;' . $user->getUsername() . '&#39; successfully updated');
                $this->_response->redirect(AdminController::class, 'users');

            }

            catch (Exception $exception)
            {
                $this->_message('Error updating user: ' . $exception->getMessage(), false);
                $this->_with($_POST)->_response->redirect(AdminController::class, 'edit_user', $id);
            }

        }

        /*
         * Show the user details, if not carrying out an edit
         */
        $user       = $can_edit_user;
        $users      = Me::getRelatedUsers([$id]);
        $user_roles = Me::getRoles();

        $this->_setVariable(compact('user', 'users', 'user_roles'));

    }


    /**
     * Delete a user
     * @param int $id ID of user to delete
     */
    public function delete_user($id = 0)
    {

        $this->csrfCheckGet();

        $this->_render(false);

        $account_model   = Instance::_retrieve(AccountModel::class);
        $can_delete_user = $account_model->checkViewableUser($id);

        if ($can_delete_user === false)
        {
            $this->_message('Invalid user', false);
        }

        else
        {

            $user    = $can_delete_user;
            $success = $user->delete();

            if ($success)
            {
                $this->_message('User successfully deleted');
            }

            else
            {
                $this->_message('Could not delete user &mdash; please try again', false);
            }

        }

        $this->_response->redirect(AdminController::class, 'users');

    }


    /**
     * Show a list of full site backups (or perform an action upon one)
     * @param string $action Optional action tag
     * @param string $file   Optional backup file name (for action)
     */
    public function backups_site($action = '', $file = '')
    {

        /*
         * If we have requested a new backup to be created
         */
        if ($action == 'create')
        {

            $success = FullBackup::create();

            if ($success)
            {
                $this->_message('Full site backup successfully created');
            }

            else
            {
                $this->_message('Could not create full site backup &mdash; please try again', false);
            }

            $this->_response->redirect(AdminController::class, 'backups_site');

        }

        /*
         * If a download has been requested
         */
        if ($action == 'download' AND $file)
        {

            $success = FullBackup::download($file);

            if ($success)
            {
                exit;
            }

            else
            {
                $this->_message('Could not download full site backup &mdash; please try again', false);
                $this->_response->redirect(AdminController::class, 'backups_site');
            }

        }

        /*
         * If a deletion has been requested
         */
        if ($action == 'delete' AND $file)
        {

            $this->csrfCheckGet();

            $success = FullBackup::delete($file);

            if ($success)
            {
                $this->_message('Full site backup successfully deleted');
            }

            else
            {
                $this->_message('Could not delete full site backup &mdash; please try again', false);
            }

            $this->_response->redirect(AdminController::class, 'backups_site');

        }

        /*
         * Otherwise just show a list
         */
        $backups = FullBackup::getAll();

        $this->_setVariable(compact('backups'));

    }


    /**
     * Show a list of database backups (or perform an action upon one)
     * @param string $action Optional action tag
     * @param string $file   Optional backup file name (for action)
     */
    public function backups_database($action = '', $file = '')
    {

        /*
         * If we have requested a new backup to be created
         */
        if ($action == 'create')
        {

            $success = DbBackup::create();

            if ($success)
            {
                $this->_message('Database backup successfully created');
            }

            else
            {
                $this->_message('Could not create database backup &mdash; please try again', false);
            }

            $this->_response->redirect(AdminController::class, 'backups_database');

        }

        /*
         * If a download has been requested
         */
        if ($action == 'download' AND $file)
        {

            $success = DbBackup::download($file);

            if ($success)
            {
                exit;
            }

            else
            {
                $this->_message('Could not download database backup &mdash; please try again', false);
                $this->_response->redirect(AdminController::class, 'backups_database');
            }

        }

        /*
         * If a deletion has been requested
         */
        if ($action == 'delete' AND $file)
        {

            $this->csrfCheckGet();

            $success = DbBackup::delete($file);

            if ($success)
            {
                $this->_message('Database backup successfully deleted');
            }

            else
            {
                $this->_message('Could not delete database backup &mdash; please try again', false);
            }

            $this->_response->redirect(AdminController::class, 'backups_database');

        }

        /*
         * Otherwise just show a list
         */
        $backups = DbBackup::getAll();

        $this->_setVariable(compact('backups'));

    }


    /**
     * Show a list of application migrations
     */
    public function migrations()
    {

        $migrations = Migration::getAll();

        try
        {
            $latest_migration = Migration::getLatest()->getId();
        }

        catch (Exception $exception)
        {
            $latest_migration = 0;
        }

        $this->_setVariable(compact('migrations', 'latest_migration'));

    }


    /**
     * Action a migration
     * @param int $id Migration ID
     */
    public function migrate_to($id = 0)
    {

        $this->csrfCheckGet();

        $this->_render(false);

        try
        {
            $success = Migration::run($id);
        }

        catch (Exception $exception)
        {
            $success = false;
        }

        if ($success)
        {
            $this->_message('Migration successfully applied');
        }

        else
        {
            $this->_message('An error occurred attempting to apply the migration &mdash; please try again', false);
        }

        $this->_response->redirect(AdminController::class, 'migrations');

    }


}