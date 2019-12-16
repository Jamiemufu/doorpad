<?php


namespace Whiskey\Bourbon\Dashboard\Controller;


use Exception;
use Whiskey\Bourbon\App\Facade\Cache;
use Whiskey\Bourbon\App\Facade\Cron;
use Whiskey\Bourbon\App\Facade\Input;
use Whiskey\Bourbon\App\Facade\Migration;
use Whiskey\Bourbon\App\Http\MainController;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;


class WhiskeyDashboardController extends MainController
{


    protected $_layout_file = 'whiskey_dashboard.ice.php';
    protected $_bourbon     = null;


    /**
     * Carry out logic common to all Dashboard routes
     */
    public function _init()
    {

        /*
         * Serve up a 404 header, to make non-humans ignore this page (in
         * addition to the robots <meta> element and header that is served)
         */
        $this->_response->notFound(false);

        /*
         * Get a reference to the bootstrapper
         */
        $this->_bourbon = Bourbon::getInstance();

    }


    /**
     * Dashboard 'Info' page
     */
    public function info()
    {

        $active_page        = 'info';
        $extensions         = $this->_model->getExtensionStatuses();
        $databases          = $this->_model->getDatabaseStatuses();
        $templating_engines = $this->_model->getTemplatingEngineStatuses();
        $storage_engines    = $this->_model->getStorageEngineStatuses();
        $caching_engines    = $this->_model->getCachingEngineStatuses();
        $email_engines      = $this->_model->getEmailEngineStatuses();
        $random_sources     = $this->_model->getRandomSourceStatuses();
        $environment        = $this->_model->getEnvironmentalInformation();
        $cache_dir          = $this->_bourbon->getDataCacheDirectory();
        $cache_clearable    = (is_readable($cache_dir) AND is_writable($cache_dir));

        $this->_setVariable(compact('active_page', 'extensions', 'databases', 'templating_engines',
                                    'storage_engines', 'caching_engines', 'email_engines',
                                    'random_sources', 'environment', 'cache_clearable'));

    }


    /**
     * Clear all caches
     */
    public function clear_caches()
    {

        $this->_render(false);

        Cache::clearAll();

        $this->_response->redirect($this->_link(static::class, 'info') . '#cache_engines');

    }


    /**
     * Dashboard 'Migrations' page
     */
    public function migrations()
    {

        $active_page        = 'migrations';
        $new_migration      = Input::get('new_migration');
        $migrations_reset   = Input::get('migrations_reset');
        $base_dir           = $this->_bourbon->getBaseDirectory();
        $migration_dir      = $this->_bourbon->getMigrationDirectory();
        $migration_dir      = $migration_dir = mb_substr($migration_dir, mb_strlen($base_dir));
        $skipped_migrations = Migration::getSkipped();
        $skipped_migrations = array_map(function($migration)
        {
            return $migration->getId();
        }, $skipped_migrations);

        try
        {
            $migrations = Migration::getAll();
        }

        catch (Exception $exception)
        {
            $migrations = [];
        }

        try
        {
            $latest_migration = Migration::getLatest()->getId();
        }
        
        catch (Exception $exception)
        {
            $latest_migration = 0;
        }

        $migrations_enabled = Migration::isActive();

        $this->_setVariable(compact('active_page', 'new_migration', 'migration_dir',
                                    'migrations_reset', 'migrations_enabled', 'migrations',
                                    'latest_migration', 'skipped_migrations'));

    }


    /**
     * Create a migration
     */
    public function create_migration()
    {

        $this->_render(false);

        try
        {

            $migration_name = Migration::create();
            $migration_name = explode('.', $migration_name);
            $migration_name = array_shift($migration_name);

            $this->_response->redirect($this->_link(static::class, 'migrations') . '?migrations&new_migration=' . $migration_name);

        }

        catch (Exception $exception)
        {
            $this->_response->redirect(static::class, 'migrations');
        }

    }


    /**
     * Reset the migration index
     */
    public function reset_migrations()
    {

        $this->_render(false);

        Migration::reset();

        $this->_response->redirect($this->_link(static::class, 'migrations') . '?migrations&migrations_reset');

    }


    /**
     * Action all migrations up to (and including) a specified migration
     * @param int $migration_id ID of migration to migrate towards
     */
    public function migrate_to($migration_id = 0)
    {

        $this->_render(false);

        Migration::run((int)$migration_id);

        $this->_response->redirect($this->_link(static::class, 'migrations') . '#migrations_list');

    }


    /**
     * Action a specified migration
     * @param int $migration_id ID of migration to action
     */
    public function action_migration($migration_id = 0)
    {

        $this->_render(false);

        Migration::action((int)$migration_id, true);

        $this->_response->redirect($this->_link(static::class, 'migrations') . '#migrations_list');

    }


    /**
     * Dashboard 'Cron' page
     */
    public function cron()
    {

        $active_page = 'cron';
        $cron_active = Cron::isActive();
        $cron_jobs   = $cron_active ? Cron::getAll() : [];

        $this->_setVariable(compact('active_page', 'cron_active', 'cron_jobs'));

    }


    /**
     * Add a cron job
     */
    public function cron_add()
    {

        $this->_render(false);

        $minute      = Input::post('minute', false);
        $hour        = Input::post('hour', false);
        $day         = Input::post('day', false);
        $month       = Input::post('month', false);
        $day_of_week = Input::post('day_of_week', false);
        $command     = Input::post('command', false);

        if (!is_null($minute) AND !is_null($hour) AND !is_null($day) AND !is_null($month) AND !is_null($day_of_week) AND !is_null($command))
        {
            Cron::add($minute, $hour, $day, $month, $day_of_week, $command);
        }

        $this->_response->redirect(static::class, 'cron');
        
    }


    /**
     * Delete a cron job
     */
    public function cron_delete()
    {

        $this->_render(false);

        $cron_job = Input::post('cron_delete', false);
        $cron_job = explode(' ', $cron_job);

        if (count($cron_job) >= 6)
        {

            $minute      = array_shift($cron_job);
            $hour        = array_shift($cron_job);
            $day         = array_shift($cron_job);
            $month       = array_shift($cron_job);
            $day_of_week = array_shift($cron_job);
            $command     = implode(' ', $cron_job);

            Cron::remove($minute, $hour, $day, $month, $day_of_week, $command);

        }

        $this->_response->redirect(static::class, 'cron');
        
    }


}