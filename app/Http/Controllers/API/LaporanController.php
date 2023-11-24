<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MasterCustomer;
use App\Models\MasterTrxReservasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function laporanCustBaru(string $tahun){
        // Mendapatkan seluruh bulan dalam setahun
        $seluruhBulan = range(1, 12);

        // Menggunakan Query Builder Laravel untuk mengambil data
        $data = MasterCustomer::select(
                DB::raw('MONTH(created_at) as bulan'),
                DB::raw('YEAR(created_at) as tahun'),
                DB::raw('COUNT(*) as jumlah_customer')
            )
            ->whereYear('created_at', $tahun)
            ->groupBy(DB::raw('MONTH(created_at)'), DB::raw('YEAR(created_at)'))
            ->get();

        // Mengonversi hasil query ke dalam bentuk array asosiatif dengan nama bulan dalam bahasa Indonesia
        $hasilLaporan = [];
        foreach ($data as $row) {
            $carbonDate = Carbon::createFromDate($row->tahun, $row->bulan, 1);
            $namaBulan = $carbonDate->translatedFormat('F'); // Menggunakan translatedFormat untuk mendapatkan nama bulan dalam bahasa Indonesia

            $hasilLaporan[$row->bulan] = [
                'no' => $row->bulan,
                'bulan' => $namaBulan,
                'tahun' => $row->tahun,
                'jumlah_customer' => $row->jumlah_customer,
            ];
        }

        // Mengisi bulan-bulan yang tidak memiliki customer dengan jumlah 0
        foreach ($seluruhBulan as $bulan) {
            if (!isset($hasilLaporan[$bulan])) {
                $carbonDate = Carbon::createFromDate(date('Y'), $bulan, 1);
                $namaBulan = $carbonDate->translatedFormat('F');

                $hasilLaporan[$bulan] = [
                    'no' => $bulan,
                    'bulan' => $namaBulan,
                    'tahun' => date('Y'),
                    'jumlah_customer' => 0,
                ];
            }
        }

        // Mengurutkan hasil laporan berdasarkan bulan
        ksort($hasilLaporan);

        return response([
            'status' => 'T',
            'data' => array_values($hasilLaporan),
        ], 200);
    }

    public function laporan5CustTerbanyak(string $tahun){
        $top5Customers = MasterCustomer::select(
                'master_customers.id as id_customer',
                'master_customers.nama_customer',
                DB::raw('COUNT(master_trx_reservasis.id) as jumlah_reservasi'),
                DB::raw('SUM(master_trx_reservasis.total_harga) as total_harga')
            )
            ->join('master_trx_reservasis', 'master_customers.id', '=', 'master_trx_reservasis.id_customer')
            ->whereYear('master_trx_reservasis.waktu_reservasi', $tahun)
            ->groupBy('master_customers.id', 'master_customers.nama_customer')
            ->orderByDesc('jumlah_reservasi')
            ->limit(5)
            ->get();

        return response([
            'status' => 'T',
            'data' => $top5Customers,
        ], 200);
    }

    public function laporanPendapatanPerJenisTamuPerBulan(string $tahun)
    {
        $data = MasterTrxReservasi::select(
            DB::raw('MONTH(master_trx_reservasis.waktu_check_out) as bulan'),
            'master_customers.jenis_customer',
            DB::raw('SUM(master_trx_reservasis.total_harga) as total_pendapatan')
        )
        ->join('master_customers', 'master_customers.id', '=', 'master_trx_reservasis.id_customer')
        // ->join('trx_layanan_berbayars', function ($join) {
        //     $join->on('trx_layanan_berbayars.id_trx_reservasi', '=', 'master_trx_reservasis.id');
        // })
        ->where('master_trx_reservasis.status', 'Out') // Menambahkan nama tabel pada status
        ->whereYear('master_trx_reservasis.waktu_check_out', $tahun)
        ->groupBy('bulan', 'master_customers.jenis_customer')
        ->get();

        return response([
            'status' => 'T',
            'data' => $data,
        ], 200);
    }
}
