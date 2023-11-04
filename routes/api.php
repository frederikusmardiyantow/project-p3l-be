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
    Route::post('login/admin', 'AuthController@loginAdmin');
    Route::post('register', 'AuthController@register');
    Route::post('forget/request','AuthController@forgetPassword');
    Route::get('password/reset/{token}','AuthController@tokenVerification');
    Route::post('forget/updatePassword/{token}','AuthController@updatePassword');
    Route::get('jenis', 'JenisKamarController@index');
    Route::get('jenis/{id}', 'JenisKamarController@show');
    
    Route::middleware(['auth:sanctum', 'checkRole:all'])->group(function (){
        Route::post('ubahPassword', 'MasterCustomerController@ubahPassword');
        Route::post('logout', 'AuthController@logout');
        Route::get('profile','MasterCustomerController@getProfile');
    });
    Route::middleware(['auth:sanctum', 'checkRole:customer'])->group(function (){
        Route::put('ubahProfile', 'MasterCustomerController@update');
        Route::get('transaksi','MasterCustomerController@riwayatTrxByMe');
    });
    Route::middleware(['auth:sanctum', 'checkRole:admin'])->group(function (){
        Route::post('jenis', 'JenisKamarController@store');
        Route::patch('jenis/{id}', 'JenisKamarController@update');
        Route::delete('jenis/{id}', 'JenisKamarController@destroy');
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
        Route::apiResource('layanan', MasterLayananBerbayarController::class);
        Route::get('layanan_all', 'MasterLayananBerbayarController@getDataForAllFlag');
        Route::apiResource('tarif', MasterTarifController::class);
        Route::get('tarif_all', 'MasterTarifController@getDataForAllFlag');
        Route::get('customer', 'MasterCustomerController@index');
        Route::get('customer_group', 'MasterCustomerController@getAllCustomerGroup');
        Route::get('customer/{id}', 'MasterCustomerController@show');
        Route::get('customer_all', 'MasterCustomerController@getDataForAllFlag');
        Route::post('register/group', 'AuthController@register');
        // Route::post('transaksi/detail', 'MasterTrxReservasiController@store');
        // Route::put('transaksi/detail/{id}', 'MasterTrxReservasiController@update');
        // Route::delete('transaksi/detail/{id}', 'MasterTrxReservasiController@destroy');
        Route::get('transaksi/detail_all', 'MasterTrxReservasiController@getDataForAllFlag');
        Route::get('transaksi/{id}','MasterCustomerController@riwayatTrxBySM');
    });
    Route::middleware(['auth:sanctum', 'checkRole:Sales & Marketing,customer'])->group(function (){
        Route::get('transaksi/detail/{id}', 'MasterTrxReservasiController@show');
        Route::get('ketersediaan/kamar', 'KamarSediaController@KamarSediaPersonal');

    });

});


