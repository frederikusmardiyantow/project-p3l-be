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
        Schema::create('jenis_kamars', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_kamar', 150);
            $table->integer('ukuran_kamar');
            $table->text('fasilitas_kamar');
            $table->text('deskripsi');
            $table->integer('kapasitas');
            $table->integer('harga_dasar');
            $table->string('gambar', 255);
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
        Schema::dropIfExists('jenis_kamars');
    }
};
