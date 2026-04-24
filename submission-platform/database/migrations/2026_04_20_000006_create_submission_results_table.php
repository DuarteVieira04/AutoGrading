<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submissions_id')->constrained('submissions')->onDelete('cascade');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->text('report_sent')->nullable();
            $table->boolean('notified_student')->default(false);
            $table->boolean('notified_teacher')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_results');
    }
};
