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
        Schema::create('ews_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('faskes_id')->constrained('faskes');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('waktu_penilaian');
            $table->unsignedTinyInteger('respirasi');
            $table->unsignedTinyInteger('saturasi_o2');
            $table->boolean('oksigen_tambahan');
            $table->decimal('suhu', 4, 1);
            $table->unsignedSmallInteger('td_sistolik');
            $table->unsignedSmallInteger('nadi');
            $table->enum('kesadaran', ['A', 'V', 'P', 'U']);
            $table->tinyInteger('skor_respirasi')->default(0);
            $table->tinyInteger('skor_saturasi')->default(0);
            $table->tinyInteger('skor_oksigen')->default(0);
            $table->tinyInteger('skor_suhu')->default(0);
            $table->tinyInteger('skor_td')->default(0);
            $table->tinyInteger('skor_nadi')->default(0);
            $table->tinyInteger('skor_kesadaran')->default(0);
            $table->tinyInteger('total_skor')->default(0);
            $table->enum('zona', ['hijau', 'kuning', 'merah'])->default('hijau');
            $table->text('catatan_rujukan')->nullable();
            $table->text('tindakan_yang_diberikan')->nullable();
            $table->enum('status', ['menunggu', 'ditangani', 'selesai'])->default('menunggu');
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users');
            $table->dateTime('waktu_ditangani')->nullable();
            $table->boolean('sudah_broadcast')->default(false);
            $table->boolean('alert_aktif')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['alert_aktif', 'zona']);
            $table->index(['faskes_id', 'waktu_penilaian']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ews_assessments');
    }
};
