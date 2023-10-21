<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterPegawai;
use App\Models\MasterRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Validator;

class MasterPegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterPegawai::with(['role'])->where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Pegawai..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterPegawai::with(['role'])->get();

        return new PostResource('T', 'Berhasil Ambil Data All Pegawai..', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userLogin = Auth::user(); //buat created_by
        $addData = $request->all();

        $validate = Validator::make($addData, [
            'id_role' => 'required',
            'nama_pegawai' => 'required|string|max:250',
            'email' => [
                'required',
                'email:rfc,dns',
                Rule::unique('App\Models\MasterCustomer', 'email'),
                Rule::unique('App\Models\MasterPegawai', 'email'),
            ],
            'password' => ['required', 'confirmed', Password::min(8)]
        ], [
            'required' => ':Attribute wajib diisi!',
            'email' => 'Email tidak valid!',
            'max' => ':Attribute terlalu panjang! (max: 250 karakter)',
            'unique' => 'Email ini sudah digunakan oleh pengguna lain!',
            'confirmed' => 'Pasword tidak cocok!',
            'min' => 'Password minimal 8 karakter!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        $role = MasterRole::find($addData['id_role']);
        if(!$role || $role->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Role tidak diketahui!'
            ], 404);
        }

        if(is_null($addData['flag_stat']) || $addData['flag_stat'] === ""){
            $addData['flag_stat'] = 1;
        };
        
        // sebenarnya ga mungkin yg buat/update ni customer si.. tapi barangkali bad case nya gitu.. jadi tau aja siapa yg bikin/ubah
        $addData['created_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];
        $addData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $addData['password'] = Hash::make($request->password);

        $pegawai = MasterPegawai::create($addData);
        $pegawai->role;

        if(!$pegawai){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Menambah Data Pegawai '.$pegawai['nama_pegawai'], $pegawai);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pegawai = MasterPegawai::with(['role'])->find($id);

        if(!is_null($pegawai)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Pegawai '.$pegawai['nama_pegawai'], $pegawai);
        }

        return response([
            'status' => 'F',
            'message' => 'Data Pegawai tidak ditemukan!'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pegawai = MasterPegawai::find($id);

        if(is_null($pegawai)){
            return response([
                'status' => 'F',
                'message' => 'Data Pegawai tidak ditemukan!'
            ], 404);
        }

        $userLogin = Auth::user(); //buat updated_by
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'id_role' => 'required',
            'nama_pegawai' => 'required|string|max:250',
            'email' => [
                'required',
                'email:rfc,dns',
                Rule::unique('App\Models\MasterCustomer', 'email'),
                Rule::unique('App\Models\MasterPegawai', 'email')->ignore(MasterPegawai::find($id)), // Mengecualikan diri sendiri
            ]
        ], [
            'required' => ':Attribute wajib diisi!',
            'email' => 'Email tidak valid!',
            'max' => ':Attribute terlalu panjang! (max: 250 karakter)',
            'unique' => 'Email ini sudah digunakan oleh pengguna lain!'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        $role = MasterRole::find($updateData['id_role']);
        if(!$role || $role->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Role tidak diketahui!'
            ], 404);
        }

        if(is_null($updateData['flag_stat']) || $updateData['flag_stat'] === ""){
            $updateData['flag_stat'] = 1;
        };
        
        $updateData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $pegawai['id_role'] = $updateData['id_role'];
        $pegawai['nama_pegawai'] = $updateData['nama_pegawai'];
        $pegawai['email'] = $updateData['email'];
        $pegawai['flag_stat'] = $updateData['flag_stat'];
        $pegawai['updated_by'] = $updateData['updated_by'];

        if(!$pegawai->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah data!'
            ], 400);
        }

        $pegawai->role;
        return new PostResource('T', 'Berhasil Mengubah Data Pegawai', $pegawai);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pegawai = MasterPegawai::find($id);

        if(is_null($pegawai) || $pegawai->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Pegawai tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('master_pegawais')->where('id', $pegawai->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Pegawai '.$pegawai->nama_pegawai, $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }
}
