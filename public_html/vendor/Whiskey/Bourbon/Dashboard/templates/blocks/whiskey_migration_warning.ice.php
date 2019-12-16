<?php

use Whiskey\Bourbon\Dashboard\Controller\WhiskeyDashboardController;

?>

<style> #_whsky_migration_warning{position:fixed;top:0;left:0;right:0;padding:15px;background-color:#232323;z-index:2147483647;color:#fff;font-family:'Open Sans',Arial,Verdana,sans-serif;font-size:14px;text-align:left;}#_whsky_migration_warning>a{color:#fff;text-decoration:underline;}#_whsky_migration_warning_close{float:right;cursor:pointer;} </style>

<div id="_whsky_migration_warning">
    &#9888; There are outstanding migrations, please <a href="{{ $_helper->_link(WhiskeyDashboardController::class, 'migrations') }}#migrations_list" target="_blank">click here</a> to view details
    <span id="_whsky_migration_warning_close">X</span>
</div>

<script> !function(){document.getElementById("_whsky_migration_warning_close").addEventListener("click",function(){var n=document.getElementById("_whsky_migration_warning");n.parentNode.removeChild(n)})}(); </script>