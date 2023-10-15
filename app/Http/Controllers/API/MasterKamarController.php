<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\JenisKamar;
use App\Models\MasterKamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterKamarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterKamar::where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Kamar..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterKamar::all();

        return new PostResource('T', 'Berhasil Ambil Data All Kamar..', $data);
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
            'nomor_kamar' => 'required|numeric|min:100|unique:App\Models\MasterKamar,nomor_kamar',
            'jenis_bed' => 'required',
            'nomor_lantai' => 'required',
            'smoking_area' => 'required'
        ], [
            'required' => ':Attribute wajib diisi.',
            'numeric' => ':Attribute harus berupa angka',
            'nomor_kamar.min' => 'Nomor kamar minimal 100',
            'unique' => ':Attribute sudah ada.'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(!JenisKamar::find($addData['id_jenis_kamar'])){
            return response([
                'status' => 'F',
                'message' => 'Jenis Kamar tidak diketahui!'
            ], 404);
        }

        if(is_null($addData['flag_stat']) || $addData['flag_stat'] === ""){
            $addData['flag_stat'] = 1;
        };
        
        // sebenarnya ga mungkin yg buat/update ni customer si.. tapi barangkali bad case nya gitu.. jadi tau aja siapa yg bikin/ubah
        $addData['created_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];
        $addData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $kamar = MasterKamar::create($addData);

        if(!$kamar){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Menambah Data Kamar '.$kamar['nomor_kamar'], $kamar);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kamar = MasterKamar::find($id);

        if(!is_null($kamar)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Kamar '.$kamar['nomor_kamar'], $kamar);
        }

        return response([
            'status' => 'F',
            'message' => 'Data Kamar tidak ditemukan!'
        ], 404);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $kamar = MasterKamar::find($id);

        if(is_null($kamar)){
            return response([
                'status' => 'F',
                'message' => 'Data Kamar tidak ditemukan!'
            ], 404);
        }

        $userLogin = Auth::user(); //buat updated_by
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'id_jenis_kamar' => 'required',
            'nomor_kamar' => 'required|numeric|min:100|unique:App\Models\MasterKamar,nomor_kamar,'.$kamar['nomor_kamar'],
            'jenis_bed' => 'required',
            'nomor_lantai' => 'required',
            'smoking_area' => 'required'
        ], [
            'required' => ':Attribute wajib diisi.',
            'numeric' => ':Attribute harus berupa angka',
            'nomor_kamar.min' => 'Nomor kamar minimal 100',
            'unique' => ':Attribute sudah ada.'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if(!JenisKamar::find($updateData['id_jenis_kamar'])){
            return response([
                'status' => 'F',
                'message' => 'Jenis Kamar tidak diketahui!'
            ], 404);
        }

        if(is_null($updateData['flag_stat']) || $updateData['flag_stat'] === ""){
            $updateData['flag_stat'] = 1;
        };
        
        $updateData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $kamar['id_jenis_kamar'] = $updateData['id_jenis_kamar'];
        $kamar['nomor_kamar'] = $updateData['nomor_kamar'];
        $kamar['jenis_bed'] = $updateData['jenis_bed'];
        $kamar['nomor_lantai'] = $updateData['nomor_lantai'];
        $kamar['smoking_area'] = $updateData['smoking_area'];
        $kamar['catatan'] = $updateData['catatan'];
        $kamar['flag_stat'] = $updateData['flag_stat'];
        $kamar['updated_by'] = $updateData['updated_by'];

        if(!$kamar->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah data!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Mengubah Data kamar', $kamar);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kamar = MasterKamar::find($id);

        if(is_null($kamar)){
            return response([
                'status' => 'F',
                'message' => 'Data Kamar tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('master_kamars')->where('id', $kamar->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Kamar '.$kamar->nomor_kamar, $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }
}
