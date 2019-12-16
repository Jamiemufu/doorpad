<?php


namespace Whiskey\Bourbon;


use Exception;
use Whiskey\Bourbon\App\Facade\Migration;
use Whiskey\Bourbon\App\Facade\Utils;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\Dashboard\Model\WhiskeyDashboardModel;


/**
 * Cli class
 * @package Whiskey\Bourbon
 */
class Cli
{


    protected $_handle  = null;
    protected $_flag    = null;
    protected $_request = null;


    protected static $_cli_path = '_whsky/cli';


    /**
     * Instantiate the CLI and listen for standard input
     */
    public function __construct()
    {

        $this->_handle  = fopen('php://stdin', 'r');
        $this->_request = Instance::_retrieve(Request::class);

        /*
         * Deal with flags
         */
        switch ($this->_getFlag())
        {

            case 'migrate-up':
                $this->_runMigrations('up', true);
                break;

        }

        /*
         * Show the menu
         */
        $this->_showOptions();

    }


    /**
     * Clean up the CLI
     */
    public function __destruct()
    {

        fclose($this->_handle);

        $this->_clearScreen();

    }


    /**
     * Get the CLI flag
     * @return string CLI flag (final URL slug fragment)
     */
    protected function _getFlag()
    {

        if (is_null($this->_flag))
        {
            $this->_flag = trim($this->_request->url, '/');
            $this->_flag = explode('/', $this->_flag);
            $this->_flag = end($this->_flag);
            $this->_flag = strtolower($this->_flag);
        }

        return $this->_flag;

    }


    /**
     * Get the URL fragment to access the CLI
     * @return string URL path
     */
    public static function getPath()
    {

        return static::$_cli_path;

    }


    /**
     * Attempt to clear the terminal window
     */
    protected function _clearScreen()
    {

        /*
         * Windows
         */
        if (stristr(PHP_OS, 'win') AND
            !stristr(PHP_OS, 'darwin'))
        {
            passthru('cls');
        }

        /*
         * *NIX
         */
        else
        {
            passthru('clear');
        }

    }


    /**
     * Display a message to the user
     * @param string $message Message to display
     */
    protected function _displayMessage($message = '')
    {

        print $message . "\n";

    }


    /**
     * Get user input
     * @return string User input
     */
    protected function _getUserInput()
    {

        return trim(fgets($this->_handle, 1024));

    }


    /**
     * Display a message and get the user's response
     * @param  string $message Message to display
     * @return string          User's response
     */
    protected function _talkToUser($message = '')
    {

        $this->_displayMessage($message);

        return $this->_getUserInput();

    }


    /**
     * Show options menu to the user
     */
    protected function _showOptions()
    {

        $this->_clearScreen();

        $migration_warning = false;

        try
        {
            $migration_warning = Migration::areJobsOutstanding();
        }

        catch (Exception $exception) {}

        if ($migration_warning)
        {
            $migration_warning = "\n\n## NOTICE: There are outstanding migrations; please consider running them";
        }

        $choice = (int)$this->_talkToUser("Welcome to the Whiskey CLI\n\nWhat would you like to do?" . $migration_warning . "\n\n1) Run latest migrations\n2) View active server modules\n3) View system information\n4) Generate project key\n5) Exit\n");

        switch ($choice)
        {

            case 1:
                $this->_runMigrations('up');
                break;

            case 2:
                $this->_viewActiveServerModules();
                break;

            case 3:
                $this->_viewSystemInformation();
                break;

            case 4:
                $this->_generateProjectKey();
                break;

            case 5:
                break;

            default:
                $this->_showOptions();
                break;

        }

    }


    /**
     * View active server modules
     */
    protected function _viewActiveServerModules()
    {

        $this->_clearScreen();

        $dashboard_model = Instance::_retrieve(WhiskeyDashboardModel::class);
        $modules         = $dashboard_model->getExtensionStatuses();
        $active_modules  =  "Active Server Modules\n";

        foreach ($modules as $name => $state)
        {
            $active_modules .= "\n[" . ($state ? '*' : ' ') . "] " . $name;
        }

        $this->_talkToUser($active_modules . "\n");

        /*
         * Return to the menu
         */
        $this->_showOptions();

    }


