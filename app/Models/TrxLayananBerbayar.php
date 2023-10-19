<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrxLayananBerbayar extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id_layanan',
        'id_trx_reservasi',
        'jumlah',
        'total_harga',
        'waktu_pemakaian',
        'flag_stat',
        'created_by',
        'updated_by'
    ];

    public function trxReservasis() {
        return $this->belongsTo(MasterTrxReservasi::class, 'id_trx_reservasi', 'id');
    }
    public function layanans() {
        return $this->belongsTo(MasterLayananBerbayar::class, 'id_layanan', 'id');
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
