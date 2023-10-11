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
        Schema::create('master_kamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_jenis_kamar')->constrained(
                table: 'jenis_kamars', indexName: 'kamar_jenis_id'
            )->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('nomor_kamar');
            $table->string('jenis_bed');
            $table->integer('nomor_lantai');
            $table->boolean('smoking_area');
            $table->text('catatan')->nullable(true);
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
        Schema::dropIfExists('master_kamars');
    }
};
