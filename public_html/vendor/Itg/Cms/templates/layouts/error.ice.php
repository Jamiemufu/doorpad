<?php


use Whiskey\Bourbon\App\Bootstrap as Bourbon;


$cms_base = Bourbon::getInstance()->getPublicPath() . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'Itg' . DIRECTORY_SEPARATOR . 'Cms' . DIRECTORY_SEPARATOR;


?><!DOCTYPE html>

<html lang="en">

    <head>

        <title>Error :: {{{ Bourbon::getInstance()->getConfiguration('site_name') }}}</title>

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

    <body class="notfound">

        {include:content}

        {{ $_helper->_js($cms_base . '_public/js/post_body.min.js') }}

    </body>

</html>