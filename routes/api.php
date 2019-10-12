<?php

use Illuminate\Http\Request;

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

/* Route::middleware("auth:api")->get("/user", function (Request $request) {
  return $request->user();
  }); */

Route::group(["middleware" => ["allow.ip"]], function() {

    Route::group(["middleware" => ["check.blocked_ip"]], function() {
        Auth::routes();
        Route::resource("privileges", "PrivilegeController")->only(["index"]);

        Route::group(["middleware" => ["check.login"]], function() {
            Route::get("test", "PrivilegeController@index");
            Route::resource("users", "UserController")->only(["index", "store"]);
        });
    });
});

Route::get("getLanguageText/{languageId}/{module}", "LanguageController@getLanguageText");

