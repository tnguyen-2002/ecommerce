<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';

define('APPNAME', 'Short It! - URL Shortener');

session_start();

$router = new \Bramus\Router\Router();

// Auth routes
$router->post('/logout', '\App\Controllers\Auth\LoginController@destroy');
$router->get('/register', '\App\Controllers\Auth\RegisterController@create');
$router->post('/register', '\\App\Controllers\Auth\RegisterController@store');
$router->get('/login', '\App\Controllers\Auth\LoginController@create');
$router->post('/login', '\App\Controllers\Auth\LoginController@store');

// Home routes
$router->get('/', '\App\Controllers\HomeController@index');
$router->get('/home', '\App\Controllers\HomeController@index');
$router->post('/shorten', 'App\Controllers\ShortUrlsController@store');
$router->post('/update-url', 'App\Controllers\ShortUrlsController@update');
$router->post('/delete-url', 'App\Controllers\ShortUrlsController@delete');
$router->post('/update-tag', 'App\Controllers\TagsController@update');
$router->post('/delete-tag', 'App\Controllers\TagsController@delete');

//URL redirect route (must be last to catch all short URLs)
$router->get('/{slug}', 'App\Controllers\ShortUrlsController@redirect');

$router->run();

