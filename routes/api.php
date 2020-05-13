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


Route::prefix('v1')->namespace('Api')->group(function () {

    //获取答疑列表
    Route::get('questions/list', 'QuestionController@list')->name('api.questions.list');
    // 答疑详情
    Route::get('questions/show/{id}', 'QuestionController@show')->name('api.questions.show');
    // 点赞
    Route::post('questions/thumb', 'QuestionController@thumb')->name('api.questions.thumb');

    // 注册
    Route::post('auth/login', 'AuthController@login')->name('api.auth.login');



    Route::middleware('auth:api')->group(function () {
        // 图片上传
        Route::post('questions/photo', 'QuestionController@photo')->name('api.questions.photo');
        // 创建问题
        Route::post('questions/create', 'QuestionController@create')->name('api.questions.create');
        Route::get('questions/orderList', 'QuestionController@orderList')->name('api.questions.orderList');
        Route::get('questions/orderShow/{id}', 'QuestionController@orderShow')->name('api.questions.orderShow');
        Route::post('questions/studentReply', 'QuestionController@studentReply')->name('api.questions.studentReply');
        Route::post('users/roleCreate', 'UserController@roleCreate')->name('api.users.roleCreate');
        Route::get('users/getRole', 'UserController@getRole')->name('api.users.getRole');
        Route::post('auth/checkToken', 'AuthController@checkToken')->name('api.auth.checkToken');
    });
});
