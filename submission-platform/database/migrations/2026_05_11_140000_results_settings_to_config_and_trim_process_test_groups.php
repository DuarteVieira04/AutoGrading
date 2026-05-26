<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            if (! Schema::hasColumn('processes', 'config')) {
                $table->json('config')->nullable()->after('weighting');
            }
        });

        $hasVis = Schema::hasColumn('processes', 'results_visibility');
        $hasCrit = Schema::hasColumn('processes', 'results_criteria');

        if ($hasVis || $hasCrit) {
            foreach (DB::table('processes')->get() as $row) {
                $cfg = [
                    'results_visibility' => ($hasVis && $row->results_visibility !== null && $row->results_visibility !== '')
                        ? $row->results_visibility
                        : 'student',
                    'results_criteria' => ($hasCrit && $row->results_criteria !== null && $row->results_criteria !== '')
                        ? $row->results_criteria
                        : 'final_grade',
                ];
                DB::table('processes')->where('id', $row->id)->update([
                    'config' => json_encode($cfg),
                ]);
            }

            Schema::table('processes', function (Blueprint $table) {
                if (Schema::hasColumn('processes', 'results_visibility')) {
                    $table->dropColumn('results_visibility');
                }
                if (Schema::hasColumn('processes', 'results_criteria')) {
                    $table->dropColumn('results_criteria');
                }
            });
        }

        if (Schema::hasTable('process_test_groups') && Schema::hasColumn('process_test_groups', 'sort_order')) {
            Schema::table('process_test_groups', function (Blueprint $table) {
                $table->dropForeign(['process_id']);
                $table->dropIndex(['process_id', 'sort_order']);
            });
        }

        Schema::table('process_test_groups', function (Blueprint $table) {
            if (Schema::hasColumn('process_test_groups', 'slug')) {
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('process_test_groups', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('process_test_groups', 'aggregation_rule')) {
                $table->dropColumn('aggregation_rule');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_test_groups', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->unsignedInteger('sort_order')->default(0)->after('path_pattern');
            $table->string('aggregation_rule')->nullable()->after('weight');
            $table->index(['process_id', 'sort_order']);
        });

        Schema::table('processes', function (Blueprint $table) {
            $table->string('results_visibility')->nullable()->after('execution_environment');
            $table->text('results_criteria')->nullable()->after('results_visibility');
        });

        if (Schema::hasColumn('processes', 'config')) {
            foreach (DB::table('processes')->get() as $row) {
                $cfg = json_decode($row->config ?? '{}', true);
                if (! is_array($cfg)) {
                    $cfg = [];
                }
                DB::table('processes')->where('id', $row->id)->update([
                    'results_visibility' => $cfg['results_visibility'] ?? null,
                    'results_criteria' => $cfg['results_criteria'] ?? null,
                ]);
            }

            Schema::table('processes', function (Blueprint $table) {
                $table->dropColumn('config');
            });
        }
    }
};
