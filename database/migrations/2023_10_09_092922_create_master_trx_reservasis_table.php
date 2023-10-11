<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_trx_reservasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_customer')->constrained(
                table: 'master_customers', indexName: 'cust_resv_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->string('id_booking', 150)->nullable(true);
            $table->string('nama_pic', 150)->nullable(true);
            $table->integer('jumlah_dewasa');
            $table->integer('jumlah_anak_anak');
            $table->text('req_layanan')->nullable(true);
            $table->dateTime('waktu_check_in');
            $table->dateTime('waktu_check_out');
            $table->integer('jumlah_malam');
            $table->integer('total_harga');
            $table->dateTime('waktu_pembayaran')->nullable(true);
            $table->dateTime('waktu_reservasi');
            $table->integer('uang_jaminan')->nullable(true);
            $table->string('status', 150);
            $table->boolean('flag_stat');
            $table->string('created_by', 150)->nullable(true);
            $table->string('updated_by', 150)->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_trx_reservasis');
    }
};
