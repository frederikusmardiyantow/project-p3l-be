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
        Schema::create('master_customers', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_customer', 2);
            $table->string('nama_customer', 150);
            $table->string('nama_institusi', 150)->nullable(true);
            $table->string('no_identitas', 50);
            $table->string('jenis_identitas', 50);
            $table->string('no_telp', 15);
            $table->string('email')->unique();
            $table->text('password')->nullable(true);
            $table->text('alamat');
            $table->boolean('flag_stat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_customers');
    }
};
