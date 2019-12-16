<?php


use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\App\Facade\Email;
use Whiskey\Bourbon\App\Facade\Csrf;
use Whiskey\Bourbon\App\Facade\Hooks;
use Whiskey\Bourbon\App\Facade\Response;
use Whiskey\Bourbon\App\Http\MainController;
use Itg\Email\Engine\Itg as ItgEmail;
use Itg\Cms\Loader as Cms;
use Itg\Buildr\Facade\DbBackup;
use Itg\Buildr\Facade\FullBackup;


/*
 * Any deferred logic (utilising the Hooks class) or application extensions
 * should reside here
 */


/*
 * Register the ITG API e-mail library -- uncomment the 'setDefaultEngine()'
 * line to use and ensure that a valid token for ITG_API_KEY is set in the .env
 * file -- ENABLE THIS IF HOSTING ON AMAZON AND SES IS NOT AVAILABLE
 */
Hooks::addListener('APP_PRE_ROUTING', function()
{
    Email::registerEngine(ItgEmail::class);
    // Email::setDefaultEngine('itg');
});


/*
 * Uncomment the below to enable the CMS module -- you may also wish to delete
 * the 404 and 500 routes/actions/views from the base system
 */
Hooks::addListener('APP_POST_CONFIGURATION', function()
{

    Bourbon::getInstance()->addTemplateDirectory(Cms::class);

    /*
     * If you don't want to show the search box, comment out or remove this whole
     * hooks section
     */
    Hooks::addListener('search', function($search)
    {

        // Methods available:
        //
        // $search->getSearchTerms()
        // $search->getSearchTermsString()
        // $search->add(HEADER, BODY, TARGET ROUTE, TAG)
        //
        // Only the header is required to add a search result; if passed, the body
        // will be shown with it in results, the route will make the header a link
        // and the tag will be used to filter search results
        //
        // The route can either be an array of controller/action/slugs or a URL
        // string

        // $search->add('Header 1', Utils::loremIpsum(350), [PageController::class, 'dashboard'], 'Tag 1');
        // $search->add('Header 2', Utils::loremIpsum(350), 'http://example.com', 'Tag 2');
        // $search->add('Header 3', Utils::loremIpsum(350), null, 'Tag 3');

    });

});


/*
 * Add custom Ice parsers
 */
require_once(Bourbon::getInstance()->getExtensionDirectory() . 'ice_parsers.php');


/*
 * Check for backups
 */
Hooks::addListener('APP_PRE_ROUTING', function()
{

    if ($_ENV['APP_ENVIRONMENT'] == 'production')
    {
        DbBackup::checkAndCreate();
        FullBackup::checkAndCreate();
    }

});


/*
 * Register controller helper methods
 */
MainController::_bindMethod('csrfCheckGet', function()
{

    if (!isset($_GET['csrf_token']) OR !Csrf::checkToken($_GET['csrf_token']))
    {
        Response::deny();
    }

});


MainController::_bindMethod('yearFrom', function($year = 0)
{

    if (!$year)
    {
        $year = date('Y');
    }

    if ((int)$year > (int)date('Y'))
    {
        throw new Exception('Year cannot be in the future');
    }

    $result = date('Y');

    if ($result != $year)
    {
        $result = $year . " - " . $result;
    }

    return $result;

});


MainController::_bindMethod('_ga', function($key = '', $force = false)
{

    if ($key != '' AND ($_ENV['APP_ENVIRONMENT'] == 'production' OR $force))
    {
        return "<script> (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){ (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) })(window,document,'script','//www.google-analytics.com/analytics.js','ga'); ga('create', '" . $key . "', 'auto'); ga('send', 'pageview'); </script>";
    }

    return '';

});