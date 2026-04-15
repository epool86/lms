<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedTinyInteger('progress_percentage')->default(0)->after('status');
            $table->timestamp('last_accessed_at')->nullable()->after('enrolled_at');
            $table->timestamp('completed_at')->nullable()->after('last_accessed_at');
        });

        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'expired', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['progress_percentage', 'last_accessed_at', 'completed_at']);
        });
    }
};
