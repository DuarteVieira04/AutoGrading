<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            if (! Schema::hasColumn('processes', 'project_zip_path')) {
                $table->string('project_zip_path', 1000)->nullable();
            }
            if (! Schema::hasColumn('processes', 'project_base_path')) {
                $table->string('project_base_path', 1000)->nullable();
            }
            if (! Schema::hasColumn('processes', 'project_working_path')) {
                $table->string('project_working_path', 1000)->nullable();
            }
            if (! Schema::hasColumn('processes', 'project_status')) {
                $table->string('project_status', 32)->default('pending');
            }
            if (! Schema::hasColumn('processes', 'project_error')) {
                $table->text('project_error')->nullable();
            }
            if (! Schema::hasColumn('processes', 'project_prepared_at')) {
                $table->timestamp('project_prepared_at')->nullable();
            }
            if (! Schema::hasColumn('processes', 'project_log')) {
                $table->longText('project_log')->nullable();
            }
        });

        Schema::table('submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('submissions', 'work_dir_path')) {
                $table->string('work_dir_path', 1000)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            foreach ([
                'project_zip_path',
                'project_base_path',
                'project_working_path',
                'project_status',
                'project_error',
                'project_prepared_at',
                'project_log',
            ] as $col) {
                if (Schema::hasColumn('processes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'work_dir_path')) {
                $table->dropColumn('work_dir_path');
            }
        });
    }
};
