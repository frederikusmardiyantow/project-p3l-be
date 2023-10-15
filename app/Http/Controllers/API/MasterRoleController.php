<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterRole::where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Role..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterRole::all();

        return new PostResource('T', 'Berhasil Ambil Data All Role..', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userLogin = Auth::user(); //buat created_by
        $addData = $request->all();

        $validate = Validator::make($addData, [
            'nama_role' => 'required|max:150'
        ], [
            'required' => ':Attribute wajib diisi.',
            'max' => ':Attribute terlalu panjang! (max: 150 karakter)'
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

        $role = MasterRole::create($addData);

        if(!$role){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Menambah Data Role '.$role['nama_role'], $role);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = MasterRole::find($id);

        if(!is_null($role)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Role '.$role['nama_role'], $role);
        }

        return response([
            'status' => 'F',
            'message' => 'Data Role tidak ditemukan!'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = MasterRole::find($id);

        if(is_null($role)){
            return response([
                'status' => 'F',
                'message' => 'Data Role tidak ditemukan!'
            ], 404);
        }

        $userLogin = Auth::user(); //buat updated_by
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'nama_role' => 'required|max:150'
        ], [
            'required' => ':Attribute wajib diisi.',
            'max' => ':Attribute terlalu panjang! (max: 150 karakter)'
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

        $role['nama_role'] = $updateData['nama_role'];
        $role['flag_stat'] = $updateData['flag_stat'];
        $role['updated_by'] = $updateData['updated_by'];

        if(!$role->save()){
            return response([
                'status' => 'F',
                'message' => 'Gagal mengubah data!'
            ], 400);
        }

        return new PostResource('T', 'Berhasil Mengubah Data Role', $role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = MasterRole::find($id);

        if(is_null($role) || $role->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Role tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('master_roles')->where('id', $role->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Role '.$role->nama_role, $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }
}
