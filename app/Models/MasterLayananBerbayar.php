<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterLayananBerbayar extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nama_layanan',
        'harga',
        'satuan',
        'keterangan',
        'flag_stat',
        'created_by',
        'updated_by'
    ];

    public function trxLayanans() {
        return $this->hasMany(TrxLayananBerbayar::class, 'id_layanan', 'id');
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
