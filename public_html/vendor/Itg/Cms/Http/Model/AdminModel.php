<?php


namespace Itg\Cms\Http\Model;


use Whiskey\Bourbon\App\Http\MainModel;
use Itg\Buildr\Facade\Me;
use Whiskey\Bourbon\App\Facade\AppEnv;
use Itg\Cms\Http\Controller\PageController;


/**
 * AdminModel class
 * @package Whiskey\Bourbon\App\Http\Model
 */
class AdminModel extends MainModel
{


    /**
     * Stop non-admin users from accessing any administration pages and prevent
     * CSRF attacks
     */
    public function _before()
    {

        $whitelist = ['users', 'create_user', 'edit_user', 'delete_user'];

        /*
         * Admin-only sections
         */
        if (in_array(AppEnv::action(), $whitelist) AND !Me::isAdmin())
        {
            $this->_response->redirect(PageController::class, 'dashboard');
        }

        /*
         * Superuser-only sections
         */
        else if (!in_array(AppEnv::action(), $whitelist) AND !Me::isSuperUser())
        {
            $this->_response->redirect(PageController::class, 'dashboard');
        }

    }


}