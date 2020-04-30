<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//获取答疑列表
Route::prefix('v1')->namespace('Api')->group(function () {


    Route::get('questions/list', 'QuestionController@list')->name('api.questions.list');
    Route::get('questions/show/{id}', 'QuestionController@show')->name('api.questions.show');
    Route::post('auth/login', 'AuthController@login')->name('api.auth.login');



});
