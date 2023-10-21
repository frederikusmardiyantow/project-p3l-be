<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\MasterTrxReservasi;
use Illuminate\Http\Request;

class MasterTrxReservasiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = MasterTrxReservasi::with(["customers", "pic", "fo"])->where('flag_stat', 1)->get();

        return new PostResource('T', 'Berhasil Ambil Data Trx Reservasi..', $data);
    }

    public function getDataForAllFlag()
    {
        $data = MasterTrxReservasi::with(["customers", "pic", "fo"])->get();

        return new PostResource('T', 'Berhasil Ambil Data All Trx Reservasi..', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // nanti waktu bikin reservasi, brrarti otomatis membuatkan data di tabel trx_reservasi_kamar dengan id_trx_reservasi adalah id reservasi yg baru saja dibuat dan id_jenis kamar dari inputan.
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reservasi = MasterTrxReservasi::with(["customers", "pic", "fo", "trxLayanans.layanans", "trxKamars.jenisKamars", "trxKamars.kamars", "invoices.pegawais.role"])->find($id);

        if(!is_null($reservasi)){
            return new PostResource('T', 'Berhasil Mendapatkan Data Trx Reservasi '.$reservasi->customers['nama_customer'], $reservasi);
        }

        return response([
            'status' => 'F',
            'message' => 'Data Trx Reservasi tidak ditemukan!'
        ], 404);
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
