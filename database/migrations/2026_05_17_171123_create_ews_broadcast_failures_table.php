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
        Schema::create('ews_broadcast_failures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ews_assessment_id')->constrained('ews_assessments');
            $table->integer('attempt')->default(1);
            $table->text('error_message');
            $table->timestamp('failed_at');
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ews_broadcast_failures');
    }
};
