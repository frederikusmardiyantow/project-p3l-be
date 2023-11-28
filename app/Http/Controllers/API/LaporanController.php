<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
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
        $total = 0;

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
            $total += $row->jumlah_customer;

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
            'data' => [
                'total_customer' => $total,
                'laporan' => array_values($hasilLaporan),
            ] 
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
        $mergedResults = [];
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $dataReservasi = MasterTrxReservasi::select(
            DB::raw('MONTH(master_trx_reservasis.waktu_check_out) as bulan'),
            'master_customers.jenis_customer as type',
            DB::raw('SUM(master_trx_reservasis.uang_jaminan) as dp')
        )
        ->join('master_customers', 'master_customers.id', '=', 'master_trx_reservasis.id_customer')
        ->whereYear('master_trx_reservasis.waktu_pembayaran', $tahun)
        ->groupBy('bulan', 'type')
        ->get();

        $resultReservasi = [];
        // Iterate through months and types
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            foreach (['G', 'P'] as $type) {
                // Check if there is a matching record in the existing result set
                $matchingRecord = $dataReservasi->first(function ($record) use ($bulan, $type) {
                    return $record->bulan == $bulan && $record->type == $type;
                });

                // Set DP value based on the presence of a matching record
                $dp = $matchingRecord ? $matchingRecord->dp : 0;

                // Add the result to the final array
                $resultReservasi[] = [
                    'bulan' => $bulan,
                    'type' => $type,
                    'dp' => $dp,
                ];
            }
        }
        foreach ($resultReservasi as $reservasi) {
            $no = $reservasi['bulan'];
            $month = $monthNames[$reservasi['bulan']];
        
            if (!isset($mergedResults[$no])) {
                $mergedResults[$no] = [
                    'no' => $no,
                    'bulan' => $month,
                    'P' => 0,
                    'G' => 0,
                    'total' => 0,
                ];
            }
        
            $mergedResults[$no][$reservasi['type']] += $reservasi['dp'];
        }

        $dataInvoice = Invoice::select(
            DB::raw('MONTH(invoices.tgl_lunas) as bulan'),
            DB::raw('LEFT(invoices.no_invoice,1) as type'),
            DB::raw('SUM((invoices.total_harga_kamar - (master_trx_reservasis.uang_jaminan)) + invoices.total_harga_layanan) as total')
        )
        ->join('master_trx_reservasis', 'master_trx_reservasis.id', '=', 'invoices.id_trx_reservasi')
        ->whereYear('invoices.tgl_lunas', $tahun)
        ->groupBy('bulan', 'type')
        ->get();

        $resultInvoice = [];
        // Iterate through months and types
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            foreach (['G', 'P'] as $type) {
                // Check if there is a matching record in the existing result set
                $matchingRecordIn = $dataInvoice->first(function ($record) use ($bulan, $type) {
                    return $record->bulan == $bulan && $record->type == $type;
                });

                // Set total value based on the presence of a matching record
                $total = $matchingRecordIn ? $matchingRecordIn->total : 0;

                // Add the result to the final array
                $resultInvoice[] = [
                    'bulan' => $bulan,
                    'type' => $type,
                    'total' => $total,
                ];
            }
        }
        foreach ($resultInvoice as $invoice) {
            $month = $invoice['bulan'];
        
            if (!isset($mergedResults[$month])) {
                $mergedResults[$month] = [
                    'bulan' => $month,
                    'P' => 0,
                    'G' => 0,
                    'total' => 0,
                ];
            }
        
            $mergedResults[$month][$invoice['type']] += $invoice['total'];
        }

        // Calculate the total for each month
        foreach ($mergedResults as &$result) {
            $result['total'] = $result['P'] + $result['G'];
        }

        // Convert the associative array to indexed array
        $mergedResults = array_values($mergedResults);

        return response([
            'status' => 'T',
            'data' => $mergedResults,
        ], 200);
    }
}
