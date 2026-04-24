<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_result_id')->constrained('submission_results')->onDelete('cascade');
            $table->string('test_name');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->string('execution_logs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_executions');
    }
};
