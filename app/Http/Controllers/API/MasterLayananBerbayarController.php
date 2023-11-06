<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterLayananBerbayar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterLayananBerbayarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterLayananBerbayar::where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Layanan Berbayar..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterLayananBerbayar::all();

        return new PostResource('T', 'Berhasil Ambil Data All Layanan Berbayar..', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userLogin = Auth::user(); //buat created_by
        $addData = $request->all();

        $validate = Validator::make($addData, [
            'nama_layanan' => 'required|string|max:150|unique_with_flag_stat_add:master_layanan_berbayars,nama_layanan,flag_stat',
            'harga' => 'required|numeric',
            'satuan' => 'required|string|max:150'
        ], [
            'required' => ':Attribute wajib diisi!',
            'max' => ':Attribute terlalu panjang! (max: 150 karakter)',
            'numeric' => ':Attribute hanya berupa angka!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(is_null($addData['flag_stat']) || $addData['flag_stat'] === ""){
            $addData['flag_stat'] = 1;
        };
        
        // sebenarnya ga mungkin yg buat/update ni customer si.. tapi barangkali bad case nya gitu.. jadi tau aja siapa yg bikin/ubah
        $addData['created_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];
        $addData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $layanan = MasterLayananBerbayar::create($addData);

        if(!$layanan){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Menambah Data Layanan Berbayar '.$layanan['nama_layanan'], $layanan);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $layanan = MasterLayananBerbayar::find($id);

        if(!is_null($layanan)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Layanan Berbayar '.$layanan['nama_layanan'], $layanan);
        }

        return response([
            'status' => 'F',
            'message' => 'Data Layanan Berbayar tidak ditemukan!'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $layanan = MasterLayananBerbayar::find($id);

        if(is_null($layanan)){
            return response([
                'status' => 'F',
                'message' => 'Data Layanan Berbayar tidak ditemukan!'
            ], 404);
        }

        $userLogin = Auth::user(); //buat updated_by
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'nama_layanan' => 'required|string|max:150|unique_with_flag_stat_update:master_layanan_berbayars,nama_layanan,flag_stat,'.$layanan['id'],
            'harga' => 'required|numeric',
            'satuan' => 'required|string|max:150'
        ], [
            'required' => ':Attribute wajib diisi!',
            'max' => ':Attribute terlalu panjang! (max: 150 karakter)',
            'numeric' => ':Attribute hanya berupa angka!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(is_null($updateData['flag_stat']) || $updateData['flag_stat'] === ""){
            $updateData['flag_stat'] = 1;
        };
        
        $updateData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $layanan['nama_layanan'] = $updateData['nama_layanan'];
        $layanan['harga'] = $updateData['harga'];
        $layanan['satuan'] = $updateData['satuan'];
        $layanan['keterangan'] = $updateData['keterangan'];
        $layanan['flag_stat'] = $updateData['flag_stat'];
        $layanan['updated_by'] = $updateData['updated_by'];

        if(!$layanan->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah data!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Mengubah Data Layanan Berbayar', $layanan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $layanan = MasterLayananBerbayar::find($id);

        if(is_null($layanan) || $layanan->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Layanan berbayar tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('master_layanan_berbayars')->where('id', $layanan->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Layanan berbayar '.$layanan->nama_layanan, $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }
}
