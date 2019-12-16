<?php


namespace Whiskey\Bourbon\App\Migration;


use Whiskey\Bourbon\Exception\App\FileWriteException;


/**
 * Migration Job class
 * @package Whiskey\Bourbon\App\Migration
 */
class Job
{


    const _NO_REUSE = true;


    /**
     * Description of the migration's purpose
     * @var string
     */
    public $description = '';


    /**
     * Template PHP migration Job file
     * @var string
     */
    protected static $_template = '<?php


namespace Whiskey\\Bourbon\\App\\Migration;


use stdClass;
use Whiskey\\Bourbon\\Storage\\Database\\Mysql\\Handler as Db;


/**
 * Job_X migration class
 * @package Whiskey\Bourbon\App\Migration
 */
class Job_X extends Job
{


    /**
     * Description of the migration\'s purpose
     * @var string
     */
    public $description = \'\';
    
    
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


}';


    /**
     * Create a new migration file
     * @param  string $base_directory Path to directory in which to save the migration file
     * @return string                 Migration file name
     * @throws FileWriteException if the migration could not be created
     */
    public static function create($base_directory = '')
    {

        $migration_id       = time();
        $migration_filename = 'Job_' . $migration_id . '.php';

        $migration_file_contents = str_replace('Job_X',
                                               'Job_' . $migration_id,
                                               static::$_template);

        $result = file_put_contents($base_directory . $migration_filename, $migration_file_contents);

        if ($result !== false)
        {
            return $migration_filename;
        }

        throw new FileWriteException('Migration could not be created');

    }


    /**
     * Get the migration ID
     * @return int Migration ID
     */
    public function getId()
    {

        $class_components = explode('_', get_called_class());

        return (int)end($class_components);

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