<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterKamar extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_jenis_kamar',
        'nomor_kamar',
        'jenis_bed',
        'nomor_lantai',
        'smoking_area',
        'catatan',
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
