<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\JenisKamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Validator;

class JenisKamarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = JenisKamar::where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Season..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = JenisKamar::all();

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
            'jenis_kamar' => 'required',
            'ukuran_kamar' => 'required|numeric',
            'fasilitas_kamar' => 'required',
            'deskripsi' => 'required',
            'kapasitas' => 'required|numeric',
            'harga_dasar' => 'required|numeric',
            'gambar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ], [
            'required' => ':Attribute wajib diisi.',
            'numeric' => ':Attribute hanya boleh berupa angka',
            'image' => ':Attribute harus berupa gambar',
            'mimes' => ':Attribute harus berformat jpeg, png, jpg, gif, atau svg',
            'max' => ':Attribute tidak boleh lebih dari 2MB',
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

        $gambar = $request->file('gambar');
        $gambar->storeAs('public/posts', $gambar->hashName('jenis_kamar/'));
        $addData['gambar'] = $gambar->hashName('jenis_kamar/');

        $season = JenisKamar::create($addData);

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
        $jenisKamar = JenisKamar::find($id);

        if(!is_null($jenisKamar)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Season '.$jenisKamar['nama_season'], $jenisKamar);
        }

        return response([
            'status' => 'F',
            'message' => 'Data Jenis Kamar tidak ditemukan!'
        ], 404);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $jenisKamar = JenisKamar::find($id);

        if(is_null($jenisKamar)){
            return response([
                'status' => 'F',
                'message' => 'Data Jenis Kamar tidak ditemukan!'
            ], 404);
        }

        $userLogin = Auth::user(); //buat updated_by
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'jenis_kamar' => 'required',
            'ukuran_kamar' => 'required|numeric',
            'fasilitas_kamar' => 'required',
            'deskripsi' => 'required',
            'kapasitas' => 'required|numeric',
            'harga_dasar' => 'required|numeric',
            'gambar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048' // sometimes menandakan validasi hanya jika ada
        ], [
            'required' => ':Attribute wajib diisi.',
            'numeric' => ':Attribute hanya boleh berupa angka',
            'image' => ':Attribute harus berupa gambar',
            'mimes' => ':Attribute harus berformat jpeg, png, jpg, gif, atau svg',
            'max' => ':Attribute tidak boleh lebih dari 2MB',
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

        $jenisKamar['jenis_kamar'] = $updateData['jenis_kamar'];
        $jenisKamar['ukuran_kamar'] = $updateData['ukuran_kamar'];
        $jenisKamar['fasilitas_kamar'] = $updateData['fasilitas_kamar'];
        $jenisKamar['deskripsi'] = $updateData['deskripsi'];
        $jenisKamar['kapasitas'] = $updateData['kapasitas'];
        $jenisKamar['harga_dasar'] = $updateData['harga_dasar'];
        $jenisKamar['flag_stat'] = $updateData['flag_stat'];
        $jenisKamar['updated_by'] = $updateData['updated_by'];

        if ($request->hasFile('gambar')){
            $gambar = $request->file('gambar');
            $gambar->storeAs('public/posts', $gambar->hashName('jenis_kamar/'));
            $updateData['gambar'] = $gambar->hashName('jenis_kamar/');

            //delete old image
            Storage::delete('public/posts/jenis_kamar/'.basename($jenisKamar->gambar));

            $jenisKamar['gambar'] = $updateData['gambar'];
        }

        if(!$jenisKamar->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah data!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Mengubah Data Jenis kamar', $jenisKamar);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $jenisKamar = JenisKamar::find($id);

        if(is_null($jenisKamar)){
            return response([
                'status' => 'F',
                'message' => 'Data Jenis Kamar tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('jenis_kamars')->where('id', $jenisKamar->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Jenis Kamar '.$jenisKamar->jenis_kamar, $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }
}
