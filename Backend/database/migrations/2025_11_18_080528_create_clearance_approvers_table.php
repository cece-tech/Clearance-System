<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->constrained()->onDelete('cascade');
            $table->string('approver_role');
            $table->string('approver_name');
            $table->integer('order')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['clearance_id', 'order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_approvers');
    }
};
