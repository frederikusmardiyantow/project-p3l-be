<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MasterTrxReservasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

class PDFController extends Controller
{
    public function exportPDFTandaTerima(string $id) {

        $cekReservasi = MasterTrxReservasi::with(["trxKamars", "trxKamars.jenisKamars", "trxKamars.kamars" ,"customers", "pic", "fo"])->find($id);
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
        // hitung jumlah masing-masing jenis kamar yang dipesan dan total harga
        $jumlahKamarPerJenis = [];
        $totalHarga = 0;
        foreach ($cekReservasi->trxKamars as $trx) {
            $jenisKamar = $trx->jenisKamars->jenis_kamar;
            $hargaPerMalam = $trx->harga_per_malam;
            // $totalPerJenis = $hargaPerMalam;
            $totalHarga += $trx->harga_per_malam;
        
            if (array_key_exists($jenisKamar, $jumlahKamarPerJenis)) {
                $jumlahKamarPerJenis[$jenisKamar]->jumlah += 1;
                $jumlahKamarPerJenis[$jenisKamar]->total_per_jenis += $hargaPerMalam;
            } else {
                $jumlahKamarPerJenis[$jenisKamar] = (object) [
                    'jenis_kamar' => $jenisKamar,
                    'jumlah' => 1,
                    'harga_per_malam' => $hargaPerMalam,
                    'total_per_jenis' => $hargaPerMalam,
                ];
            }
        }        
        // return response([
        //     'data' => $jumlahKamarPerJenis
        // ]);
        
        // gunakan carbon untuk ngeformat tanggal
        $tanggal_cetak = Carbon::now();
        $waktu_reservasi = Carbon::parse($cekReservasi['waktu_reservasi']);
        $check_in = Carbon::parse($cekReservasi['waktu_check_in']);
        $check_out = Carbon::parse($cekReservasi['waktu_check_out']);
        $tanggal_pembayaran = Carbon::parse($cekReservasi['waktu_pembayaran']);

        $data = [
            'id_booking' => $cekReservasi['id_booking'],
            'tanggal_reservasi' => $waktu_reservasi->format('d F Y H:i:s'),
            'nama' => $cekReservasi->customers->nama_customer,
            'alamat' => $cekReservasi->customers->alamat,
            'check_in' => $check_in->format('d F Y H:i:s'),
            'check_out' => $check_out->format('d F Y H:i:s'),
            'dewasa' => $cekReservasi->jumlah_dewasa,
            'anak_anak' => $cekReservasi->jumlah_anak_anak,
            'tanggal_cetak' => $tanggal_cetak->format('d M Y H:i:s'),
            'tanggal_pembayaran' => $tanggal_pembayaran->format('d F Y H:i:s'),
            'total_harga' => $totalHarga * $cekReservasi['jumlah_malam'],
            'jumlahKamarPerJenis' => $jumlahKamarPerJenis,
            'jumlahMalam' => $cekReservasi['jumlah_malam'],
            'req_layanan' => $cekReservasi->req_layanan
        ]; // Data yang akan dimasukkan ke dalam PDF

        $pdf = PDF::loadView('tanda-terima', $data);

        // Tambahkan gambar ke dalam PDF
        $pdf->getDomPDF()->getOptions()->set('isPhpEnabled', true);
        $pdf->getDomPDF()->getOptions()->set('isHtml5ParserEnabled', true);

        $pdf->getDomPDF()->loadHtml(view('tanda-terima', $data)->render());

        // Render PDF
        $pdf->getDomPDF()->render();

        return $pdf->stream('tanda-terima-pemesanan.pdf');

        // $data = [];

        // $pdf = PDF::loadView('tanda-terima', $data);
        // return $pdf->download('tanda-terima-pemesanan.pdf');
    }
    public function exportPDFNotaLunas(string $id) {

        $cekReservasi = MasterTrxReservasi::with(["trxKamars", "trxKamars.jenisKamars", "trxKamars.kamars", "trxLayanans", "trxLayanans.layanans", "customers", "pic", "fo"])->find($id);
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
        // Catatan: invoice ini cuma buat ambil data no invoice dan id pegawainya. untuk total harga kamar dan layanan, pajak, dan total semua dihitung dalam kode ini (tidak diambil dari tabel invoice. Untuk mencocokan kebenaran data aja si, jd dihitung ulang)
        $invoice = Invoice::with(["trxReservasis", "pegawais"])->where('id_trx_reservasi', $id)->first();
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

        // return response([
        //     'data' => $data,
        //     'total_kamar' => $totalHargaKamarAll,
        //     'total_layanan' => $totalHargaLayananAll,
        //     'total_pajak' => $pajakLayanan
        // ]);

        $pdf = PDF::loadView('nota-lunas', $data);

        // Tambahkan gambar ke dalam PDF
        $pdf->getDomPDF()->getOptions()->set('isPhpEnabled', true);
        $pdf->getDomPDF()->getOptions()->set('isHtml5ParserEnabled', true);

        $pdf->getDomPDF()->loadHtml(view('nota-lunas', $data)->render());

        // Render PDF
        $pdf->getDomPDF()->render();

        return $pdf->stream('nota-lunas-pemesanan.pdf');

        // $data = [];

        // $pdf = PDF::loadView('tanda-terima', $data);
        // return $pdf->download('tanda-terima-pemesanan.pdf');
    }

    }
}