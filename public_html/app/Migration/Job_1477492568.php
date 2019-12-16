<?php


namespace Whiskey\Bourbon\App\Migration;


use stdClass;
//use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;

use Whiskey\Bourbon\App\Facade\Db;

/**
 * Job_1477492568 migration class
 * @package Whiskey\Bourbon\App\Migration
 */
class Job_1477492568 extends Job
{


    /**
     * Description of the migration's purpose
     * @var string
     */
    public $description = 'Create the visitors table';
    
    
    protected $_dependencies = null;
    
    
    /**
     * Instantiate the migration and gather dependencies
     * @param Db $db Db instance
     */
    public function __construct(Db $db)
    {
    
        $this->_dependencies     = new stdClass();
        $this->_dependencies->db = $db;
    
    }


    /**
     * Apply the migration
     */
    public function up() {

        /*
         * Set up the 'users' table
         */
        Db::buildSchema()->table('visitors')
                         ->autoId()
                         ->varChar('first_name', '')
                         ->varChar('last_name', '')
                         ->varChar('badge', '')
                         ->varChar('company', '')
                         ->varChar('visiting', '')
                         ->create();


    }


    /**
     * Undo the migration
     */
    public function down() {

        Db::drop('visitors');
    }


}