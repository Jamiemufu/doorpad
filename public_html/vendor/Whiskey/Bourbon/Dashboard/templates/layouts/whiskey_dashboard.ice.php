<?php


use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\Dashboard\Controller\WhiskeyDashboardController;


$dashboard_path  = Bourbon::getInstance()->getPublicPath();
$dashboard_path  = rtrim(rtrim($dashboard_path, '/'), '_public');
$dashboard_path .= 'vendor/Whiskey/Bourbon/Dashboard/';


?><!DOCTYPE html>

<html lang="en">

    <head>

        <title>Dashboard :: Whiskey Framework v{{ Bourbon::VERSION }}</title>
        <meta name="robots" content="noindex, nofollow" />
        <meta charset="utf-8" />
        <link rel="shortcut icon" href="{{ $dashboard_path }}public/images/icon.png" />
        <meta name="viewport" content="user-scalable=no, width=device-width" />
        <!--[if lt IE 9]>
            <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
        <link href="{{ $dashboard_path }}public/css/styles.min.css" rel="stylesheet" />

    </head>

    <body>

        <header>
            <a href="{{ $_helper->_link(WhiskeyDashboardController::class, 'info') }}">
                <img src="{{ $dashboard_path }}public/images/icon.png" alt="Whiskey Framework v{{ Bourbon::VERSION }}" />
            </a>
            <h1>Dashboard</h1>
        </header>

        <nav>
            <a{{ $active_page == 'info' ? ' class="active"' : ''}} href="{{ $_helper->_link(WhiskeyDashboardController::class, 'info') }}">Info</a>
            <a{{ $active_page == 'migrations' ? ' class="active"' : ''}} href="{{ $_helper->_link(WhiskeyDashboardController::class, 'migrations') }}">Migrations</a>
            <a{{ $active_page == 'cron' ? ' class="active"' : ''}} href="{{ $_helper->_link(WhiskeyDashboardController::class, 'cron') }}">Cron</a>
        </nav>

        {include:message}
        {include:content}

        <script src="{{ $dashboard_path }}public/js/scripts.min.js"></script>

    </body>

</html>