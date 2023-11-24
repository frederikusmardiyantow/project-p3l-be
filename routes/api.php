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
    Route::post('ketersediaan/kamar', 'KamarSediaController@KamarSediaPersonal');
    
    Route::middleware(['auth:sanctum', 'checkRole:all'])->group(function (){
        Route::post('ubahPassword', 'MasterCustomerController@ubahPassword');
        Route::post('logout', 'AuthController@logout');
        Route::get('profile','MasterCustomerController@getProfile');
    });
    Route::middleware(['auth:sanctum', 'checkRole:customer'])->group(function (){
        Route::put('ubahProfile', 'MasterCustomerController@update');
        Route::get('transaksi','MasterCustomerController@riwayatTrxByMe');
    });
    Route::middleware(['auth:sanctum', 'checkRole:admin,Front Office'])->group(function (){
        Route::apiResource('kamar', MasterKamarController::class);
        Route::get('reservasi/kamar', 'TrxReservasiKamarController@index');
    });
    Route::middleware(['auth:sanctum', 'checkRole:admin'])->group(function (){
        Route::post('jenis', 'JenisKamarController@store');
        Route::patch('jenis/{id}', 'JenisKamarController@update');
        Route::delete('jenis/{id}', 'JenisKamarController@destroy');
        Route::get('jenis_all', 'JenisKamarController@getDataForAllFlag');
        Route::get('kamar_all', 'MasterKamarController@getDataForAllFlag');
        Route::apiResource('role', MasterRoleController::class);
        Route::get('role_all', 'MasterRoleController@getDataForAllFlag');
        Route::apiResource('pegawai', MasterPegawaiController::class);
        Route::get('pegawai_all', 'MasterPegawaiController@getDataForAllFlag');
        Route::get('reservasi/kamar_all', 'TrxReservasiKamarController@getDataForAllFlag');
    });
    Route::middleware(['auth:sanctum', 'checkRole:Sales & Marketing'])->group(function (){
        Route::apiResource('season', MasterSeasonController::class);
        Route::get('season_all', 'MasterSeasonController@getDataForAllFlag');
        Route::get('layanan_all', 'MasterLayananBerbayarController@getDataForAllFlag');
        Route::apiResource('tarif', MasterTarifController::class);
        Route::get('tarif_all', 'MasterTarifController@getDataForAllFlag');
        Route::get('customer', 'MasterCustomerController@index');
        Route::get('customer_group', 'MasterCustomerController@getAllCustomerGroup');
        Route::get('customer/{id}', 'MasterCustomerController@show');
        Route::get('customer_all', 'MasterCustomerController@getDataForAllFlag');
        Route::post('register/group', 'AuthController@register');
        Route::get('transaksi/{id}','MasterCustomerController@riwayatTrxBySM');
    });
    Route::middleware(['auth:sanctum', 'checkRole:Sales & Marketing,customer'])->group(function (){
        Route::post('transaksi/reservasi/kamar','MasterTrxReservasiController@entryDataReservasi');
        Route::post('transaksi/reservasi/upload/{id}','MasterTrxReservasiController@uploadBuktiPembayaran');
        Route::post('transaksi/pembatalan/kamar/{id}','MasterTrxReservasiController@pembatalanReservasi');
        Route::get('transaksi/pembatalan/cekPengembalian/{id}','MasterTrxReservasiController@cekPengembalianDanaOrTidak');
        Route::post('transaksi/layanan', 'TrxLayananBerbayarController@store');
        Route::get('export/pdf/{id}', 'PDFController@exportPDFTandaTerima');
    });
    Route::middleware(['auth:sanctum', 'checkRole:Sales & Marketing,customer,Front Office'])->group(function (){
        
        Route::get('transaksi/reservasi/show', 'MasterTrxReservasiController@index');
        Route::get('transaksi/reservasi/show_all', 'MasterTrxReservasiController@getDataForAllFlag');
        Route::apiResource('layanan', MasterLayananBerbayarController::class);
        Route::get('transaksi/detail/{id}', 'MasterTrxReservasiController@show');
    });
    Route::middleware(['auth:sanctum', 'checkRole:Front Office'])->group(function (){
        Route::post('reservasi/kamar/check-in/{id_trx_reservasi}', 'TrxReservasiKamarController@fixCheckIn');
        Route::get('reservasi/kamar/check-in/{id_trx_reservasi}/cek-waktu', 'TrxReservasiKamarController@cekWaktuCheckIn');
        Route::get('reservasi/kamar/tersedia', 'KamarSediaController@NomorKamarTersedia');
    });
    Route::get('laporan/customer-baru/{tahun}', 'LaporanController@laporanCustBaru');
    Route::get('laporan/customer/reservasi-terbanyak/{tahun}', 'LaporanController@laporan5CustTerbanyak');
    Route::get('laporan/pendapatan/{tahun}', 'LaporanController@laporanPendapatanPerJenisTamuPerBulan');

});


