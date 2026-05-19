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
        Schema::create('ews_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ews_assessment_id')->constrained('ews_assessments');
            $table->foreignId('user_id')->constrained('users');
            $table->string('aksi');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ews_logs');
    }
};
