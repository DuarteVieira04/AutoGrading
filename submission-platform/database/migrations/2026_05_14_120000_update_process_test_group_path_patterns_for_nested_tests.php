<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alinha path_pattern com a nova árvore: tests/tests, tests/tests1, tests/tests2.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'tests1' => 'tests/tests1',
            'tests2' => 'tests/tests2',
            'tests' => 'tests/tests',
        ];

        foreach ($map as $from => $to) {
            DB::table('process_test_groups')
                ->where('path_pattern', $from)
                ->update(['path_pattern' => $to]);
        }
    }

    public function down(): void
    {
        $map = [
            'tests/tests1' => 'tests1',
            'tests/tests2' => 'tests2',
            'tests/tests' => 'tests',
        ];

        foreach ($map as $from => $to) {
            DB::table('process_test_groups')
                ->where('path_pattern', $from)
                ->update(['path_pattern' => $to]);
        }
    }
};
