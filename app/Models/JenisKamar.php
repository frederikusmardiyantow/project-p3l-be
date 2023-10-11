<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisKamar extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'jenis_kamar',
        'ukuran_kamar',
        'fasilitas_kamar',
        'deskripsi',
        'kapasitas',
        'harga_dasar',
        'gambar',
        'flag_stat',
        'created_by',
        'updated_by'
    ];

    // Accessor untuk atribute Image
    protected function gambar(): Attribute
    {
        return Attribute::make(
            get: fn($gambar) => asset('/storage/posts/' . $gambar),
        );
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
