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
        Schema::create('trx_reservasi_kamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_jenis_kamar')->constrained(
                table: 'jenis_kamars', indexName: 'tarif_jenisK_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_kamar')->nullable()->constrained(
                table: 'master_kamars', indexName: 'trx_kamar_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_trx_reservasi')->constrained(
                table: 'master_trx_reservasis', indexName: 'resv_kamar_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('harga_per_malam')->nullable(true);
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
        Schema::dropIfExists('trx_reservasi_kamars');
    }
};
