<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterSeason;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterSeasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterSeason::where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Season..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterSeason::all();

        return new PostResource('T', 'Berhasil Ambil Data All Season..', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userLogin = Auth::user(); //buat created_by
        $addData = $request->all();

        $validate = Validator::make($addData, [
            'nama_season' => 'required|max:255|unique_with_flag_stat_add:master_seasons,nama_season,flag_stat',
            'jenis_season' => 'required|max:50',
            'tgl_mulai' => 'required|date|date_format:Y-m-d H:i:s', //formatnya harus Y-m-d H:i:s
            'tgl_selesai' => 'required|date|date_format:Y-m-d H:i:s', //formatnya harus Y-m-d H:i:s
        ], [
            'required' => ':Attribute wajib diisi.',
            'unique' => ':Attribute sudah ada.',
            'max' => ':Attribute terlalu panjang!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        // Jika validasi berhasil, lanjutkan untuk memeriksa jarak minimal
        $tglMulai = Carbon::parse($addData['tgl_mulai']);
        $tglSelesai = Carbon::parse($addData['tgl_selesai']);
        $today = Carbon::now();
        
        if($tglMulai <= $today){ 
            return response([
                'status' => 'F',
                'message' => 'Tanggal mulai season tidak boleh sebelum hari ini!!'
            ], 400);
        }
        if($tglMulai->diffInMonths($today) < 2){ 
            return response([
                'status' => 'F',
                'message' => 'Tanggal mulai season harus berjarak minimal 2 bulan dari hari ini!!'
            ], 400);
        }
        if($tglSelesai <= $tglMulai){
            return response([
                'status' => 'F',
                'message' => 'Tanggal selesai harus lebih besar dari Tanggal mulai!!'
            ], 400);
        }

        if(is_null($addData['flag_stat']) || $addData['flag_stat'] === ""){
            $addData['flag_stat'] = 1;
        };
        
        // sebenarnya ga mungkin yg buat/update ni customer si.. tapi barangkali bad case nya gitu.. jadi tau aja siapa yg bikin/ubah
        $addData['created_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];
        $addData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $season = MasterSeason::create($addData);

        if(!$season){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Menambah Data Season '.$season['nama_season'], $season);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $season = MasterSeason::find($id);

        if(!is_null($season)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Season '.$season['nama_season'], $season);
        }

        return response([
            'status' => 'F',
            'message' => 'Data tidak ditemukan!'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $season = MasterSeason::find($id);

        if(is_null($season)){
            return response([
                'status' => 'F',
                'message' => 'Data Season tidak ditemukan!'
            ], 404);
        }

        $userLogin = Auth::user(); //buat updated_by
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'nama_season' => 'required|max:255|unique_with_flag_stat_update:master_seasons,nama_season,flag_stat,'.$season['id'],
            'jenis_season' => 'required|max:50',
            'tgl_mulai' => 'required|date|date_format:Y-m-d H:i:s', //formatnya harus Y-m-d H:i:s
            'tgl_selesai' => 'required|date|date_format:Y-m-d H:i:s', //formatnya harus Y-m-d H:i:s
        ], [
            'required' => ':Attribute wajib diisi.',
            'unique' => ':Attribute sudah ada.',
            'max' => ':Attribute terlalu panjang!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        // Jika validasi berhasil, lanjutkan untuk memeriksa jarak minimal
        $tglMulai = Carbon::parse($updateData['tgl_mulai']);
        $tglSelesai = Carbon::parse($updateData['tgl_selesai']);
        $today = Carbon::now();
        
        if($tglMulai <= $today){ 
            return response([
                'status' => 'F',
                'message' => 'Tanggal mulai season tidak boleh sebelum hari ini!!'
            ], 400);
        }
        if($tglMulai->diffInMonths($today) < 2){ 
            return response([
                'status' => 'F',
                'message' => 'Tanggal mulai season harus berjarak minimal 2 bulan dari hari ini!!'
            ], 400);
        }
        if($tglSelesai <= $tglMulai){
            return response([
                'status' => 'F',
                'message' => 'Tanggal selesai harus lebih besar dari Tanggal mulai!!'
            ], 400);
        }

        if(is_null($updateData['flag_stat']) || $updateData['flag_stat'] === ""){
            $updateData['flag_stat'] = 1;
        };
        
        $updateData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $season['nama_season'] = $updateData['nama_season'];
        $season['jenis_season'] = $updateData['jenis_season'];
        $season['tgl_mulai'] = $updateData['tgl_mulai'];
        $season['tgl_selesai'] = $updateData['tgl_selesai'];
        $season['flag_stat'] = $updateData['flag_stat'];
        $season['updated_by'] = $updateData['updated_by'];

        if(!$season->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah data!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Mengubah Data Season', $season);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $season = MasterSeason::find($id);

        if(is_null($season) || $season->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Season tidak ditemukan!'
            ], 404);
        }
        
        // Jika validasi berhasil, lanjutkan untuk memeriksa jarak minimal
        $tglMulai = Carbon::parse($season['tgl_mulai']);
        $today = Carbon::now();
        
        if(($tglMulai <= $today) || ($tglMulai->diffInMonths($today) < 2)){ 
            return response([
                'status' => 'F',
                'message' => 'Season hanya boleh dihapus dengan jarak minimal 2 bulan setelah hari ini!!'
            ], 400);
        }

        $deleted = DB::table('master_seasons')->where('id', $season->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Season '.$season->nama_season, $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }

}
