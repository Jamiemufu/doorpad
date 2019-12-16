<?php

use Whiskey\Bourbon\App\Facade\AppEnv;
use Whiskey\Bourbon\Js as WhskyJs;

?>
<!DOCTYPE html>

<html lang="en">

    <head>

        <title>Doorpad</title>
        <meta name="description" content="Site Description" />
        <meta name="keywords" content="Site, Keywords" />
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="{{ AppEnv::imageDir() }}favicon.png" />
        <!-- <meta name="viewport" content="user-scalable=no, width=device-width" /> -->
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black" />
        <meta name="viewport" content="initial-scale=1.0,width=device-width">        
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Montserrat&display=swap" rel="stylesheet">
        <!-- local bootstrap  -->
        {{ $_helper->_css('bootstrap/bootstrap.min.css') }}
        <!-- local css -->
        {{ $_helper->_css('styles.min.css?v=3') }}
        <!-- js -->
        {{ $_helper->_js('whsky.min.js?v=2') }}
        <!--[if lt IE 9]>
            {{ $_helper->_js('ie8.min.js') }}
        <![endif]-->
        <script src='https://code.responsivevoice.org/responsivevoice.js'></script>

    </head>

    <body>

        {include:message}
        {include:content}
        
        {{ $_helper->_js('jquery-3.4.1.min.js') }}
        {{ $_helper->_js('bootstrap/bootstrap.min.js') }}
        {{ $_helper->_js('scripts.min.js') }}

        {{ $_helper->_ga(isset($_ENV['GA_KEY']) ? $_ENV['GA_KEY'] : '') }}

    </body>

</html>