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
/** @var Illuminate\Support\Facades\Route $router */

$router->post('nfce','NfeController@nfce');
$router->post('nfe-transfer', 'NfeController@nfeTransfer');
$router->get('print', 'NfeController@printNfe');
$router->post('cancel', 'NfeController@cancelNfe');
