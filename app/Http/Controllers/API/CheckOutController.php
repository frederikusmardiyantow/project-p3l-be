<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Invoice;
use App\Models\MasterTrxReservasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class CheckOutController extends Controller
{
    public function TampilDataNotaLunasCheckOut(string $id_trx_reservasi) {
        $cekReservasi = MasterTrxReservasi::with(["trxKamars", "trxKamars.jenisKamars", "trxKamars.kamars", "trxLayanans", "trxLayanans.layanans", "customers", "pic", "fo"])->find($id_trx_reservasi);
        if(!$cekReservasi){
            return response([
                'status' => 'F',
                'message' => 'Data Trx Reservasi tidak ditemukan!'
            ], 404);
        }
        if($cekReservasi['status'] == 'Batal'){
            return response([
                'status' => 'F',
                'message' => 'Reservasi sudah dibatalkan!'
            ], 403);
        }
        if($cekReservasi['status'] != 'Out'){
            return response([
                'status' => 'F',
                'message' => 'Nota Lunas belum bisa digenerate karena status reservasi belum check out!'
            ], 403);
        }
        // Catatan: invoice ini cuma buat ambil data no invoice dan id_trx_reservasi pegawainya. untuk total harga kamar dan layanan, pajak, dan total semua dihitung dalam kode ini (tidak diambil dari tabel invoice. Untuk mencocokan kebenaran data aja si, jd dihitung ulang)
        $invoice = Invoice::with(["trxReservasis", "pegawais"])->where('id_trx_reservasi', $id_trx_reservasi)->first();
        if(!$invoice){
            return response([
                'status' => 'F',
                'message' => 'Data Invoice tidak ditemukan!'
            ], 404);
        }
        // hitung jumlah masing-masing jenis kamar yang dipesan dan total harga
        $trxKamarPesananPerJenis = [];
        $totalHargaKamarAll = 0;
        foreach ($cekReservasi->trxKamars as $trx) {
            $jenisKamar = $trx->jenisKamars->jenis_kamar;
            $hargaPerMalam = $trx->harga_per_malam;
            // $totalPerJenis = $hargaPerMalam;
            $totalHargaKamarAll += $trx->harga_per_malam;
        
            if (array_key_exists($jenisKamar, $trxKamarPesananPerJenis)) {
                $trxKamarPesananPerJenis[$jenisKamar]->jumlah += 1;
                $trxKamarPesananPerJenis[$jenisKamar]->total_per_jenis += $hargaPerMalam;
            } else {
                $trxKamarPesananPerJenis[$jenisKamar] = (object) [
                    'jenis_kamar' => $jenisKamar,
                    'jumlah' => 1,
                    'harga_per_malam' => $hargaPerMalam,
                    'total_per_jenis' => $hargaPerMalam,
                ];
            }
        }

        // hitung jumlah masing-masing trx layanan yang dipesan dan total harga
        $trxLayananPesan = [];
        $totalHargaLayananAll = 0;
        foreach ($cekReservasi->trxLayanans as $trx) {
            $namaLayanan = $trx->layanans->nama_layanan;
            $hargaSatuan = $trx->layanans->harga;
            $totalHargaLayananAll += $trx->total_harga;
            $tglPemakaian = Carbon::parse($trx->waktu_pemakaian)->format('Y-m-d');
        
            // Pengecekan menggunakan namaLayanan dan tgl_pemakaian
            $key = $namaLayanan . '_' . $tglPemakaian;

            if (array_key_exists($key, $trxLayananPesan)) {
                $trxLayananPesan[$key]->jumlah += $trx->jumlah;
                $trxLayananPesan[$key]->total_per_layanan += $hargaSatuan;
            } else {
                $trxLayananPesan[$key] = (object) [
                    'nama_layanan' => $namaLayanan,
                    'tgl_pemakaian' => $tglPemakaian,
                    'jumlah' => $trx->jumlah,
                    'harga_per_satuan' => $hargaSatuan,
                    'total_per_layanan' => $trx->total_harga,
                ];
            }
        }

        // hitung pajak layanan (10%)
        $pajakLayanan = ($totalHargaLayananAll * 0.1);
        // hitung total keseluruhan yang harus dibayar
        $total_semua = (($totalHargaKamarAll * $cekReservasi['jumlah_malam']) + $totalHargaLayananAll + $pajakLayanan);
        // hitung uang yang kurang atau yg harus dikembalikan
        $kurang_atau_kembali = $total_semua - $cekReservasi->uang_jaminan - $cekReservasi->deposit;
        
        // gunakan carbon untuk ngeformat tanggal
        $tanggal_cetak = Carbon::now();
        $check_in = Carbon::parse($cekReservasi['waktu_check_in']);
        $check_out = Carbon::parse($cekReservasi['waktu_check_out']);
        // tanggal lunas initinya kan sama dengan tgl dia check out. jadi ya ambil aja dr waktu co untuk tgl lunasnya.
        $tanggal_lunas = Carbon::parse($cekReservasi['waktu_check_out']);

        $data = [
            'tanggal_lunas' => $tanggal_lunas->format('d F Y'),
            'no_invoice' => $invoice->no_invoice,
            'fo' => $invoice->pegawais->nama_pegawai,
            'id_booking' => $cekReservasi['id_booking'],
            'nama' => $cekReservasi->customers->nama_customer,
            'alamat' => $cekReservasi->customers->alamat,
            'check_in' => $check_in->format('d F Y H:i:s'),
            'check_out' => $check_out->format('d F Y H:i:s'),
            'dewasa' => $cekReservasi->jumlah_dewasa,
            'anak_anak' => $cekReservasi->jumlah_anak_anak,
            'tanggal_cetak' => $tanggal_cetak->format('d M Y H:i:s'),
            'trxKamarPesananPerJenis' => $trxKamarPesananPerJenis,
            'jumlahMalam' => $cekReservasi['jumlah_malam'],
            'total_harga_kamar' => $totalHargaKamarAll * $cekReservasi['jumlah_malam'],
            'trxLayananPesan' => $trxLayananPesan,
            'total_harga_layanan' => $totalHargaLayananAll,
            'pajak' => $pajakLayanan,
            'total_semua' => $total_semua,
            'jaminan' => $cekReservasi->uang_jaminan,
            'deposit' => $cekReservasi->deposit,
            'uang_kurang' => ($kurang_atau_kembali < 0 ? null : $kurang_atau_kembali),
            'uang_kembali' => ($kurang_atau_kembali < 0 ? $kurang_atau_kembali * -1 : null)
        ]; // Data yang akan dimasukkan ke dalam PDF

        return new PostResource('T', 'Berhasil Ambil Data Check Out..', $data);

    }

    public function CheckOut(Request $request) {
        $userLogin = Auth::user();
        $req = $request->all();

        $validate = Validator::make($req, [
            'id_trx_reservasi' => 'required',
            'tgl_lunas' => 'required',
            'total_harga_kamar' => 'required|numeric',
            'total_harga_layanan' => 'required|numeric',
            'pajak_layanan' => 'required|numeric',
            'total_semua' => 'required|numeric',
            'temp_lebih_kurang' => 'required|in:0,1',
            'total_lebih_kurang' => 'required|numeric',
            'jumlah_bayar' => 'required|numeric'
        ], [
            'required' => ':Attribute wajib diisi.',
            'numeric' => ':Attribute hanya boleh berupa angka',
            'in' => ':Attribute hanya boleh berupa 0 (kurang) atau 1 (lebih)'
        ]);

        if($validate->fails()){
            return response([
                'status' => 'F',
                'message' => $validate->errors()
            ], 400);
        }

        $reservasi = MasterTrxReservasi::with(["trxKamars", "trxKamars.jenisKamars", "trxKamars.kamars", "trxLayanans", "trxLayanans.layanans", "customers", "pic", "fo"])->find($req['id_trx_reservasi']);

        if(is_null($reservasi)){
            return response([
                'status' => 'F',
                'message' => 'Data Transaksi Reservasi tidak ditemukan!'
            ], 404);
        }
        if($reservasi['status'] == 'Batal'){
            return response([
                'status' => 'F',
                'message' => 'Reservasi sudah dibatalkan!'
            ], 403);
        }
        if($reservasi['status'] == 'Out'){
            return response([
                'status' => 'F',
                'message' => 'Reservasi memang sudah check out sebelumnya!'
            ], 403);
        }

        if(($req['jumlah_bayar'] != $req['total_lebih_kurang']) && $req['temp_lebih_kurang'] == 0){
            return response([
                'status' => 'F',
                'message' => 'Jumlah bayar harus sama dengan total kekurangan!'
            ], 403);
        }

        // untuk generate no invoice
        $increment = '001';
        $jenis_customer = $reservasi['customers']->jenis_customer;
        $tglTukIdInvoice = Carbon::parse($req['tgl_lunas'])->format('dmy');
        $noInvoice = MasterTrxReservasi::select('id_booking')->where('id_booking', 'LIKE', $jenis_customer.$tglTukIdInvoice.'-%')->latest()->first();
        if(!is_null($noInvoice)){
            $parts = explode('-', $noInvoice);
            $no = end($parts);
            $increment = (int)$no + 1;
        }
        $angka = intval($increment); // Konversi ke integer
        $pemformatan3digit = sprintf('%03d', $angka);
        $req['no_invoice'] = $jenis_customer.$tglTukIdInvoice.'-'.$pemformatan3digit;

        $req['id_pegawai'] = $userLogin['id'];
        $req['created_by'] = $userLogin['nama_pegawai'];
        
        $checkOut = Invoice::create($req);
        
        if(!$checkOut){
            return response([
                'status' => 'F',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }
        $reservasi->update([
            'status' => 'Out',
            'updated_by' => $userLogin['nama_pegawai']
        ]);
        
        return new PostResource('T', 'Transaksi '.$reservasi['id_booking'].' Sudah Selesai. Berhasil Check Out..', $checkOut);
    }
}