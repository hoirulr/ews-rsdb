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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('faskes_id')->nullable()->after('password')->constrained('faskes')->nullOnDelete();
            $table->string('no_hp')->nullable()->after('faskes_id');
            $table->boolean('is_active')->default(true)->after('no_hp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('faskes_id');
            $table->dropColumn(['no_hp', 'is_active']);
        });
    }
};
