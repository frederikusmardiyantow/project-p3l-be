<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MasterTrxReservasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

class PDFController extends Controller
{
    public function exportPDF(string $id) {

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

}
