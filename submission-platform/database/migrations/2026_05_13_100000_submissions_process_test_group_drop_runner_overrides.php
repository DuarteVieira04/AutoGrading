<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('process_test_group_id')
                ->nullable()
                ->after('evaluation_process_id')
                ->constrained('process_test_groups')
                ->nullOnDelete();
        });

        Schema::table('process_test_groups', function (Blueprint $table) {
            if (Schema::hasColumn('process_test_groups', 'runner_overrides')) {
                $table->dropColumn('runner_overrides');
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['process_test_group_id']);
            $table->dropColumn('process_test_group_id');
        });

        Schema::table('process_test_groups', function (Blueprint $table) {
            $table->json('runner_overrides')->nullable();
        });
    }
};
