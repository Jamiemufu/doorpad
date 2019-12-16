<?php


namespace Itg\Buildr\User;


use Exception;
use Whiskey\Bourbon\Auth\Handler as Auth;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;
use Whiskey\Bourbon\Security\Password;
use Whiskey\Bourbon\Helper\Utils;


/**
 * Me class
 * @package Itg\Buildr\User
 */
class Me extends User
{


    const _NO_REUSE = false;


    /**
     * Instantiate the Me object
     * @param Auth     $auth     Auth object
     * @param Password $password Password object
     * @param Db       $db       Db object
     * @param Utils    $utils    Utils object
     * @throws Exception if dependencies are not provided
     */
    public function __construct(Auth $auth, Password $password, Db $db, Utils $utils)
    {

        if (!isset($auth) OR
            !isset($password) OR
            !isset($db) OR
            !isset($utils))
        {
            throw new Exception('Dependencies not provided');
        }

        try
        {

            parent::__construct($auth, $password, $db, $utils);

            $auth_id = isset($auth->details()->id) ? $auth->details()->id : 0;

            if ($auth_id)
            {
                $this->setId($auth_id);
            }

        }

        catch (Exception $exception) {}

    }


}