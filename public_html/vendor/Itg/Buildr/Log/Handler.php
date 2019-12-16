<?php


namespace Itg\Buildr\Log;


use Exception;
use stdClass;
use Itg\Buildr\User\User;
use Itg\Buildr\User\Me;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;


/**
 * Log Handler class
 * @package Itg\Buildr\Log
 */
class Handler
{


    protected $_dependencies = null;
    protected $_exceptions   = [];
    
    
    /**
     * Instantiate the Log object
     * @param Me $me Me object
     * @param Db $db Db object
     * @throws Exception if dependencies are not provided
     */
    public function __construct(Me $me, Db $db)
    {

        if (!isset($me) OR
            !isset($db))
        {
            throw new Exception('Dependencies not provided');
        }

        $this->_dependencies     = new stdClass();
        $this->_dependencies->me = $me;
        $this->_dependencies->db = $db;

    }


    /**
     * Register an action which should not be logged
     * @param string $controller Controller
     * @param string $action     Action
     */
    public function registerException($controller = '', $action = '')
    {

        $this->_exceptions[] = json_encode([$controller, $action]);

    }


    /**
     * Log a hit
     * @param  string $controller Controller
     * @param  string $action     Action
     * @param  array  $slugs      Slug(s)
     * @param  User   $user       User object
     * @return bool                   Whether the action was successfully logged
     */
    public function logHit($controller = '', $action = '', array $slugs = [], User $user)
    {

        $action_string = json_encode([$controller, $action]);

        if (in_array($action_string, $this->_exceptions))
        {
            return false;
        }

        try
        {

            $this->_dependencies->db->build()->table('logs')
                                             ->data('controller', $controller)
                                             ->data('action', $action)
                                             ->data('slug', implode('/', $slugs))
                                             ->data('user_id', $user->getId())
                                             ->data('date', time())
                                             ->insert();

            return true;

        }

        catch (Exception $exception)
        {
            return false;
        }

    }


}