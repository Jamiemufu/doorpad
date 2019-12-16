<?php


use Itg\Buildr\Facade\Me;
use Itg\Buildr\Facade\Nav;
use Whiskey\Bourbon\Js;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Itg\Cms\Http\Controller\AccountController;
use Itg\Cms\Http\Controller\PageController;


$cms_base         = Bourbon::getInstance()->getPublicPath() . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'Itg' . DIRECTORY_SEPARATOR . 'Cms' . DIRECTORY_SEPARATOR;
$_show_search_box = !!count(Hooks::get('search'));


?><!DOCTYPE html>

<html lang="en">

    <head>

        <title>{{{ Nav::getActiveGroup() }}} {{{ (Nav::getActiveItem() != Nav::getActiveGroup()) ? '- ' . Nav::getActiveItem() : '' }}} :: {{{ Bourbon::getInstance()->getConfiguration('site_name') }}}</title>

        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <meta name="robots" content="noindex, nofollow" />

        <link rel="shortcut icon" href="{{ $cms_base }}_public/images/favicon.png" type="image/png" />

        {{ $_helper->_css($cms_base . '_public/css/styles.min.css') }}

        {{ $_helper->_js($cms_base . '_public/js/initial.min.js') }}
        {{ $_helper->_js(Js::getPath()) }}

        <!--[if lt IE 9]>
            {{ $_helper->_js($cms_base . '_public/js/ie8.min.js') }}
        <![endif]-->

    </head>

    <body class="stickyheader">

        <!-- Preloader -->
        <div id="preloader">
            <div id="status"><i class="fa fa-spinner fa-spin"></i></div>
        </div>

        <section>

            <div class="leftpanel sticky-leftpanel">
                <div class="logopanel">
                    <h1>{{{ Bourbon::getInstance()->getConfiguration('site_name') }}}</h1>
                </div>
                <!-- logopanel -->
                <div class="leftpanelinner">
                    <!-- This is only visible to small devices -->
                    <div class="visible-xs hidden-sm hidden-md hidden-lg">
                        <div class="media userlogged">
                            <img alt="" src="{{ Me::getIcon() }}" class="media-object">
                            <div class="media-body">
                                <h4>{{ Me::getUsername() }}</h4>
                                <span>{{ Me::getRoleName() }}</span>
                            </div>
                        </div>
                    </div>
                    <h5 class="sidebartitle">Navigation</h5>
                    {{ Nav::getHtml() }}
                </div>
                <!-- leftpanelinner -->
            </div>
            <!-- leftpanel -->
            <div class="mainpanel">
                <div class="headerbar">
                    <a class="menutoggle"><i class="fa fa-bars"></i></a>
                    @if ($_show_search_box)
                        <form class="searchform" action="{{ $_helper->_link(PageController::class, 'search') }}" method="GET">
                            <input type="text" class="form-control" name="keywords" placeholder="Search..." value="{{{ $_GET['keywords'] or '' }}}" />
                        </form>
                    @endif
                    <div class="header-right">
                        <ul class="headermenu">
                            <li>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <img src="{{ Me::getIcon() }}" alt="" />
                                    {{ Me::getUsername() }}
                                    <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-usermenu pull-right">
                                        <li><a href="{{ $_helper->_link(AccountController::class, 'my_account') }}"><i class="glyphicon glyphicon-user"></i> My Account</a></li>
                                        <li><a href="{{ $_helper->_link(PageController::class, 'logout') }}"><i class="glyphicon glyphicon-log-out"></i> Log Out</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <button id="chatview" class="btn btn-default tp-icon chat-icon">
                                <i class="fa fa-users"></i>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <!-- header-right -->
                </div>
                <!-- headerbar -->
                <div class="pageheader">
                    <h2><a href="{{ $_helper->_link(PageController::class, 'dashboard') }}"><i class="fa fa-home"></i></a> {{{ Nav::getActiveGroup() }}} <span<?php if (Nav::getActiveItem() == Nav::getActiveGroup()) { echo ' style="display: none;"'; } ?>>{{{ Nav::getActiveItem() }}}</span></h2>
                </div>
                <div class="contentpanel">
                    {include:message}
                    {include:content}
                </div>
                <!-- contentpanel -->
            </div>
            <!-- mainpanel -->
            <div class="rightpanel">
                <!-- Tab panes -->
                <div class="tab-content">
                    <div class="tab-pane active" id="rp-alluser">
                        <div id="online_users_holder">
                            <h5 class="sidebartitle">Online Users</h5>
                            <ul class="chatuserlist" id="online_users_list"></ul>
                        </div>
                    </div>
                    <!-- tab-pane -->
                </div>
                <!-- tab-content -->
            </div>
            <!-- rightpanel -->

        </section>

        {{ $_helper->_js($cms_base . '_public/js/post_body.min.js') }}
        {{ $_helper->_js($cms_base . '_public/js/buildr.min.js') }}

    </body>
</html>