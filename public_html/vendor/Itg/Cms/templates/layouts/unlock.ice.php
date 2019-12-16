<?php


use Itg\Cms\Http\Controller\PageController;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;


$cms_base = Bourbon::getInstance()->getPublicPath() . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'Itg' . DIRECTORY_SEPARATOR . 'Cms' . DIRECTORY_SEPARATOR;


?><!DOCTYPE html>

<html lang="en">

    <head>

        <title>Unlock :: {{{ Bourbon::getInstance()->getConfiguration('site_name') }}}</title>

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

            <div class="lockedpanel">
                <div class="loginuser">
                    <img src="{{ $user->getIcon() }}" alt="" />
                </div>
                <div class="logged">
                    <h4>{{ $user->getUsername() }}</h4>
                    @if ($user->getEmail() != '')
                        <small class="text-muted">{{ $user->getEmail() }}</small>
                    @endif
                </div>
                <form method="post" action="{{ $_helper->_link(PageController::class, 'login_attempt') }}">
                    <input type="hidden" name="csrf_token" value="{csrf_token}" />
                    <input type="hidden" name="username" value="{{ $user->getUsername() }}" />
                    <input name="password" type="password" class="form-control" placeholder="Password" autofocus="autofocus" />
                    <button class="btn btn-success btn-block">Log Back In</button>
                </form>
                <br />
                <small class="text-muted"><a href="{{ $_helper->_link(PageController::class, 'login_attempt') }}">Not you?</a></small>
            </div><!-- lockedpanel -->

        </section>

        {{ $_helper->_js($cms_base . '_public/js/post_body.min.js') }}

    </body>

</html>