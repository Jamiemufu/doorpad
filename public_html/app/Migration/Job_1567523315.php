<?php


namespace Whiskey\Bourbon\App\Migration;


use stdClass;
// use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;
use Whiskey\Bourbon\App\Facade\Db;


/**
 * Job_1567523315 migration class
 * @package Whiskey\Bourbon\App\Migration
 */
class Job_1567523315 extends Job
{


    /**
     * Description of the migration's purpose
     * @var string
     */
    public $description = 'Add signed in to table';
    
    
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
    public function up()
    {

        $carReg = array("field" => "carReg", "type" => "varchar", "length" => 255, "null" => false);
        $signedIn = array("field" => "signedIn", "type" => "int", "length" => 1, "null" => false, "default" => 0);
        $inTime = array("field" => "signedInTime", "type" => "datetime", "null" => false);
        $outTime = array("field" => "signedOutTime", "type" => "datetime", "null" => false);
       

        Db::addField("visitors", $carReg);
        Db::addField("visitors", $signedIn);
        Db::addField("visitors", $inTime);
        Db::addField("visitors", $outTime);
    }


    /**
     * Undo the migration
     */
    public function down()
    {
        Db::dropfield("visitors", "carReg");
        Db::dropfield("visitors", "signedIn");        
        Db::dropfield("visitors", "signedinTime");
        Db::dropfield("visitors", "signedoutTime");

    }


}