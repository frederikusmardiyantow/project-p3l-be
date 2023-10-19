<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrxReservasiKamar extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_jenis_kamar',
        'id_kamar',
        'id_trx_reservasi',
        'harga_per_malam',
        'flag_stat',
        'created_by',
        'updated_by'
    ];

    public function jenisKamars() {
        return $this->belongsTo(JenisKamar::class, 'id_jenis_kamar', 'id');
    }
    public function kamars() {
        return $this->belongsTo(MasterKamar::class, 'id_kamar', 'id');
    }
    public function trxReservasis() {
        return $this->belongsTo(MasterTrxReservasi::class, 'id_trx_reservasi', 'id');
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
