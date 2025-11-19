<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/2024_01_01_000004_create_clearance_documents_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->constrained()->onDelete('cascade');
            $table->string('document_name');
            $table->string('document_path');
            $table->string('document_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index('clearance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_documents');
    }
};