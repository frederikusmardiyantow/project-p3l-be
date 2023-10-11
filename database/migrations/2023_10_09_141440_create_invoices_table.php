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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_trx_reservasi')->constrained(
                table: 'master_trx_reservasis', indexName: 'resv_inv_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_pegawai')->constrained(
                table: 'master_pegawais', indexName: 'inv_pgw_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('tgl_lunas');
            $table->integer('total_harga_kamar');
            $table->integer('total_harga_layanan');
            $table->integer('pajak_layanan');
            $table->integer('total_semua');
            $table->string('created_by', 150)->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
