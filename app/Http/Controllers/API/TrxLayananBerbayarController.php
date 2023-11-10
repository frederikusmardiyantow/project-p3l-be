<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterLayananBerbayar;
use App\Models\MasterTrxReservasi;
use App\Models\TrxLayananBerbayar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class TrxLayananBerbayarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userLogin = Auth::user(); //buat created_by
        $addData = $request->all();

        $validate = Validator::make($addData, [
            'id_layanan' => 'required',
            'id_trx_reservasi' => 'required',
            'jumlah' => 'required',
            'total_harga' => 'required',
        ], [
            'required' => ':Attribute wajib diisi.',
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        $layanan = MasterLayananBerbayar::find($addData['id_layanan']);
        if(!$layanan || $layanan->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Layanan tidak diketahui!'
            ], 404);
        }
        $trxReservasi = MasterTrxReservasi::find($addData['id_trx_reservasi']);
        if(!$trxReservasi || $trxReservasi->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Transaksi Reservasi tidak diketahui!'
            ], 404);
        }

        if(is_null($addData['flag_stat']) || $addData['flag_stat'] === ""){
            $addData['flag_stat'] = 1;
        };
        
        // sebenarnya ga mungkin yg buat/update ni customer si.. tapi barangkali bad case nya gitu.. jadi tau aja siapa yg bikin/ubah
        $addData['created_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];
        $addData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $trxLayanan = TrxLayananBerbayar::create($addData);
        $trxLayanan->trxReservasis;
        $trxLayanan->layanans;

        if(!$trxLayanan){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Menambah Data Tarif', $trxLayanan);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
