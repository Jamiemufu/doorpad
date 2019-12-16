<?php


namespace Whiskey\Bourbon\Config;


use Whiskey\Bourbon\Config\Type\Database;


$databases = new Database();


/*
 * Connect to the default database
 */
$databases->set('default',
    [
        'host'     => $_ENV['DB_HOST'],
        'database' => $_ENV['DB_DATABASE'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
        'port'     => $_ENV['DB_PORT'],
        'socket'   => $_ENV['DB_SOCKET']
    ]);


return $databases;