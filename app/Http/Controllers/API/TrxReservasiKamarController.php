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

    public function cekWaktuCheckIn(string $id_trx_reservasi){
        $today = Carbon::now();
        $tanggal_hr_ini = date('Y-m-d');

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
        if($tanggal_hr_ini > date('Y-m-d', strtotime($trxReservasi->waktu_check_in))){
            $trxReservasi->update([
                'status' => 'Batal'
            ]);
            
            return response([
                'status' => 'F',
                'message' => 'Sudah tidak dapat melakukan check-in. Melewati Batas!'
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

    public function fixCheckIn(Request $request, string $id_trx_reservasi){
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
            'deposit' => 'required|numeric|min:1'
        ];

        $validate = Validator::make($data, $validasi,[
            'array' => ':Attribute harus berupa array',
            'required' => ':Attribute wajib diisi!',
            'numeric' => ':Attribute hanya boleh berupa Angka',
            'min' => ':Attribute tidak boleh <= 0'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        // Cari ketersediaan data di db
        $trxReservasi = MasterTrxReservasi::with('customers', 'pic', 'fo', 'trxKamars', 'trxLayanans', 'invoices')->find($id_trx_reservasi);
        if(!$trxReservasi || $trxReservasi->flag_stat == 0){
            return response([
                'status' => 'F',
                'message' => 'Trx Reservasi tidak diketahui!'
            ], 404);
        }

        foreach($data['kamar'] as $kamar){
            if (!isset($kamar['id_kamar'])) {
                return response([
                    'status' => 'F',
                    'message' => 'ID Kamar harus ada'
                ], 400);
            }
        }
        // Menghitung berapa kali masing-masing nilai muncul
        $countValues = array_count_values(array_column($data['kamar'], 'id_kamar'));

        // Memeriksa apakah ada nilai yang muncul lebih dari satu kali
        $nonUniqueValues = array_filter($countValues, function ($count) {
            return $count > 1;
        });

        if (!empty($nonUniqueValues)) {
            return response([
                'status' => 'F',
                'message' => 'Nilai id_kamar harus unik dalam array kamar.'
            ], 400);
        }

        // cek id kamar yg terinput valid semua
        $tempNoKamar = []; //tuk nampung semua nomor kamar yang dipilih
        foreach($data['kamar'] as $kamar){
            $MstKamar = MasterKamar::where('flag_stat', '!=', 0)->find($kamar['id_kamar']);
            if(!$MstKamar || $MstKamar->flag_stat == 0){
                return response([
                    'status' => 'F',
                    'message' => 'Kamar ada yang tidak diketahui!'
                ], 404);
            }
            //lakukan push ke tempNoKamar
            array_push($tempNoKamar, $MstKamar->nomor_kamar);
        }

        // cek jumlah data berdasar id trx 
        $cekData = TrxReservasiKamar::where('id_trx_reservasi', $id_trx_reservasi)
        ->where('flag_stat', '!=', 0)
        ->get();

        if(count($data['kamar']) != count($cekData)){
            return response([
                'status' => 'F',
                'message' => 'Jumlah Kamar tidak sesuai reservasi!'
            ], 403);
        }

        if($trxReservasi->deposit != 0){
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

        try{
            foreach($cekData as $cek){
                //lakukan perulangan berdasar tempNoKamar yg sudah didifine diatas td
                foreach($tempNoKamar as $kamar){
                    $MstKamar = MasterKamar::with('jenisKamars')->where('nomor_kamar', $kamar)->where('flag_stat', '!=', 0)->first();
                    if($cek['id_jenis_kamar'] == $MstKamar->id_jenis_kamar){
                        $cek->id_kamar = $MstKamar->id;
                        $cek->updated_by = $userLogin['nama_pegawai'];
                        unset($tempNoKamar[array_search($kamar, $tempNoKamar)]);
                        break;
                    }
                }
            }

            if(count($tempNoKamar) != 0){
                return response([
                    'status' => 'F',
                    'message' => 'Check In gagal karena jenis dari kamar '. implode(', ', $tempNoKamar).' tidak cocok dengan pesanan!'
                ], 403);
            }else{
                $trxReservasi['id_fo'] = $userLogin->id;
                $trxReservasi['deposit'] = $data['deposit'];
                $trxReservasi['status'] = "In";
                $trxReservasi['updated_by'] = $userLogin['nama_pegawai'];
                // Setelah perulangan selesai, simpan semua objek yang telah diubah
                foreach ($cekData as $cek) {
                    $cek->save();
                }
                $trxReservasi->save();

                return new PostResource('T', 'Berhasil Membayarkan deposit dan Check In untuk semua kamar yang dipilih', $trxReservasi);
            }
        } catch (\Exception $e) {
            // Tangkap pengecualian dan berikan respons dengan pesan kesalahan
            return response([
                'status' => 'F',
                'message' => 'Gagal melakukan update: ' . $e->getMessage()
            ], 500);
        }
    }
}
