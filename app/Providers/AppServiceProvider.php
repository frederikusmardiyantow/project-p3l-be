<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        config(['app.locale' =>'id']);
        Carbon::setlocale('id');
        date_default_timezone_set('Asia/Jakarta');

        // untuk buat validasi kustom, yakni nilai harus unik dari salah satu kolom di db dgn syarat flag_stat bernilai 1.
        Validator::extend('unique_with_flag_stat_add', function ($attribute, $value, $parameters, $validator) {
            // Parameters should include the table name, field name, and flag_stat field name.
            list($table, $field, $flagStatField) = $parameters;
        
            // Check if a record with the given value and flag_stat = 1 exists.
            $count = \DB::table($table)
                ->where($field, $value)
                ->where($flagStatField, 1)
                ->count();
        
            return $count === 0;
        });
        Validator::extend('unique_with_flag_stat_update', function ($attribute, $value, $parameters, $validator) {
            // Parameters should include the table name, field name, and flag_stat field name.
            list($table, $field, $flagStatField, $id) = $parameters;
        
            // Check if a record with the given value and flag_stat = 1 exists.
            $count = \DB::table($table)
                ->where($field, $value)
                ->where($flagStatField, 1)
                ->where('id', '!=', $id)
                ->count();
        
            return $count === 0;
        });

        // // untuk validasi di entryDataReservasi(MasterTrxReservasi) bahwa inputan kamar harus berupa array yang berisi objek, di mana setiap objek memiliki properti 'id_jenis_kamar' berupa integer dan 'jumlah' juga berupa integer
        // Validator::extend('array_of_objects', function ($attribute, $value, $parameters, $validator) {
        //     echo('masuk validasi');
        //     if (!is_array($value)) {
        //         echo('masuk validasi false bukan array');
        //         return false;
        //     }
            
        //     foreach ($value as $item) {
        //         echo(is_object($item) ? 'T' : 'F');
        //         if (!is_object($item) || !property_exists($item, 'id_jenis_kamar') || !property_exists($item, 'jumlah') || !is_int($item->id_jenis_kamar) || !is_int($item->jumlah)) {
        //             return false;
        //         }
        //     }
        //     return true;
        // });
    }

}
