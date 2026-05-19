<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_results', function (Blueprint $table) {
            if (! Schema::hasColumn('submission_results', 'success_rate_percent')) {
                $table->decimal('success_rate_percent', 5, 2)->nullable()->after('final_grade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('submission_results', function (Blueprint $table) {
            if (Schema::hasColumn('submission_results', 'success_rate_percent')) {
                $table->dropColumn('success_rate_percent');
            }
        });
    }
};
