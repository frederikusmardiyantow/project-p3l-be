<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterTrxReservasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_customer',
        'id_booking',
        'nama_pic',
        'jumlah_dewasa',
        'jumlah_anak_anak',
        'req_layanan',
        'waktu_check_in',
        'waktu_check_out',
        'jumlah_malam',
        'total_harga',
        'waktu_pembayaran',
        'waktu_reservasi',
        'uang_jaminan',
        'status',
        'flag_stat',
        'created_by',
        'updated_by'
    ];

    public function getCreatedAtAttribute(){
        if(!is_null($this->attributes['created_at'])){
            return Carbon::parse($this->attributes['created_at'])->format('Y-m-d H:i:s');
        }
    }
    public function getUpdatedAtAttribute(){
        if(!is_null($this->attributes['updated_at'])){
            return Carbon::parse($this->attributes['updated_at'])->format('Y-m-d H:i:s');
        }
    }
}
