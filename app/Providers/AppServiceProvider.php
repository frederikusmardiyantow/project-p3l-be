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
        Validator::extend('unique_with_flag_stat', function ($attribute, $value, $parameters, $validator) {
            // Parameters should include the table name, field name, and flag_stat field name.
            list($table, $field, $flagStatField) = $parameters;
        
            // Check if a record with the given value and flag_stat = 1 exists.
            $count = \DB::table($table)
                ->where($field, $value)
                ->where($flagStatField, 1)
                ->count();
        
            return $count === 0;
        });
    }

}
