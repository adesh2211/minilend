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
Route::group(["namespace"=>"API"],function() {
	Route::post('customer_application', 'CustomerController@createCustomerApplication');
	Route::post('admin/login', 'UserController@adminLogin');
	Route::get('customer/track_application', 'UserController@trackCustomerApplications');
	Route::post('forgot_password', 'UserController@forgot_password');
	Route::group(['middleware' => 'auth:api'], function() {
		Route::post('change_password', 'UserController@change_password');
		Route::post('app_logout', 'UserController@app_logout');
		Route::group(['middleware' => 'admin',"prefix"=>"admin"], function() {
			Route::get('customers', 'UserController@getCustomers');
			Route::post('update_status', 'UserController@postStatusChange');
			Route::get('customer/applications', 'UserController@getCustomerApplications');
			Route::get('customer_application', 'UserController@getCustomerApplication');
		});
	});
});