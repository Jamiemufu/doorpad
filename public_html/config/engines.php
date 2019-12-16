<?php


namespace Whiskey\Bourbon\Config;


use Psr\Log\LogLevel;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\Config\Type\Engines;
use Whiskey\Bourbon\Email\Engine\Ses;
use Whiskey\Bourbon\Email\Engine\SwiftMailer;
use Whiskey\Bourbon\Helper\Component\Captcha\Engine\Simple as SimpleCaptcha;
use Whiskey\Bourbon\Helper\Component\Captcha\Handler as CaptchaHandler;
use Whiskey\Bourbon\Helper\Component\Captcha\Engine\Recaptcha;
use Whiskey\Bourbon\Storage\Cache\Handler as CacheHandler;
use Whiskey\Bourbon\Storage\Cache\Engine\Memcached;
use Whiskey\Bourbon\Storage\Cache\Engine\Apc;
use Whiskey\Bourbon\Storage\Cache\Engine\File as FileCache;
use Whiskey\Bourbon\Storage\Meta\Handler as MetaHandler;
use Whiskey\Bourbon\Storage\Meta\Engine\Database as DatabaseMeta;
use Whiskey\Bourbon\Storage\Meta\Engine\File as FileMeta;
use Whiskey\Bourbon\Storage\File\Handler as FileHandler;
use Whiskey\Bourbon\Storage\File\Engine\S3 as S3Storage;
use Whiskey\Bourbon\Storage\File\Engine\File as FileStorage;
use Whiskey\Bourbon\Logging\Engine\Widget as WidgetLogger;
use Whiskey\Bourbon\Logging\Engine\Cli as CliLogger;
use Whiskey\Bourbon\Logging\Engine\File as FileLogger;
use Whiskey\Bourbon\Logging\Engine\Email as EmailLogger;
use Whiskey\Bourbon\Logging\Engine\BlackHole as NullLogger;
use Whiskey\Bourbon\Templating\Engine\Ice\Loader as Ice;
use Whiskey\Bourbon\Templating\Engine\Twig\Loader as Twig;
use Whiskey\Bourbon\Auth\Handler as AuthHandler;
use Whiskey\Bourbon\Auth\Oauth\Provider\Facebook;
use Whiskey\Bourbon\Auth\Oauth\Provider\Twitter;
use Whiskey\Bourbon\Auth\Oauth\Provider\Google;
use Whiskey\Bourbon\Auth\Oauth\Provider\Microsoft;
use Whiskey\Bourbon\Auth\Oauth\Provider\LinkedIn;
use Whiskey\Bourbon\Email\Handler as EmailHandler;
use Whiskey\Bourbon\App\Migration\Handler as Migration;
use Whiskey\Bourbon\App\Schedule\Handler as Schedule;
use Whiskey\Bourbon\Io\Http;
use Whiskey\Bourbon\Helper\Url;


$engines = new Engines();


/*
 * Map engine keys against the handler classes that the engines set in this file
 * should be registered with
 */
$engines->setHandlerMappings(
    [
        'cache'   => CacheHandler::class,
        'captcha' => CaptchaHandler::class,
        'email'   => EmailHandler::class,
        'meta'    => MetaHandler::class,
        'oauth'   => AuthHandler::class,
        'storage' => FileHandler::class
    ]);


/*
 * Caching engines
 */
$engines->set('cache', Memcached::class);
$engines->set('cache', Apc::class);
$engines->set('cache', FileCache::class);


/*
 * Captcha engines
 */
$engines->set('captcha', Recaptcha::class);
$engines->set('captcha', SimpleCaptcha::class);


/*
 * Email engines
 */
$engines->set('email', SwiftMailer::class);
$engines->set('email', Ses::class);


/*
 * Meta storage engines
 */
$engines->set('meta', DatabaseMeta::class);
$engines->set('meta', FileMeta::class);


/*
 * File storage engines
 */
$engines->set('storage', S3Storage::class);
$engines->set('storage', FileStorage::class);


/*
 * Error logging engines
 */
if ($_ENV['APP_ENVIRONMENT'] == 'production')
{
    $logger           = FileLogger::class;
    $emergency_logger = EmailLogger::class;
}

else
{
    $logger           = Bourbon::getInstance()->runningFromCli() ? CliLogger::class : WidgetLogger::class;
    $emergency_logger = NullLogger::class;
}

$engines->set('logging', [LogLevel::EMERGENCY => [$logger, $emergency_logger]]);
$engines->set('logging', [LogLevel::ALERT     => [$logger, $emergency_logger]]);
$engines->set('logging', [LogLevel::CRITICAL  => [$logger, $emergency_logger]]);
$engines->set('logging', [LogLevel::ERROR     => $logger]);
$engines->set('logging', [LogLevel::WARNING   => $logger]);
$engines->set('logging', [LogLevel::NOTICE    => $logger]);
$engines->set('logging', [LogLevel::INFO      => $logger]);
$engines->set('logging', [LogLevel::DEBUG     => $logger]);


/*
 * Templating engines
 */
$engines->set('templating', ['.ice.php'   => Ice::class]);
$engines->set('templating', ['.twig.html' => Twig::class]);


/*
 * OAuth engines
 */
$engines->set('oauth', Facebook::class);
$engines->set('oauth', Twitter::class);
$engines->set('oauth', Google::class);
$engines->set('oauth', Microsoft::class);
$engines->set('oauth', LinkedIn::class);


/*
 * Configuration
 */
$engines->config(S3Storage::class,   'connectToBucket',          [$_ENV['S3_BUCKET'], $_ENV['AWS_KEY'], $_ENV['AWS_SECRET']]);
$engines->config(Ses::class,         'setCredentials',           [$_ENV['AWS_KEY'], $_ENV['AWS_SECRET'], $_ENV['SES_REGION']]);
$engines->config(Recaptcha::class,   'setCredentials',           [$_ENV['RECAPTCHA_SITE_KEY'], $_ENV['RECAPTCHA_SECRET']]);
$engines->config(FileStorage::class, 'setServerDirectory',       [Bourbon::getInstance()->getPublicDirectory('storage')]);
$engines->config(FileStorage::class, 'setClientDirectory',       [Bourbon::getInstance()->getPublicPath('storage')]);
$engines->config(EmailLogger::class, 'setToAddress',             [$_ENV['APP_ADMIN_EMAIL']]);
$engines->config(FileLogger::class,  'setDirectory',             [Bourbon::getInstance()->getLogDirectory()]);
$engines->config(FileCache::class,   'setDirectory',             [Bourbon::getInstance()->getDataCacheDirectory()]);
$engines->config(Migration::class,   'setDirectory',             [Bourbon::getInstance()->getMigrationDirectory()]);
$engines->config(Schedule::class,    'setDirectory',             [Bourbon::getInstance()->getScheduledJobsDirectory()]);
$engines->config(FileMeta::class,    'setPath',                  [Bourbon::getInstance()->getDataDirectory() . 'meta_storage.php']);
$engines->config(Http::class,        'setCertificateBundlePath', [Bourbon::getInstance()->getDataDirectory() . 'cacert.pem']);
$engines->config(Url::class,         'setCanonicalDomain',       [$_ENV['APP_CANONICAL_DOMAIN']]);


return $engines;