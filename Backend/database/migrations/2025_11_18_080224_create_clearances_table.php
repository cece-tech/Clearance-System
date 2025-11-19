<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/2024_01_01_000002_create_clearances_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('section');
            $table->string('course');
            $table->string('request_type');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('student_id')
                  ->references('id')
                  ->on('students')
                  ->onDelete('cascade');

            // Indexes for better query performance
            $table->index(['status', 'request_type']);
            $table->index('created_at');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearances');
    }
};