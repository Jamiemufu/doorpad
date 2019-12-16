<?php


namespace Whiskey\Bourbon\App\Migration;


use stdClass;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;


/**
 * Job_1477493474 migration class
 * @package Whiskey\Bourbon\App\Migration
 */
class Job_1477493474 extends Job
{


    /**
     * Description of the migration's purpose
     * @var string
     */
    public $description = '';
    
    
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
    public function up() {}


    /**
     * Undo the migration
     */
    public function down() {}


}