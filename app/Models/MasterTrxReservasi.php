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
        'id_pic',
        'id_fo',
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
        'deposit',
        'status',
        'bukti_pembayaran',
        'flag_stat',
        'created_by',
        'updated_by'
    ];

    public function trxKamars() {
        return $this->hasMany(TrxReservasiKamar::class, 'id_trx_reservasi', 'id');
    }
    public function invoices() {
        return $this->hasMany(Invoice::class, 'id_trx_reservasi', 'id');
    }
    public function customers() {
        return $this->belongsTo(MasterCustomer::class, 'id_customer', 'id');
    }
    public function trxLayanans() {
        return $this->hasMany(TrxLayananBerbayar::class, 'id_trx_reservasi', 'id');
    }
    public function pic() {
        return $this->belongsTo(MasterPegawai::class, 'id_pic', 'id');
    }
    public function fo() {
        return $this->belongsTo(MasterPegawai::class, 'id_fo', 'id');
    }

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
