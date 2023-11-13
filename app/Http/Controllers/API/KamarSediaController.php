<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\JenisKamar;
use App\Models\MasterKamar;
use App\Models\MasterSeason;
use App\Models\MasterTrxReservasi;
use App\Models\TrxReservasiKamar;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Validator;

class KamarSediaController extends Controller
{
    public function KamarSediaPersonal(Request $request){
        $data = $request->all();

        // untuk validasi inputan
        $validate = Validator::make($data, [
            "tgl_check_in"=> "required",
            "tgl_check_out"=> "required",
            "jumlah_dewasa"=> "required",
            "jumlah_anak_anak"=> "required",
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        // untuk pengecekkan inputan tgl check-in dan tgl-check-out benar
        $tglCheckIn = Carbon::parse($data['tgl_check_in']);
        $tglCheckOut = Carbon::parse($data['tgl_check_out']);
        $today = Carbon::now();
        // Cek apakah waktu saat ini sudah melewati jam 14:00:00
        if ($today->hour >= 14) {
            // Jika sudah, atur waktu menjadi jam 13:00:00
            $today->setTime(13, 0, 0);
        }
        
        if($tglCheckIn < $today){ 
            return response([
                'status' => 'F',
                'message' => 'Tanggal check in tidak mungkin sebelum hari ini!!'
            ], 400);
        }
        if($tglCheckOut < $tglCheckIn){
            return response([
                'status' => 'F',
                'message' => 'Tanggal check out tidak mungkin sebelum check in!!'
            ], 400);
        }

        // // untuk pengecekan inputan jumlah orang atau total orang yg nginap dengan inputan kamar yang diinginkan
        // $totalOrang = $data['jumlah_dewasa'] + $data['jumlah_anak_anak'];
        // if($totalOrang == 1){
        //     $kamarYgSeharusnyaDiPesan = ceil($totalOrang/2); //ceil untuk pembulatan angka ke atas
        // }else{
        //     $kamarYgSeharusnyaDiPesan = floor($totalOrang/2); //floor untuk membulatkan angka ke bawah
        // }
        // if($data['jumlah_kamar'] < $kamarYgSeharusnyaDiPesan){
        //     return response([
        //         'status' => 'F',
        //         'message' => 'Jumlah Kamar yang dipesan tidak sesuai penginap!!'
        //     ], 400);
        // }

        // untuk menampilkan kamar beserta jumlah kamar keseluruhan by jenis kamar dan juga menampilkan data jenis kamar beserta harga dasar
        $jumlahKamarPerJenis = MasterKamar::select('master_kamars.id_jenis_kamar', \DB::raw('COUNT(*) as jumlah_kamar'), 'jenis_kamars.jenis_kamar as jenis_kamar', 'jenis_kamars.harga_dasar as harga_dasar')
        ->join('jenis_kamars', 'master_kamars.id_jenis_kamar', '=', 'jenis_kamars.id')
        ->where('master_kamars.flag_stat', '!=', 0)
        ->groupBy('id_jenis_kamar', 'jenis_kamars.jenis_kamar', 'jenis_kamars.harga_dasar')
        ->with('jenisKamars')->get();

        // untuk mengecek kamar yg tersedia di rentang waktu inputan tgl-check-in dan tgl-check-out
        $kamarYgTersedia = MasterTrxReservasi::where(function($query) use ($data) {
            $query->where('waktu_check_in', '<', $data['tgl_check_in'])->where('waktu_check_out', '>', $data['tgl_check_out']);
        })->OrWhere(function($query) use ($data) {
            $query->where('waktu_check_in', '<', $data['tgl_check_out'])->where('waktu_check_out', '>', $data['tgl_check_out']);
        })->OrWhere(function($query) use ($data) {
            $query->where('waktu_check_in', '>=', $data['tgl_check_in'])->where('waktu_check_out', '<=', $data['tgl_check_out']);
        })->where('flag_stat', '!=', 0)->where('status', '!=', 'Batal')->with('trxKamars')->get();

        // untuk mengecek apakah tgl-check-in yg diinputkan masuk dalam season tertentu atau tidak.
        // Di select nya dipakein as. kalo ndak error e 
        $isSeason = MasterSeason::select('master_tarifs.id_season as id_season', 'master_tarifs.perubahan_tarif as perubahan_tarif', 'master_tarifs.id_jenis_kamar as id_jenis_kamar', 'master_seasons.jenis_season as jenis_season', 'master_seasons.nama_season as nama_season')->join('master_tarifs', 'master_seasons.id', '=', 'master_tarifs.id_season')
        ->where(function($query) use ($data) {
            $query->where('master_seasons.tgl_mulai', '<=', $data['tgl_check_in'])->where('master_seasons.tgl_selesai', '>', $data['tgl_check_in'])->where('master_seasons.flag_stat', '!=', 0);
        })->get();

        // melakukan perulangan 
        foreach($jumlahKamarPerJenis as $jumlah){
            foreach($isSeason as $seasonOrNot){
                if($seasonOrNot->id_jenis_kamar == $jumlah->id_jenis_kamar){
                    if($seasonOrNot->jenis_season == 'High'){
                        // echo('masuk high');
                        $harga = $jumlah->harga_dasar + $seasonOrNot->perubahan_tarif;
                    }else{
                        // echo('masuk promo');
                        $harga = $jumlah->harga_dasar - $seasonOrNot->perubahan_tarif;
                    }
                    $jumlah->nama_season = $seasonOrNot->nama_season;
                    $jumlah->perubahan_tarif = $seasonOrNot->perubahan_tarif;
                    $jumlah->jenis_season = $seasonOrNot->jenis_season;
                    $jumlah->harga_saat_ini = $harga;
                }
            }
            // kalo tidak termasuk ke season apapun, maka set jenisnya normal dan lainnya null iar imang response per ojeknya sama.
            if(is_null($jumlah->jenis_season)){
                $jumlah->nama_season = null;
                $jumlah->perubahan_tarif = null;
                $jumlah->jenis_season = 'Normal';
                $jumlah->harga_saat_ini = $jumlah->harga_dasar;
            }
        }

        // melakukan pengecekkan dan kemudian perulangan untuk menghitung kamar yang sudah direservasi
        if($kamarYgTersedia !== null && $kamarYgTersedia->count() > 0){
            foreach($kamarYgTersedia as $reservasi){
                foreach($reservasi->trxKamars as $rooms){
                    $idJK = $rooms->id_jenis_kamar;
                    $objJK = $jumlahKamarPerJenis->first(function ($item) use ($idJK) {
                        return $item->id_jenis_kamar === $idJK;
                    });
    
                    if ($objJK && $objJK->jumlah_kamar > 0) {
                        $objJK->jumlah_kamar -= 1;
                    }
                    
                }
            }

            return response()->json([
                'status' => 'T', 
                'message' => 'Sudah ada reservasi lain di tanggal tersebut!', 
                'data' => $jumlahKamarPerJenis
            ], 200);
            
        }

        return new PostResource('T', 'Belum ada reservasi', $jumlahKamarPerJenis);
    }
    
    
}
