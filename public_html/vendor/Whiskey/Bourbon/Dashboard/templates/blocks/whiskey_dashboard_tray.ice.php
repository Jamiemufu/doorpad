<?php

use Whiskey\Bourbon\Dashboard\Controller\WhiskeyDashboardController;

?>

<style> #_whsky_dashboard_widget,#_whsky_dashboard_widget_handle{box-shadow:0 0 10px 0 rgba(255,255,255,.35);background-color:#232323}#_whsky_dashboard_widget{position:fixed;left:0;right:0;bottom:0;height:0;overflow:visible;z-index:2147483647}#_whsky_dashboard_widget a{color:#333}#_whsky_dashboard_widget_handle{cursor:pointer;position:absolute;top:-15px;left:50%;margin-left:-24px;width:48px;height:15px;border-top-left-radius:4px;border-top-right-radius:4px;background-repeat:no-repeat;background-position:center;background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAHCAQAAACSTrSSAAAAEklEQVQIW2P8b89AA8A4AMYCAK4HA77RwX2pAAAAAElFTkSuQmCC)}#_whsky_dashboard_widget_inner{position:absolute;top:0;bottom:0;left:0;right:0;padding:20px;background-color:#232323}#_whsky_dashboard_widget_nav{position:absolute;top:0;left:0;right:0;height:25px;border-radius:2px;padding-left:1px}#_whsky_dashboard_widget_nav>div{background-color:rgba(255,255,255,.05);display:inline-block;line-height:20px;margin:1px 1px 1px 0;padding:1px 15px;color:#999;font-family:'Open Sans','Century Gothic',Arial,sans-serif;font-size:14px;cursor:pointer;float:left}#_whsky_dashboard_widget_nav>div:first-child{margin:1px}#_whsky_dashboard_widget_nav>div.active{background-color:rgba(255,255,255,.1)}._whsky_dashboard_widget_content_pane{position:absolute;top:24px;left:2px;right:2px;bottom:2px;background-color:#fff;border-radius:2px;overflow:auto;z-index:1}#_whsky_dashboard_widget_dashboard_content>div,#_whsky_dashboard_widget_database_content>div,#_whsky_dashboard_widget_information_content>div{background-color:rgba(0,0,0,.05);text-align:left;color:#333;font-size:14px;font-family:'Open Sans','Century Gothic',Arial,sans-serif}#_whsky_dashboard_widget_dashboard_content{z-index:2}#_whsky_dashboard_widget_dashboard_content>div,#_whsky_dashboard_widget_database_content>div{padding:3px}#_whsky_dashboard_widget_information_content>div>div{display:inline-block;vertical-align:middle;box-sizing:border-box;width:50%;word-break:break-all;color:#333;font-family:'Open Sans','Century Gothic',Arial,sans-serif;font-size:14px;text-align:left}#_whsky_dashboard_widget_information_content>div>div:nth-child(1){padding:3px;border-right:1px solid rgba(0,0,0,.1)}#_whsky_dashboard_widget_information_content>div>div:nth-child(2){padding-left:10px;white-space:pre-wrap}#_whsky_dashboard_widget_database_content>div>span{display:inline-block;vertical-align:middle;box-sizing:border-box;height:10px;width:10px;border-radius:50%;border:1px solid rgba(0,0,0,.1);margin:0 10px 0 5px;background-color:#e30022}#_whsky_dashboard_widget_database_content>div>span.active{background-color:#9acd32}#_whsky_dashboard_widget_timeline_content>div{color:#333;background-color:rgba(0,0,0,.05);font-family:'Open Sans','Century Gothic',Arial,sans-serif;font-size:14px;text-align:left}#_whsky_dashboard_widget_timeline_content>div>div{display:inline-block;vertical-align:middle;box-sizing:border-box;text-align:left}#_whsky_dashboard_widget_timeline_content>div>div:nth-child(1){width:34%;word-break:break-all;padding:3px;border-right:1px solid rgba(0,0,0,.1);color:#333;font-family:'Open Sans','Century Gothic',Arial,sans-serif;font-size:14px}#_whsky_dashboard_widget_timeline_content>div>div:nth-child(2){width:66%;height:20px;position:relative}._whsky_dashboard_widget_timeline_details>div:nth-child(1){position:absolute;top:8px;left:0;height:4px;background-color:rgba(0,0,0,.05)}._whsky_dashboard_widget_timeline_details>div:nth-child(2){position:absolute;top:1px;bottom:1px;background-color:#666;color:#fff;border-radius:2px;font-size:10px;padding:2px 5px;line-height:15px;font-family:'Open Sans','Century Gothic',Arial,sans-serif}#_whsky_dashboard_widget_dashboard_content>div:nth-child(even),#_whsky_dashboard_widget_database_content>div:nth-child(even),#_whsky_dashboard_widget_information_content>div:nth-child(even),#_whsky_dashboard_widget_timeline_content>div:nth-child(even){background-color:rgba(0,0,0,.1)} </style>

