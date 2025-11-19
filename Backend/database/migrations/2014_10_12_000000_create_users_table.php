<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('last_name');
        $table->string('first_name');
        $table->string('middle_initial')->nullable();
        $table->string('student_id', 7)->nullable()->unique();
        $table->string('email')->unique();
        $table->string('password');
        $table->enum('role', ['admin', 'staff', 'student'])->default('staff');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
