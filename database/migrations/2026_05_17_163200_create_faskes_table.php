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
        Schema::create('faskes', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_faskes');
            $table->enum('tipe', ['rsud', 'puskesmas', 'rs_perujuk']);
            $table->string('kode_faskes')->unique();
            $table->string('alamat')->nullable();
            $table->string('no_telp')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faskes');
    }
};
