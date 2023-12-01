<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */
//require __DIR__ . '/vendor/autoload.php';
//
//use Ratchet\Server\IoServer;
//use Ratchet\Http\HttpServer;
//use Ratchet\WebSocket\WsServer;
//
//$server = IoServer::factory(
//    new HttpServer(
//        new WsServer(
//            new \App\Socket\WebSocketServer()
//        )
//    ),
//    8080 // پورت مورد نظر خود را تعیین کنید
//);
//
//$server->run();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
