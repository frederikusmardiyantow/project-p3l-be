<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Lunas Pemesanan</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
    <div class="header">
        <img src="GAH-horizontal.png" alt="logo" class="gambar-logo">
        <p class="alamat">Jl. Babarsari No.43, Janti, Caturtunggal, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55281</p>
    </div>
    <div class="body mt-4">
        <p class="judul mb-4">INVOICE Pemesanan</p>
        <div class="mb-4 d-flex justify-center">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <tr>
                            <td style="width: 30%">Tanggal</td>
                            <td style="width: 2px">:</td>
                            <td>{{ $tanggal_lunas }}</td>
                        </tr>
                        <tr>
                            <td>No. Invoice</td>
                            <td>:</td>
                            <td>{{ $no_invoice }}</td>
                        </tr>
                        <tr>
                            <td>Front Office</td>
                            <td>:</td>
                            <td>{{ $fo }}</td>
                        </tr>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-4">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <td style="width: 30%">ID Booking</td>
                        <td style="width: 2px">:</td>
                        <td>{{ $id_booking }}</td>
                    </tr>
                    <tr>
                        <td>Nama</td>
                        <td>:</td>
                        <td>{{ $nama }}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td>{{ $alamat }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-4">
            <p class="font-weight-bold" style="font-size: 18px">Detail Pemesanan</p>
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <td style="width: 30%">Check In</td>
                        <td style="width: 2px">:</td>
                        <td>{{ $check_in }}</td>
                    </tr>
                    <tr>
                        <td>Check Out</td>
                        <td>:</td>
                        <td>{{ $check_out }}</td>
                    </tr>
                    <tr>
                        <td>Dewasa</td>
                        <td>:</td>
                        <td>{{ $dewasa }}</td>
                    </tr>
                    <tr>
                        <td>Anak-anak</td>
                        <td>:</td>
                        <td>{{ $anak_anak }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-5">
            <p class="font-weight-bold" style="font-size: 18px">KAMAR</p>
            <table class='table table-bordered lh-base'>
                <thead>
                    <tr style="text-align: center">
                        {{-- <th>No.</th> --}}
                        <th>Jenis Kamar</th>
                        <th>Jumlah Kamar</th>
                        <th>Harga</th>
                        <th>Jumlah Malam</th>
                        <th>Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i=1 @endphp
                    @foreach($trxKamarPesananPerJenis as $data)
                    <tr style="text-align: center">
                        {{-- <td>{{ $i++ }}</td> --}}
                        <td>{{ $data->jenis_kamar }}</td>
                        <td>{{ $data->jumlah }}</td>
                        <td style="text-align: right;">{{ "Rp " . number_format($data->harga_per_malam, 0, ',', '.') . ",-" }}</td>
                        <td>{{ $jumlahMalam }}</td>
                        <td style="text-align: right">{{ "Rp " . number_format($data->total_per_jenis * $jumlahMalam, 0, ',', '.') . ",-" }}</td>
                    </tr>
                    @endforeach
                    <tr style="text-align: right">
                        <td colspan="4"></td>
                        <td style="font-weight: bold">{{ "Rp " . number_format($total_harga_kamar, 0, ',', '.') . ",-" }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-4">
            <p class="font-weight-bold" style="font-size: 18px">LAYANAN</p>
            <table class='table table-bordered lh-base'>
                <thead>
                    <tr style="text-align: center">
                        {{-- <th>No.</th> --}}
                        <th>Layanan</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i=1 @endphp
                    @foreach($trxLayananPesan as $data)
                    <tr style="text-align: center">
                        {{-- <td>{{ $i++ }}</td> --}}
                        <td>{{ $data->nama_layanan }}</td>
                        <td style="text-align: right;">{{ $data->tgl_pemakaian }}</td>
                        <td style="text-align: right;">{{ $data->jumlah }}</td>
                        <td style="text-align: right">{{ "Rp " . number_format($data->harga_per_satuan, 0, ',', '.') . ",-" }}</td>
                        <td style="text-align: right">{{ "Rp " . number_format($data->total_per_layanan, 0, ',', '.') . ",-" }}</td>
                    </tr>
                    @endforeach
                    <tr style="text-align: right">
                        <td colspan="4"></td>
                        <td style="font-weight: bold">{{ "Rp " . number_format($total_harga_layanan, 0, ',', '.') . ",-" }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div>
            <table class='table lh-base'>
                <thead>
                    <tr style="text-align: center">
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="text-align: right">
                        <td colspan="3"  style="width: 80%"></td>
                        <td>Pajak</td>
                        <td>{{ "Rp " . number_format($pajak, 0, ',', '.') . ",-" }}</td>
                    </tr>
                    <tr style="text-align: right">
                        <td colspan="3"></td>
                        <td style="font-weight: bold">TOTAL</td>
                        <td style="font-weight: bold">{{ "Rp " . number_format($total_semua, 0, ',', '.') . ",-" }}</td>
                    </tr>
                    <tr style="text-align: right">
                        <td colspan="5"></td>
                    </tr>
                    <tr style="text-align: right">
                        <td colspan="3"></td>
                        <td>Jaminan</td>
                        <td>{{ "Rp " . number_format($jaminan, 0, ',', '.') . ",-" }}</td>
                    </tr>
                    <tr style="text-align: right">
                        <td colspan="3"></td>
                        <td>Deposit</td>
                        <td>{{ "Rp " . number_format($deposit, 0, ',', '.') . ",-" }}</td>
                    </tr>
                    @if ($uang_kurang == null)
                        <tr style="text-align: right">
                            <td colspan="3"></td>
                            <td style="font-weight: bold">Uang Kembali</td>
                            <td style="font-weight: bold">{{ "Rp " . number_format($uang_kembali, 0, ',', '.') . ",-" }}</td>
                        </tr>
                    @else
                    <tr style="text-align: right">
                        <td colspan="3"></td>
                        <td style="font-weight: bold">Kekurangan</td>
                        <td style="font-weight: bold">{{ "Rp " . number_format($uang_kurang, 0, ',', '.') . ",-" }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    
    <p class="mt-5" style="font-style: italic; font-size: 13px">dicetak pada tanggal {{ $tanggal_cetak }}</p>
</body>
</html>