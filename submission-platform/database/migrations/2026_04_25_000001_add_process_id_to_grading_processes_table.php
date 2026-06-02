<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('grading_processes', 'process_id')) {
            return;
        }

        Schema::table('grading_processes', function (Blueprint $table) {
            $table->unsignedBigInteger('process_id')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('grading_processes', 'process_id')) {
            Schema::table('grading_processes', function (Blueprint $table) {
                $table->dropColumn('process_id');
            });
        }
    }
};
