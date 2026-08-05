<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor RM adalah penomoran internal masing-masing faskes, sehingga
     * keunikannya harus per faskes asal — bukan global. Unique global lama
     * membuat dua pasien berbeda dari faskes berbeda tergabung jadi satu.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->dropUnique(['no_rm']);
            $table->unique(['faskes_asal_id', 'no_rm']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->dropUnique(['faskes_asal_id', 'no_rm']);
            $table->unique('no_rm');
        });
    }
};
