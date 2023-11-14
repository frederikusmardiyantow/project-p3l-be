<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Tanda Terima Pemesanan</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
    <div class="header">
        <img src="GAH-horizontal.png" alt="logo" class="gambar-logo">
        <p class="alamat">Jl. Babarsari No.43, Janti, Caturtunggal, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55281</p>
    </div>
    <div class="body mt-4">
        <p class="judul mb-4">Tanda Terima Pemesanan</p>
        <div class="mb-4">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <td style="text-align: left">ID Booking : {{ $id_booking }}</td>
                        <td style="text-align: right">Tanggal    : {{ $tanggal_reservasi }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-4">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <td style="width: 30%">Nama</td>
                        <td style="width: 2px">:</td>
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
                    <tr>
                        <td>Tanggal Pembayaran</td>
                        <td>:</td>
                        <td>{{ $tanggal_pembayaran }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-4">
            <p class="font-weight-bold" style="font-size: 18px">Rincian</p>
            <table class='table table-bordered lh-base'>
                <thead>
                    <tr style="text-align: center">
                        {{-- <th>No.</th> --}}
                        <th>Jenis Kamar</th>
                        <th>Jumlah Kamar</th>
                        <th>Harga</th>
                        <th>Jumlah Malam</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i=1 @endphp
                    @foreach($jumlahKamarPerJenis as $data)
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
                        <td style="font-weight: bold">{{ "Rp " . number_format($total_harga, 0, ',', '.') . ",-" }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-4">
            <p class="font-weight-bold" style="font-size: 18px">Permintaan Khusus</p>
            @if ($req_layanan == null)
                <p class="ml-4 text-muted font-italic">- Tidak ada data permintaan khusus -</p>
            @else
                <p class="ml-4">{{ $req_layanan }}</p>
            @endif
        </div>

    </div>
    
    <p class="mt-5" style="font-style: italic; font-size: 13px">dicetak pada tanggal {{ $tanggal_cetak }}</p>
</body>
</html>