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
        Schema::table('ews_assessments', function (Blueprint $table): void {
            $table->enum('feedback_hasil', [
                'meninggal',
                'icu_lebih_24_jam',
                'rawat_inap_lebih_24_jam',
            ])->nullable()->after('waktu_ditangani');
            $table->text('feedback_catatan')->nullable()->after('feedback_hasil');
            $table->foreignId('feedback_oleh')->nullable()->after('feedback_catatan')->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_feedback')->nullable()->after('feedback_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ews_assessments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('feedback_oleh');
            $table->dropColumn(['feedback_hasil', 'feedback_catatan', 'waktu_feedback']);
        });
    }
};
