<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\JenisKamar;
use App\Models\MasterCustomer;
use App\Models\MasterTrxReservasi;
use App\Models\TrxLayananBerbayar;
use App\Models\TrxReservasiKamar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

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
    public function entryDataReservasi(Request $request)
    {
        $userLogin = Auth::user();
        $jenis = $userLogin->jenis_customer;
        $addData = $request->all();
        $today = Carbon::now(); //untuk waktu reservasi dan generate id booking

        if($jenis){
            $prefiks = 'P';
        }else{
            $prefiks = 'G';
        }

        $validasi = [
            'kamar' => 'required|array',
            'jumlah_dewasa' => 'required',
            'jumlah_anak_anak' => 'required',
            'waktu_check_in' => 'required',
            'waktu_check_out' => 'required',
            'jumlah_malam' => 'required',
        ];
        if($prefiks === 'G'){
            // cek jika yg login adalah SM maka id customer wajib diisi!
            $validasi['id_customer'] = 'required';
        }

        $validate = Validator::make($addData, $validasi,[
            'array' => ':Attribute harus berupa array',
            'required' => ':Attribute wajib diisi!',
            'status.in' => 'Status hanya Menunggu Pembayaran, Terkonfirmasi, Check In, Check Out, dan Batal'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        if($prefiks == 'P'){
            $addData['id_customer'] = $userLogin->id;
        }else{
            $cekCustomer = MasterCustomer::find($addData['id_customer']);
            if(!$cekCustomer || $cekCustomer->flag_stat === 0 ||$cekCustomer->jenis_customer === 'P'){
                return response([
                    'status' => 'F',
                    'message' => 'Customer tidak diketahui!'
                ], 404);
            }
        }

        // untuk generate id booking
        $increment = '001';
        $tglTukIdBooking = $today->format('dmy');
        $noBookTukHariIni = MasterTrxReservasi::select('id_booking')->where('id_booking', 'LIKE', $prefiks.$tglTukIdBooking.'-%')->latest()->first();
        if(!is_null($noBookTukHariIni)){
            $parts = explode('-', $noBookTukHariIni);
            $no = end($parts);
            $increment = (int)$no + 1;
        }
        $angka = intval($increment); // Konversi ke integer
        $pemformatan3digit = sprintf('%03d', $angka);
        $addData['id_booking'] = $prefiks.$tglTukIdBooking.'-'.$pemformatan3digit;

        if($prefiks == 'G'){
            $addData['id_pic'] = $userLogin['id'];
        }

        // kamar berisi array of object. masing2 object berisi id_jenis_kamar dan jumlahnya.
        $jumlahKamarPesan = 0;
        $KamarPesan = []; //deklarasi array penampung untuk kamar yang dipesan. isinya id jenis dan harga per malam
        $totalHarga = 0;
        foreach($addData['kamar'] as $kamar){
            if (!isset($kamar['id_jenis_kamar']) || !isset($kamar['jumlah']) || !isset($kamar['harga_per_malam'])) {
                return response([
                    'status' => 'F',
                    'message' => 'ID Jenis Kamar, Jumlah Kamar, dan Harga Per Malam harus ada'
                ], 400);
            }
            $jenisKamar = $kamar['id_jenis_kamar'];
            $jumlahKamar = $kamar['jumlah'];
            $hargaPerMalam = $kamar['harga_per_malam'];

            $jumlahKamarPesan += $jumlahKamar;

            for($i = 0; $i < $jumlahKamar; $i++){
                $KamarPesan[] = [
                    'id_jenis_kamar' => $jenisKamar,
                    'harga_per_malam' => $hargaPerMalam
                ];
                $totalHarga += $hargaPerMalam;
            }
        }
        $addData['total_harga'] = $totalHarga * $addData['jumlah_malam'];

        // untuk pengecekan kembali jumlah orang atau total orang yg nginap dengan inputan kamar yang dilakukan
        $totalOrang = $addData['jumlah_dewasa'] + $addData['jumlah_anak_anak'];
        if($totalOrang == 1){
            $kamarYgSeharusnyaDiPesan = ceil($totalOrang/2); //ceil untuk pembulatan angka ke atas
        }else{
            $kamarYgSeharusnyaDiPesan = floor($totalOrang/2); //floor untuk membulatkan angka ke bawah
        }
        if($jumlahKamarPesan < $kamarYgSeharusnyaDiPesan){
            return response([
                'status' => 'F',
                'message' => "Jumlah Kamar yang dipesan tidak sesuai penginap. Dengan jumlah {$totalOrang} Orang, minimal pesan {$kamarYgSeharusnyaDiPesan} kamar ya!!"
            ], 400);
        }

        if(is_null($addData['flag_stat']) || $addData['flag_stat'] === ""){
            $addData['flag_stat'] = 1;
        };
        
        // sebenarnya ga mungkin yg buat/update ni customer si.. tapi barangkali bad case nya gitu.. jadi tau aja siapa yg bikin/ubah
        $addData['created_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];
        $addData['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        // menghitung jumlah malam
        $tglCheckIn = Carbon::parse($addData['waktu_check_in']);
        $tglCheckOut = Carbon::parse($addData['waktu_check_out']);
        // $addData['jumlah_malam'] = $tglCheckIn->diffInDays($tglCheckOut);
        $addData['status'] = 'Menunggu Pembayaran';
        $addData['uang_jaminan'] = 0;
        $addData['deposit'] = 0;
        $addData['waktu_reservasi'] = $today->format('Y-m-d H:i:s');

        // pengecekan keseluruhan id jenis kamar yg diinputkan dulu
        foreach($KamarPesan as $kamar){
            $jenis = JenisKamar::find($kamar['id_jenis_kamar']);
            if(!$jenis || $jenis->flag_stat === 0){
                return response([
                    'status' => 'F',
                    'message' => 'Jenis Kamar ada yang tidak diketahui!'
                ], 404);
            }
        }

        $trxReservasi = MasterTrxReservasi::create($addData);

        if(!$trxReservasi){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        // baru lah ini di add data
        foreach ($KamarPesan as $kamar) {
            $trxKamar = TrxReservasiKamar::create([
                'id_jenis_kamar' => $kamar['id_jenis_kamar'],
                'id_trx_reservasi' => $trxReservasi->id,
                'harga_per_malam' => $kamar['harga_per_malam'],
                'flag_stat' => 1,
                'created_by' => $addData['created_by'],
                'updated_by' => $addData['updated_by']
            ]);

            if(!$trxKamar){
                return response([
                    'status' => 'F',
                    'message' => 'Terjadi kesalahan pada server'
                ], 500);
            }
        }
        $trxReservasi->trxKamars;
        $trxReservasi->customers;

        return new PostResource('T', 'Berhasil Menambah Data Reservasi', $trxReservasi);

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
        $reservasi = MasterTrxReservasi::find($id);

        if(is_null($reservasi) || $reservasi->flag_stat === 0){
            return response([
                'status' => 'F',
                'message' => 'Data Reservasi tidak ditemukan!'
            ], 404);
        }

        $deleted = DB::table('master_trx_reservasis')->where('id', $reservasi->id)->update(['flag_stat' => 0]);

        if($deleted){
            return new PostResource('T', 'Berhasil Menghapus Data Reservasi', $deleted);
        }

        return response([
            'status' => 'F',
            'message' => 'Gagal menghapus data!'
        ], 400);
    }

    public function uploadBuktiPembayaran(Request $request, string $id) { //id = idReservasi
        $userLogin = Auth::user();
        $today = Carbon::now(); //untuk waktu pembayaran
        $jenis = $userLogin->jenis_customer;
        $data = $request->all();

        if($jenis){
            $customer = 'P';
        }else{
            $customer = 'G';
        }

        $cekReservasi = MasterTrxReservasi::find($id);
        if(is_null($cekReservasi)){
            return response([
                'status' => 'F',
                'message' => 'Data Trx Reservasi tidak ditemukan!'
            ], 404);
        }

        // dicek apakah kolom bukti_pembayaran sudah terisi belom. kalo sudah ga bisa lagi
        if(!is_null($cekReservasi->bukti_pembayaran)){
            return response([
                'status' => 'F',
                'message' => 'Sudah dilakukan upload bukti pembayaran!'
            ], 400);
        }

        $validasi = [
            'gambar_bukti' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        if($customer === 'G'){
            // cek jika yg login adalah SM maka jumlah dp wajib diisi!
            $validasi['uang_jaminan'] = 'required|integer';
        }

        $validate = Validator::make($data, $validasi, [
            'required' => ':Attribute wajib diisi.',
            'integer' => ':Attribute hanya berupa angka',
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
        //jika uang jaminan tidak memenuhi syarat
        if($customer === 'G' && ($data['uang_jaminan'] <= 0 || $data['uang_jaminan'] < ($cekReservasi['total_harga']/2))){
            return response([
                'status' => 'F',
                'message' => 'Gagal upload! Uang Jaminan kurang!'
            ], 403);
        }

        $data['updated_by'] = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

        $gambar = $request->file('gambar_bukti');
        $gambar->storeAs('public/posts', $gambar->hashName('bukti_bayar/'));
        $data['gambar_bukti'] = $gambar->hashName('bukti_bayar/');

        if($customer == 'P'){
            $uang_jaminan = $cekReservasi->total_harga;
        }else{
            $uang_jaminan = $data['uang_jaminan'];
        }

        $uploadBukti = MasterTrxReservasi::where('id', $id)->update([
            'bukti_pembayaran' => $data['gambar_bukti'], 
            'uang_jaminan' => $uang_jaminan,
            'waktu_pembayaran' => $today->format('Y-m-d H:i:s'),
            'status' => 'Terkonfirmasi',
            'updated_by' => $data['updated_by']
        ]);
        $dataReservasi = MasterTrxReservasi::find( $id );

        if(!$uploadBukti){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Mengunggah Bukti Pembayaran', $dataReservasi);
    }

    public function cekPengembalianDanaOrTidak(string $id){
        $today = Carbon::now();

        $cekReservasi = MasterTrxReservasi::find($id);
        if(!$cekReservasi){
            return response([
                'status' => 'F',
                'message' => 'Data Trx Reservasi tidak ditemukan!'
            ], 404);
        }
        if($cekReservasi['status'] == 'Menunggu Pembayaran'){
            return response([
                'status' => 'F',
                'message' => 'Maaf, Reservasi belum dibayar!'
            ], 403);
        }
        if($cekReservasi['status'] == 'Batal'){
            return response([
                'status' => 'F',
                'message' => 'Reservasi sudah dibatalkan!'
            ], 403);
        }

        $checkIn = Carbon::parse($cekReservasi['waktu_check_in']);
        $selisihHari = $today->diffInDays($checkIn);

        if ($selisihHari <= 7){
            return response([
                'status' => 'F',
                'message' => 'Uang jaminan tidak bisa dikembalikan'
            ], 403);
        }

        return response([
            'status' => 'T',
            'message' => 'Uang jaminan akan dikembalikan'
        ], 200);
    }

    public function pembatalanReservasi(string $id) {
        $userLogin = Auth::user();
        $today = Carbon::now();

        $cekReservasi = MasterTrxReservasi::find($id);
        if(!$cekReservasi){
            return response([
                'status' => 'F',
                'message' => 'Data Trx Reservasi tidak ditemukan!'
            ], 404);
        }
        if($cekReservasi->status == 'Batal'){
            return response([
                'status' => 'F',
                'message' => "Transaksi sudah dibatalkan sejak $cekReservasi->updated_at"
            ], 403);
        }

        $checkIn = Carbon::parse($cekReservasi['waktu_check_in']);
        $selisihHari = $today->diffInDays($checkIn);

        if ($selisihHari > 7 && $cekReservasi['status'] != 'Menunggu Pembayaran'){
            $uang_jaminan = 0;
        }else{
            $uang_jaminan = $cekReservasi['uang_jaminan'];
        }

        $updated_by = $userLogin['nama_pegawai'] ? $userLogin['nama_pegawai'] : 'Customer: '.$userLogin['nama_customer'];

         // Update status pada tabel trx_reservasi_kamars
        $updateStatusTrxReservasiKamars = TrxReservasiKamar::where('id_trx_reservasi', $id)->update([
            'flag_stat' => 0
        ]);
        if(!$updateStatusTrxReservasiKamars){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        // Update status pada tabel trx_layanan_berbayar
        $updateStatusTrxLayananBerbayar = TrxLayananBerbayar::where('id_trx_reservasi', $id)->update([
            'flag_stat' => 0
        ]);
        if(!$updateStatusTrxLayananBerbayar){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        $updateStatus = MasterTrxReservasi::where('id', $id)->update([
            'status' => 'Batal',
            'uang_jaminan' => $uang_jaminan,
            'updated_by' => $updated_by
        ]);

        if(!$updateStatus){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }

        return new PostResource('T', 'Berhasil Membatalkan Reservasi', $updateStatus);
    }
}
