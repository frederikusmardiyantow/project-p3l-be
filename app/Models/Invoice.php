<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'no_invoice',
        'id_trx_reservasi',
        'id_pegawai',
        'tgl_lunas',
        'total_harga_kamar',
        'total_harga_layanan',
        'pajak_layanan',
        'total_semua',
        'created_by'
    ];

    public function trxReservasis() {
        return $this->belongsTo(MasterTrxReservasi::class, 'id_trx_reservasi', 'id');
    }
    public function pegawais() {
        return $this->belongsTo(MasterPegawai::class, 'id_pegawai', 'id');
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
