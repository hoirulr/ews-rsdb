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
        Schema::table('ews_logs', function (Blueprint $table): void {
            $table->dropForeign(['ews_assessment_id']);
        });

        Schema::table('ews_logs', function (Blueprint $table): void {
            $table->string('aksi', 50)->change();
        });

        Schema::table('ews_logs', function (Blueprint $table): void {
            $table->foreign('ews_assessment_id')->references('id')->on('ews_assessments')->cascadeOnDelete();
            $table->string('status', 20)->default('sukses')->after('aksi');
            $table->json('payload')->nullable()->after('keterangan');
            $table->string('ip_address', 45)->nullable()->after('payload');
            $table->string('user_agent')->nullable()->after('ip_address');

            $table->index(['ews_assessment_id', 'aksi']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ews_logs', function (Blueprint $table): void {
            $table->dropIndex(['ews_assessment_id', 'aksi']);
            $table->dropIndex(['created_at']);
            $table->dropForeign(['ews_assessment_id']);
            $table->dropColumn(['status', 'payload', 'ip_address', 'user_agent']);
        });

        Schema::table('ews_logs', function (Blueprint $table): void {
            $table->string('aksi')->change();
        });

        Schema::table('ews_logs', function (Blueprint $table): void {
            $table->foreign('ews_assessment_id')->references('id')->on('ews_assessments');
        });
    }
};