<div id="_whsky_dashboard_widget">
    <span id="_whsky_dashboard_widget_handle" title="Developer Tools"></span>
    <div id="_whsky_dashboard_widget_inner">
        <div id="_whsky_dashboard_widget_nav">
            <div data-target="dashboard" class="active">Dashboard</div>
            <div data-target="info">Info</div>
            <div data-target="databases">Databases</div>
            <div data-target="timeline">Timeline</div>
        </div>
        <div id="_whsky_dashboard_widget_dashboard_content" class="_whsky_dashboard_widget_content_pane">
            <div><sup>[link]</sup> <a href="{{ $_helper->_link(WhiskeyDashboardController::class, 'info') }}" target="_blank">Dashboard</a></div>
            <div><sup>[link]</sup> <a href="{{ $_helper->_link(WhiskeyDashboardController::class, 'migrations') }}" target="_blank">Migration Management</a></div>
            <div><sup>[link]</sup> <a href="{{ $_helper->_link(WhiskeyDashboardController::class, 'cron') }}" target="_blank">Cron Job Management</a></div>
            <div><sup>[link]</sup> <a href="https://whsky.uk" target="_blank">Framework Documentation</a></div>
        </div>
        <div id="_whsky_dashboard_widget_information_content" class="_whsky_dashboard_widget_content_pane">
            @foreach ($environment as $key => $value)
                <div>
                    <div>{{{ $key }}}</div><div>{{{ $value }}}</div>
                </div>
            @endforeach
        </div>
        <div id="_whsky_dashboard_widget_database_content" class="_whsky_dashboard_widget_content_pane">
            @if (count($database_connections))
                @foreach ($database_connections as $connection_name => $connected)
                <div>
                    <span{{ ($connected ? ' class="active"' : '') }}></span>
                    {{{ $connection_name }}}
                </div>
                @endforeach
            @else
                <div>No database connections found</div>
            @endif
        </div>
        <div id="_whsky_dashboard_widget_timeline_content" class="_whsky_dashboard_widget_content_pane">
            @foreach ($autoload_logs['autoloads'] as $autoload)
                <div>
                    <div>{{{ $autoload['class'] }}}</div><div class="_whsky_dashboard_widget_timeline_details">
                        <div style="width: {{{ $autoload['memory_percentage'] }}}%;"></div>
                        <div style="left: {{{ ($autoload['time_percentage'] * 0.8) }}}%;" title="Memory Usage{{ "\n" }}{{ number_format(($autoload['memory'] / 1024 / 1024), 5) }} MiB">{{ number_format($autoload['time'], 3) }}s</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script> !function(){document.getElementById("_whsky_dashboard_widget_handle").addEventListener("click",function(){var t=document.getElementById("_whsky_dashboard_widget").style.height,e="250px"==t?"0":"250px";document.getElementById("_whsky_dashboard_widget").style.height=e});var t=document.querySelectorAll("#_whsky_dashboard_widget_nav > div");for(var e in t)t.hasOwnProperty(e)&&t[e].addEventListener("click",function(){var t=document.querySelectorAll("#_whsky_dashboard_widget_nav > div");for(var e in t)t.hasOwnProperty(e)&&t[e].classList.remove("active");this.classList.add("active"),document.getElementById("_whsky_dashboard_widget_dashboard_content").style.zIndex="dashboard"==this.dataset.target?2:1,document.getElementById("_whsky_dashboard_widget_information_content").style.zIndex="info"==this.dataset.target?2:1,document.getElementById("_whsky_dashboard_widget_database_content").style.zIndex="databases"==this.dataset.target?2:1,document.getElementById("_whsky_dashboard_widget_timeline_content").style.zIndex="timeline"==this.dataset.target?2:1})}(); </script>