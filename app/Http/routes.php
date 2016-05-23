<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

/*Route::get('/', function () {
    return view('home');
});*/

Route::get('/', 'HomeController@index');

Route::get('/test', 'Test@index');

Route::auth();

Route::get('/home', 'HomeController@index');

//Role
Route::post('user/apiList', 'UserController@apiList');
Route::post('user/apiEdit', 'UserController@apiEdit');
Route::resource('user', 'UserController');

Route::group(['middleware' => 'auth'], function() {
    //
    Route::group(['prefix' => 'master'], function() {
        
        //Role
        Route::post('role/apiList', 'RoleController@apiList');
        Route::post('role/apiEdit', 'RoleController@apiEdit');
        Route::resource('role', 'RoleController');

        //Religion
        Route::post('religion/apiList', 'ReligionController@apiList');
        Route::post('religion/apiEdit', 'ReligionController@apiEdit');
        Route::resource('religion', 'ReligionController');
    });
});
