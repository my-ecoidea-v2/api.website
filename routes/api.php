<?php

use Illuminate\Http\Request;

Route::post('user/register', 'UserController@register');
Route::post('user/login', 'UserController@authenticate');
// Route::get('register/verify/{confirmationCode}', 
// [
//     'as' => 'confirmation_path',
//     'uses' => 'UserController@confirm',
// ]);
    
Route::group(['middleware' => ['jwt.verify']], function()
{
    Route::get('user/get', 'UserController@getAuthenticatedUser');
    Route::post('user/logout', 'UserController@logout');
    Route::put('user/modify', 'UserController@update');
    Route::delete('user/delete', 'UserController@delete');

    Route::post('publication/create', 'PublicationController@create');
    Route::put('publication/publish', 'PublicationController@publish');
    Route::delete('publication/delete', 'PublicationController@delete');
    
    Route::get('publication/get', 'PublicationController@get');
    Route::get('publication/getAll', 'PublicationController@getAll');
    Route::get('publication/getFast', 'PublicationController@getFast');
    Route::get('publication/getModeration', 'PublicationController@getModeration');

    Route::put('publication/interact/like', 'PublicationController@like');
    Route::put('publication/interact/favoris', 'PublicationController@favoris');
    Route::put('publication/seen', 'PublicationController@seen');

    Route::get('user/meFavoris', 'UserController@meFavoris');
    Route::get('user/meIdea', 'UserController@meIdea');

    Route::get('publication/search', 'PublicationSearchController@search');

});
