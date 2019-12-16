<?php


namespace Whiskey\Bourbon\Config;


use Whiskey\Bourbon\Config\Type\ClassAlias;


$aliases = new ClassAlias();


/*
 * Alias façade classes
 */
$aliases->set(\Whiskey\Bourbon\App\Facade\Acl::class,              'Acl');
$aliases->set(\Whiskey\Bourbon\App\Facade\AppEnv::class,           'AppEnv');
$aliases->set(\Whiskey\Bourbon\App\Facade\Auth::class,             'Auth');
$aliases->set(\Whiskey\Bourbon\App\Bootstrap::class,               'Bourbon');
$aliases->set(\Whiskey\Bourbon\App\Facade\Cache::class,            'Cache');
$aliases->set(\Whiskey\Bourbon\App\Facade\Captcha::class,          'Captcha');
$aliases->set(\Whiskey\Bourbon\App\Facade\Color::class,            'Color');
$aliases->set(\Whiskey\Bourbon\App\Facade\Colour::class,           'Colour');
$aliases->set(\Whiskey\Bourbon\App\Facade\Cookie::class,           'Cookie');
$aliases->set(\Whiskey\Bourbon\App\Facade\Cron::class,             'Cron');
$aliases->set(\Whiskey\Bourbon\App\Facade\Crypt::class,            'Crypt');
$aliases->set(\Whiskey\Bourbon\App\Facade\Csrf::class,             'Csrf');
$aliases->set(\Whiskey\Bourbon\App\Facade\Db::class,               'Db');
$aliases->set(\Whiskey\Bourbon\App\Facade\Email::class,            'Email');
$aliases->set(\Whiskey\Bourbon\App\Facade\Errors::class,           'Errors');
$aliases->set(\Whiskey\Bourbon\App\Facade\FlashMessage::class,     'FlashMessage');
$aliases->set(\Whiskey\Bourbon\App\Facade\FormBuilder::class,      'FormBuilder');
$aliases->set(\Whiskey\Bourbon\App\Facade\Hooks::class,            'Hooks');
$aliases->set(\Whiskey\Bourbon\App\Facade\Http::class,             'Http');
$aliases->set(\Whiskey\Bourbon\App\Facade\Ice::class,              'Ice');
$aliases->set(\Whiskey\Bourbon\App\Facade\Input::class,            'Input');
$aliases->set(\Whiskey\Bourbon\App\Facade\Lang::class,             'Lang');
$aliases->set(\Whiskey\Bourbon\App\Facade\Logging::class,          'Logging');
$aliases->set(\Whiskey\Bourbon\App\Facade\Meta::class,             'Meta');
$aliases->set(\Whiskey\Bourbon\App\Facade\Migration::class,        'Migration');
$aliases->set(\Whiskey\Bourbon\App\Facade\Paginate::class,         'Paginate');
$aliases->set(\Whiskey\Bourbon\App\Facade\Password::class,         'Password');
$aliases->set(\Whiskey\Bourbon\App\Facade\Request::class,          'Request');
$aliases->set(\Whiskey\Bourbon\App\Facade\Response::class,         'Response');
$aliases->set(\Whiskey\Bourbon\App\Facade\Router::class,           'Router');
$aliases->set(\Whiskey\Bourbon\Helper\Component\SafeString::class, 'SafeString');
$aliases->set(\Whiskey\Bourbon\App\Facade\Server::class,           'Server');
$aliases->set(\Whiskey\Bourbon\App\Facade\Session::class,          'Session');
$aliases->set(\Whiskey\Bourbon\App\Facade\SplitTest::class,        'SplitTest');
$aliases->set(\Whiskey\Bourbon\App\Facade\Storage::class,          'Storage');
$aliases->set(\Whiskey\Bourbon\App\Facade\Templating::class,       'Templating');
$aliases->set(\Whiskey\Bourbon\App\Facade\Tracking::class,         'Tracking');
$aliases->set(\Whiskey\Bourbon\App\Facade\Url::class,              'Url');
$aliases->set(\Whiskey\Bourbon\App\Facade\Utils::class,            'Utils');
$aliases->set(\Whiskey\Bourbon\App\Facade\Validation::class,       'Validation');


return $aliases;