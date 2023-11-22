<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\JenisKamar;
use App\Models\MasterKamar;
use App\Models\MasterTrxReservasi;
use App\Models\TrxReservasiKamar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class TrxReservasiKamarController extends Controller
{

    public function index()
    {
        $data = TrxReservasiKamar::with(["jenisKamars", "kamars", "trxReservasis"])->where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Trx Kamar..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = TrxReservasiKamar::with(["jenisKamars", "kamars", "trxReservasis"])->get();

        return new PostResource('T', 'Berhasil Ambil Data All Trx Kamar..', $data);
    }

    function cekWaktuCheckIn(string $id_trx_reservasi){
        $today = Carbon::now();

        $trxReservasi = MasterTrxReservasi::find($id_trx_reservasi);
        if(!$trxReservasi || $trxReservasi->flag_stat == 0){
            return response([
                'status' => 'F',
                'message' => 'Trx Reservasi tidak diketahui!'
            ], 404);
        }

        if($today < $trxReservasi->waktu_check_in){
            return response([
                'status' => 'F',
                'message' => 'Belum bisa Check In! Waktu Check In di '. Carbon::parse($trxReservasi->waktu_check_in)->format('d M Y \p\u\k\u\l H:i')
            ], 403);
        }
        if($trxReservasi->status == 'Batal' || $trxReservasi->status == 'Out' || $trxReservasi->status == 'In'){
            return response([
                'status' => 'F',
                'message' => 'Trx Reservasi sudah berstatus '. $trxReservasi->status .'!'
            ], 403);
        }else if($trxReservasi->status != 'Terkonfirmasi' && $trxReservasi->status != 'In'){
            return response([
                'status' => 'F',
                'message' => 'Trx Reservasi belum dibayar!'
            ], 403);
        }

        return response([
            'status' => 'T',
            'message' => 'Bisa Lakukan Check In'
        ], 200);
    }

    function fixCheckIn(Request $request, string $id_trx_reservasi){
        $userLogin = Auth::user();
        $data = $request->all();
        
        $jenis = $userLogin->jenis_customer;
        if($jenis){
            return response([
                'status' => 'F',
                'message' => "Anda Customer Jangan coba-coba check-in sendiri ya.."
            ], 403);
        }

        $validasi = [
            'kamar' => 'required|array',
            'deposit' => 'required|numeric'
        ];

        $validate = Validator::make($data, $validasi,[
            'array' => ':Attribute harus berupa array',
            'required' => ':Attribute wajib diisi!',
            'numeric' => ':Attribute hanya boleh berupa Angka'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        // Cari ketersediaan data di db
        $trxReservasi = MasterTrxReservasi::find($id_trx_reservasi);
        if(!$trxReservasi || $trxReservasi->flag_stat == 0){
            return response([
                'status' => 'F',
                'message' => 'Trx Reservasi tidak diketahui!'
            ], 404);
        }

        foreach($data['kamar'] as $kamar){
            if (!isset($kamar['id_kamar']) || !isset($kamar['id_jenis_kamar'])) {
                return response([
                    'status' => 'F',
                    'message' => 'ID Kamar dan ID Jenis Kamar harus ada'
                ], 400);
            }
        }

        // cek id kamar yg terinput valid semua
        foreach($data['kamar'] as $kamar){
            $MstKamar = MasterKamar::find($kamar['id_kamar']);
            if(!$MstKamar || $MstKamar->flag_stat == 0){
                return response([
                    'status' => 'F',
                    'message' => 'Kamar ada yang tidak diketahui!'
                ], 404);
            }
        }

        // cek jumlah data berdasar id trx dan id_jenis kamar
        $cekData = TrxReservasiKamar::where('id_trx_reservasi', $id_trx_reservasi)
        ->where('flag_stat', '!=', 0)
        ->get();

        if(count($data['id_kamar']) != count($cekData)){
            return response([
                'status' => 'F',
                'message' => 'Jumlah Kamar tidak sesuai reservasi!'
            ], 403);
        }

        if($trxReservasi->deposit != null){
            return response([
                'status' => 'F',
                'message' => 'Deposit pada Trx Reservasi sudah terbayar!'
            ], 403);
        }
        if($trxReservasi->status == 'Batal' || $trxReservasi->status == 'Out'){
            return response([
                'status' => 'F',
                'message' => 'Trx Reservasi sudah berstatus '. $trxReservasi->status .'!'
            ], 403);
        }else if($trxReservasi->status != 'Terkonfirmasi' && $trxReservasi->status != 'In'){
            return response([
                'status' => 'F',
                'message' => 'Trx Reservasi belum dibayar!'
            ], 403);
        }

        foreach($data['kamar'] as $kamar){
            if($cekData['id_jenis_kamar'] == $kamar['id_jenis_kamar']){
                $cekData['id_kamar'] = $kamar['id_kamar'];
            }
        }
        $trxReservasi['id_fo'] = $userLogin->id;
        $trxReservasi['deposit'] = $data['deposit'];
        $trxReservasi['status'] = "In";

        if(!$trxReservasi->save() && !$cekData->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal membayarkan deposit dan check in!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Membayarkan deposit dan Check In', $trxReservasi);
    }

    // function pilihKamar(Request $request, string $id_trx_reservasi) {
    //     $userLogin = Auth::user();
    //     $data = $request->all();
    //     $today = Carbon::now(); //untuk waktu reservasi dan generate id booking
        
    //     $jenis = $userLogin->jenis_customer;
    //     if($jenis){
    //         return response([
    //             'status' => 'F',
    //             'message' => "Anda Customer Jangan coba-coba check-in sendiri ya.."
    //         ], 403);
    //     }

    //     $validasi = [
    //         'id_kamar' => 'required'
    //     ];

    //     $validate = Validator::make($data, $validasi,[
    //         'required' => ':Attribute wajib diisi!',
    //     ]);

    //     if($validate->fails()){
    //         return response([
    //             'status' => 'F',
    //             'message' => $validate->errors()
    //         ], 400);
    //     }

    //     $kamar = MasterKamar::find($data['id_kamar']);
    //     if(!$kamar || $kamar->flag_stat == 0){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Kamar tidak diketahui!'
    //         ], 404);
    //     }
    //     $trxReservasi = MasterTrxReservasi::find($id_trx_reservasi);
    //     if(!$trxReservasi || $trxReservasi->flag_stat == 0){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Trx Reservasi tidak diketahui!'
    //         ], 404);
    //     }
    //     if($trxReservasi->status == 'Batal'){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Trx Reservasi sudah dibatalkan!'
    //         ], 403);
    //     }else if($trxReservasi->status == 'Out'){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Trx Reservasi sudah berstatus out!'
    //         ], 403);
    //     }else if($trxReservasi->status != 'Terkonfirmasi' || $trxReservasi->status != 'In'){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Trx Reservasi belum dibayar!'
    //         ], 403);
    //     }

    //     $cek = TrxReservasiKamar::where('id_trx_reservasi', $id_trx_reservasi)->where('flag_stat', '!=', 0)
    //     ->where('id_kamar', '=', $data['id_kamar'])
    //     ->first();

    //     if ($cek) {
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Error! Kamar sudah dipilih!'
    //         ], 404);
    //     }

    //     $trxKamar = TrxReservasiKamar::where('id_trx_reservasi', $id_trx_reservasi)->where('flag_stat', '!=', 0)
    //     ->where('id_kamar', '=', null)
    //     ->first(); // Use first() to get a single record

    //     if (!$trxKamar) {
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Error! Data Trx Reservasi di Trx Kamar tidak ada atau sudah terisi!'
    //         ], 404);
    //     }
        
    //     $trxKamar['id_kamar'] = $data['id_kamar'];

    //     if(!$trxKamar->save()){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Gagal memasukkan kamar!'
    //         ], 400);
    //     }

    //     return new PostResource('T', 'Berhasil Memasukkan data kamar', $trxKamar);
    // }

    // function batalPilihKamar(Request $request, string $id_trx_reservasi){
    //     $userLogin = Auth::user();
    //     $data = $request->all();
    //     $jenis = $userLogin->jenis_customer;
    //     if($jenis){
    //         return response([
    //             'status' => 'F',
    //             'message' => "Anda Customer Jangan coba-coba batalkan check-in sendiri ya.."
    //         ], 403);
    //     }

    //     $validasi = [
    //         'id_kamar' => 'required'
    //     ];

    //     $validate = Validator::make($data, $validasi,[
    //         'required' => ':Attribute wajib diisi!',
    //     ]);

    //     if($validate->fails()){
    //         return response([
    //             'status' => 'F',
    //             'message' => $validate->errors()
    //         ], 400);
    //     }

    //     $kamar = MasterKamar::find($data['id_kamar']);
    //     if(!$kamar || $kamar->flag_stat == 0){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Kamar tidak diketahui!'
    //         ], 404);
    //     }
    //     $trxReservasi = MasterTrxReservasi::find($id_trx_reservasi);
    //     if(!$trxReservasi || $trxReservasi->flag_stat == 0){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Trx Reservasi tidak diketahui!'
    //         ], 404);
    //     }

    //     $trxKamar = TrxReservasiKamar::where('id_trx_reservasi', $id_trx_reservasi)->where('flag_stat', '!=', 0)
    //     ->where('id_kamar', '=', $data['id_kamar'])
    //     ->first(); // Use first() to get a single record

    //     if (!$trxKamar) {
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Error! Data Trx Reservasi di Trx Kamar tidak ada!'
    //         ], 404);
    //     }
        
    //     $trxKamar['id_kamar'] = null;

    //     if(!$trxKamar->save()){
    //         return response([
    //             'status' => 'F',
    //             'message' => 'Gagal membatalkan kamar!'
    //         ], 400);
    //     }

    //     return new PostResource('T', 'Berhasil membatalkan kamar', $trxKamar);
    // }
}
