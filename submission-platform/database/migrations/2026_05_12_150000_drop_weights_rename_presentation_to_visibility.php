<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_test_groups', function (Blueprint $table) {
            if (Schema::hasColumn('process_test_groups', 'weight')) {
                $table->dropColumn('weight');
            }
        });

        Schema::table('process_test_groups', function (Blueprint $table) {
            if (Schema::hasColumn('process_test_groups', 'presentation')) {
                $table->dropColumn('presentation');
            }
        });

        Schema::table('process_test_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('process_test_groups', 'visibility')) {
                $table->json('visibility')->nullable()->after('path_pattern');
            }
        });

        Schema::table('processes', function (Blueprint $table) {
            if (Schema::hasColumn('processes', 'weighting')) {
                $table->dropColumn('weighting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->decimal('weighting', 5, 2)->nullable()->after('execution_environment');
        });

        Schema::table('process_test_groups', function (Blueprint $table) {
            if (Schema::hasColumn('process_test_groups', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });

        Schema::table('process_test_groups', function (Blueprint $table) {
            $table->decimal('weight', 10, 4)->nullable()->after('path_pattern');
            $table->json('presentation')->nullable();
        });
    }
};
