<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_test_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('path_pattern');
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('aggregation_rule')->nullable();
            $table->json('runner_overrides')->nullable();
            $table->json('presentation')->nullable();
            $table->timestamps();

            $table->index(['process_id', 'sort_order']);
        });

        Schema::table('processes', function (Blueprint $table) {
            if (Schema::hasColumn('processes', 'config')) {
                $table->dropColumn('config');
            }
        });
    }

    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->json('config')->nullable()->after('weighting');
        });

        Schema::dropIfExists('process_test_groups');
    }
};
