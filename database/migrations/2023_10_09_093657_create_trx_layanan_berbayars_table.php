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
        Schema::create('trx_layanan_berbayars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_layanan')->constrained(
                table: 'master_layanan_berbayars', indexName: 'trx_layanan_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_trx_reservasi')->constrained(
                table: 'master_trx_reservasis', indexName: 'trx_lay_resv_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('jumlah');
            $table->integer('total_harga');
            $table->dateTime('waktu_pemakaian')->nullable(true);
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
        Schema::dropIfExists('trx_layanan_berbayars');
    }
};
