<?php


use Itg\Cms\Http\Controller\PageController;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;


$cms_base = Bourbon::getInstance()->getPublicPath() . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'Itg' . DIRECTORY_SEPARATOR . 'Cms' . DIRECTORY_SEPARATOR;


?><!DOCTYPE html>

<html lang="en">

    <head>

        <title>Log In :: {{{ Bourbon::getInstance()->getConfiguration('site_name') }}}</title>

        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <meta name="robots" content="noindex, nofollow" />

        <link rel="shortcut icon" href="{{ $cms_base }}_public/images/favicon.png" type="image/png" />

        {{ $_helper->_css($cms_base . '_public/css/styles.min.css') }}

        {{ $_helper->_js($cms_base . '_public/js/initial.min.js') }}

        <!--[if lt IE 9]>
            {{ $_helper->_js($cms_base . '_public/js/ie8.min.js') }}
        <![endif]-->

    </head>

    <body class="signin">

        <section>

            <div class="signinpanel">
                {include:message}
                <div class="row">
                    <form method="post" action="{{ $_helper->_link(PageController::class, 'login_attempt') }}">
                        <input type="hidden" name="csrf_token" value="{csrf_token}" />
                        <h4 class="nomargin">Sign In</h4>
                        <p class="mt5 mb20">Log in to access your account</p>
                        <input type="text" class="form-control uname" name="username" placeholder="Username" autofocus="autofocus" />
                        <input type="password" class="form-control pword" name="password" placeholder="Password" />
                        <button class="btn btn-success btn-block">Sign In</button>
                    </form>
                </div>
            </div>

        </section>

        {{ $_helper->_js($cms_base . '_public/js/post_body.min.js') }}

    </body>

</html>