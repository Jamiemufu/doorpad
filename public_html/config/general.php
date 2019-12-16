<?php


namespace Whiskey\Bourbon\Config;


use Whiskey\Bourbon\Config\Type\General;
use Itg\ErrorReport;


$config = new General();


/*
 * Miscellaneous application settings
 */
$config->set('site_name', 'Admin Panel');


/*
 * Define a key for use in hashing and encryption -- this should be unique for
 * each project
 */
$config->set('project_key', 'aa54ff585509c0e27aacfc69542f440bbba82e14d08f6180fbd88a48ad26c66a653a80f83bf7e2d3a7e1cb961d5d6b1a4c0bbb886b518e6ee0514aa64a48dc07');


/*
 * Local timezone (consult http://php.net/manual/en/timezones.php if unsure)
 */
$config->set('timezone', 'Europe/London');


/*
 * Name of application environment
 */
$config->set('environment', $_ENV['APP_ENVIRONMENT']);


/*
 * Whether to enable debug settings if not in the production environment
 */
$config->set('debug', $_ENV['APP_DEBUG']);


/*
 * Register the ITG API error logger
 */
if ($_ENV['APP_ENVIRONMENT'] == 'production' AND isset($_ENV['ITG_API_KEY']))
{
    ErrorReport::init($_ENV['ITG_API_KEY']);
}


/*
 * Give us more memory and time to play with
 */
ini_set('memory_limit',      '256M');
ini_set('max_execution_time', 300);


return $config;