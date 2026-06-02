<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_name')->nullable();
            $table->foreignId('process_type_id')->constrained('process_types')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('open_date')->nullable();
            $table->timestamp('close_date')->nullable();
            $table->string('execution_environment')->nullable();
            $table->string('results_visibility')->nullable();
            $table->text('results_criteria')->nullable();
            $table->decimal('weighting', 5, 2)->nullable();
            $table->bigInteger('max_file_size_byte')->nullable();
            $table->boolean('email_notification')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};
