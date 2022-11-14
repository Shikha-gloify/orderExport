<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/getorders', [
    'as' => 'getorders', 'uses' => 'OrderController@list'
]);

$router->get('/setCacheOrders', [
    'as' => 'set', 'uses' => 'OrderController@setCacheData'
]);

$router->get('/getCacheOrders', [
    'as' => 'get', 'uses' => 'OrderController@getCacheData'
]);
$router->get('/dipatchSyncJob', [
    'as' => 'get', 'uses' => 'OrderController@dipatchSyncJob'
]);

$router->get('/lisTtest', [
    'as' => 'lisTtest', 'uses' => 'OrderController@lisTtest'
]);
$router->post('/getcsvdata', [
    'as' => 'getcsvdata', 'uses' => 'ExportOrder@getcsvdata'
]);
$router->get('/downloadcsvfile/{ext_id}', [
    'as' => 'downloadcsvfile', 'uses' => 'ExportOrder@downloadcsvfile'
]);







