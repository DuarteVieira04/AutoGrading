<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->foreignId('grading_process_id')
                ->nullable()
                ->after('student_id')
                ->constrained('grading_processes')
                ->nullOnDelete();
            $table->text('grading_log')->nullable()->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropForeign(['grading_process_id']);
            $table->dropColumn(['grading_process_id', 'grading_log']);
        });
    }
};
