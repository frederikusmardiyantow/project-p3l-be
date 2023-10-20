<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\JenisKamar;
use App\Models\MasterSeason;
use App\Models\MasterTarif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterTarifController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterTarif::with(['seasons', 'jenisKamars'])->where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Tarif..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterTarif::with(['seasons', 'jenisKamars'])->get();

        return new PostResource('T', 'Berhasil Ambil Data All Tarif..', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userLogin = Auth::user(); //buat created_by
        $addData = $request->all();

        $validate = Validator::make($addData, [
            'id_jenis_kamar' => 'required',
            'id_season' => 'required',
            'perubahan_tarif' => 'required|numeric'
        ], [
            'required' => ':Attribute wajib diisi.',
            'numeric' => ':Attribute harus berupa angka'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        $jenis = JenisKamar::find($addData['id_jenis_kamar']);
        if(!$jenis || $jenis->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Jenis Kamar tidak diketahui!'
            ], 404);
        }
        $season = MasterSeason::find($addData['id_season']);
        if(!$season || $season->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Season tidak diketahui!'
            ], 404);
        }

        // cek bahwa id jenis kamar bersamaan id season tidak boleh ada di db sebelumnya.
        $cek = MasterTarif::where('id_jenis_kamar', $addData['id_jenis_kamar'])
            ->where('id_season', $addData['id_season'])
            ->first();

        if ($cek) {
            // Kombinasi id_jenis_kamar dan id_season sudah ada dalam database
            return response([
                'status' => 'F',
                'message' => 'Kombinasi id_jenis_kamar dan id_season sudah ada dalam database.'
            ], 400);
        }

        if(is_null($addData['flag_stat']) || $addData['flag_stat'] === ""){
            $addData['flag_stat'] = 1;
        };
        
        // sebenarnya ga mungkin yg buat/update ni customer si.. tapi barangkali bad case nya gitu.. jadi tau aja siapa yg bikin/ubah
        $addData['created_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];
        $addData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $tarif = MasterTarif::create($addData);
        $tarif->seasons;
        $tarif->jenisKamars;

        if(!$tarif){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Menambah Data Tarif', $tarif);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tarif = MasterTarif::with(['seasons', 'jenisKamars'])->find($id);

        if(!is_null($tarif)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Season', $tarif);
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
        $tarif = MasterTarif::with(['seasons', 'jenisKamars'])->find($id);

        if(is_null($tarif)){
            return response([
                'status' => 'F',
                'message' => 'Data Tarif tidak ditemukan!'
            ], 404);
        }

        $userLogin = Auth::user(); //buat updated_by
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'id_jenis_kamar' => 'required',
            'id_season' => 'required',
            'perubahan_tarif' => 'required|numeric'
        ], [
            'required' => ':Attribute wajib diisi.',
            'numeric' => ':Attribute harus berupa angka'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        $jenis = JenisKamar::find($updateData['id_jenis_kamar']);
        if(!$jenis || $jenis->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Jenis Kamar tidak diketahui!'
            ], 404);
        }
        $season = MasterSeason::find($updateData['id_season']);
        if(!$season || $season->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Season tidak diketahui!'
            ], 404);
        }

        // cek bahwa id jenis kamar bersamaan id season tidak boleh ada di db sebelumnya.
        $cek = MasterTarif::where('id_jenis_kamar', $updateData['id_jenis_kamar'])
            ->where('id_season', $updateData['id_season'])
            ->where('id','!=', $tarif['id'])
            ->first();

        if ($cek) {
            // Kombinasi id_jenis_kamar dan id_season sudah ada dalam database
            return response([
                'status' => 'F',
                'message' => 'Kombinasi id_jenis_kamar dan id_season sudah ada dalam database.'
            ], 400);
        }

        if(is_null($updateData['flag_stat']) || $updateData['flag_stat'] === ""){
            $updateData['flag_stat'] = 1;
        };
        
        $updateData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $tarif['id_jenis_kamar'] = $updateData['id_jenis_kamar'];
        $tarif['id_season'] = $updateData['id_season'];
        $tarif['perubahan_tarif'] = $updateData['perubahan_tarif'];

        if(!$tarif->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah tarif!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Mengubah Data Tarif', $tarif);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tarif = MasterTarif::with(['seasons', 'jenisKamars'])->find($id);

        if(is_null($tarif) || $tarif->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Tarif tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('master_tarifs')->where('id', $tarif->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Tarif', $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }
}
