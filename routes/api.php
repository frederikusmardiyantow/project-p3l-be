<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::namespace('API')->group(function() {
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    
    Route::middleware(['auth:sanctum', 'checkRole:all'])->group(function (){
        Route::post('logout', 'AuthController@logout');
    });
    Route::middleware(['auth:sanctum', 'checkRole:admin'])->group(function (){
        Route::apiResource('jenis', JenisKamarController::class);
        Route::get('jenis_all', 'JenisKamarController@getDataForAllFlag');
        Route::apiResource('kamar', MasterKamarController::class);
        Route::get('kamar_all', 'MasterKamarController@getDataForAllFlag');
        Route::apiResource('role', MasterRoleController::class);
        Route::get('role_all', 'MasterRoleController@getDataForAllFlag');
        Route::apiResource('pegawai', MasterPegawaiController::class);
        Route::get('pegawai_all', 'MasterPegawaiController@getDataForAllFlag');
    });
    Route::middleware(['auth:sanctum', 'checkRole:Sales & Marketing'])->group(function (){
        Route::apiResource('season', MasterSeasonController::class);
        Route::get('season_all', 'MasterSeasonController@getDataForAllFlag');
    });

});