    /**
     * View system information
     */
    protected function _viewSystemInformation()
    {

        $this->_clearScreen();

        $dashboard_model = Instance::_retrieve(WhiskeyDashboardModel::class);
        $information     = $dashboard_model->getEnvironmentalInformation();
        $system_info     =  "System Information\n";

        foreach ($information as $key => $value)
        {
            $system_info .= "\n" . $key . ": " . $value;
        }

        $this->_talkToUser($system_info . "\n");

        /*
         * Return to the menu
         */
        $this->_showOptions();

    }


    /**
     * Apply migrations
     * @param string $direction Direction in which to apply migrations ('up' or 'down')
     * @param bool   $exit      Whether to exit after applying migrations
     */
    protected function _runMigrations($direction = 'up', $exit = false)
    {

        $this->_clearScreen();

        $direction = (strtolower($direction) == 'up') ? 'up' : 'down';

        /*
         * Run migrations up
         */
        if ($direction == 'up')
        {

            try
            {

                $migrations        = Migration::getAll();
                $migrations        = array_reverse($migrations);
                $latest_migration  = Migration::getLatest();
                $migrations_to_run = [];

                /*
                 * Get migrations that haven't been run yet
                 */
                foreach ($migrations as $migration)
                {

                    if ($migration->getId() > $latest_migration->getId())
                    {
                        $migrations_to_run[] = $migration;
                    }

                }

                /*
                 * Check if there are any migrations to run
                 */
                if (empty($migrations_to_run))
                {
                    if ($exit) { exit; }
                    $this->_talkToUser("No migrations to run\n");
                    $this->_showOptions();
                    return;
                }

                else
                {

                    /*
                     * Run each migration
                     */
                    foreach ($migrations_to_run as $migration)
                    {

                        $migration_name = ($migration->description != '') ? $migration->description : $migration->getId();

                        /*
                         * Per-migration success
                         */
                        try
                        {
                            Migration::run($migration->getId());
                            $this->_displayMessage('Success: ' . $migration_name);
                        }

                        /*
                         * Per-migration failure -- alert the user and return to
                         * the menu
                         */
                        catch (Exception $exception)
                        {
                            if ($exit) { exit; }
                            $this->_displayMessage('Failure: ' . $migration_name);
                            $this->_talkToUser("\nErrors encountered when attempting to run migrations\n");
                            $this->_showOptions();
                            return;
                        }

                    }

                }

                /*
                 * If all migrations were successful
                 */
                if ($exit) { exit; }
                $this->_talkToUser("\nMigrations successfully run");

            }

            /*
             * Miscellaneous errors (such as PHP parse errors in migration
             * classes)
             */
            catch (Exception $exception)
            {
                if ($exit) { exit; }
                $this->_talkToUser('Unable to run migrations: ' . $exception->getMessage() . "\n");
            }

        }

        /*
         * Return to the menu
         */
        $this->_showOptions();

    }


    /**
     * Generate and (if possible) save a new project key
     */
    protected function _generateProjectKey()
    {

        $this->_clearScreen();

        /*
         * Generate a new project key
         */
        $project_key      = hash('sha512', Utils::random(512));
        $config_file_path = Bourbon::getInstance()->getConfigDirectory() . 'general.php';
        $write_success    = false;

        /*
         * Attempt to update and save the existing project key in the 'general'
         * configuration file
         */
        if (is_writable($config_file_path))
        {

            $config_file = file_get_contents($config_file_path);
            $pattern     = '/\\$config->set\\([\'|"]project_key[\'|"], *[\'|"](.*)[\'|"]\\);/';
            $replacement = '$config->set(\'project_key\', \'' . $project_key . '\');';
            $config_file = preg_replace($pattern, $replacement, $config_file);

            $write_success = (file_put_contents($config_file_path, $config_file) !== false);

        }

        $additional_message = $write_success ? "\nYou do not need to do anything — the project's general configuration file has been updated automatically\n" : "\nPlease copy and paste this key into the project's 'general' configuration file\n";

        $this->_talkToUser("A new project key has been generated:\n\n" . $project_key . "\n" . $additional_message);

        /*
         * Return to the menu
         */
        $this->_showOptions();

    }


}