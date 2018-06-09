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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware("auth:api")->group(function () {
    Route::prefix("networks")->group(function () {
        Route::get("/", "NetworksController@index")->middleware(["roguard:read_network"]);
        Route::get("/{network_id}", "NetworksController@show")->middleware(["roguard:read_network"]);
        Route::post("/","NetworksController@store")->middleware(["roguard:create_network"]);
    });
    Route::prefix("sites")->group(function(){
        Route::get("/","SitesController@index")->middleware(["roguard:read_site"]);
        Route::get("/{site_id}","SitesController@show")->middleware(["roguard:read_site"]);
        Route::post("/","SitesController@store")->middleware(["roguard:create_site"]);
        Route::get("/{site_id}/players","PlayersController@listBySite")->middleware(["roguard:read_player"]);
    });
    Route::prefix("players")->group(function(){
        Route::post("/authorize","PlayersController@authorizePlayer")->middleware(["roguard:authorize_player"]);
        Route::post("/promotions/create","PlayersController@setPromotion")->middleware(["roguard:create_bonus"]);
    });
    Route::prefix("events")->group(function(){
        Route::get("/","EventsController@list")->middleware(["roguard:read_event"]);
        Route::post("/","EventsController@create")->middleware(["roguard:create_event"]);
        Route::post("/{event_id}/result","EventsController@result")->middleware(["roguard:result_event"]);
    });
});

Route::middleware("roplayer")->prefix("player")->group(function(){
    Route::post("/check","PlayersController@check");
    Route::post("/promotions","PlayersController@getPromotions");
    Route::prefix("tickets")->group(function(){
        Route::post("/check","TicketsController@checkSoldBulk");
        Route::post("/","TicketsController@store");
        Route::post("/list","TicketsController@list");
        Route::post("/promotions","PlayersController@getPromotions");

    });
    Route::prefix("events")->group(function() {
        Route::get("/", "EventsController@list");
    });
});

