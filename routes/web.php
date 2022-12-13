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
Route::get('api/v1/', function () {
    return view('welcome');
});
$router->get('api/v1/getorders', [
    'as' => 'getorders', 'uses' => 'OrderController@list'
]);

$router->get('api/v1/setCacheOrders', [
    'as' => 'set', 'uses' => 'OrderController@setCacheData'
]);

$router->get('api/v1/getCacheOrders', [
    'as' => 'get', 'uses' => 'OrderController@getCacheData'
]);
$router->get('/api/v1/dipatchSyncJob', [
    'as' => 'get', 'uses' => 'OrderController@dipatchSyncJob'
]);

$router->get('api/v1/lisTtest', ['middleware' =>['auth'],
    'as' => 'lisTtest', 'uses' => 'OrderController@lisTtest'
]);
$router->post('api/v1/getcsvdata', ['middleware' =>['auth'],
    'as' => 'getcsvdata', 'uses' => 'ExportOrder@getcsvdata'
]);
$router->get('api/v1/downloadcsvfile/{ext_id}', [
    'as' => 'downloadcsvfile', 'uses' => 'ExportOrder@downloadcsvfile'
]);

$router->post('api/v1/testt', [
    'as' => 'testt', 'uses' => 'ExportOrder@testt'
]);

$router->post('api/v1/getkioskcsv', [
    'as' => 'testt', 'uses' => 'ExportOrder@getkioskcsv'
]);







