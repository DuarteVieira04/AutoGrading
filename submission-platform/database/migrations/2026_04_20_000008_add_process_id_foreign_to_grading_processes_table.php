<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grading_processes', 'process_id')) {
            Schema::table('grading_processes', function (Blueprint $table) {
                $table->foreignId('process_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('processes')
                    ->nullOnDelete();
            });

            return;
        }

        Schema::table('grading_processes', function (Blueprint $table) {
            $table->foreign('process_id')
                ->references('id')
                ->on('processes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('grading_processes', 'process_id')) {
            return;
        }

        Schema::table('grading_processes', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
            $table->dropColumn('process_id');
        });
    }
};
