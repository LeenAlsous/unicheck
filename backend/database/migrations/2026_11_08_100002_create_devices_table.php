<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Students' phones. A student can have several phones (lost or new phone),
        // but each phone belongs to only one student.
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            // The phone's ID from the app (a hash of the phone's built-in ID). Unique: one phone, one student.
            $table->string('device_uid', 100)->unique();
            $table->string('name', 100)->nullable(); // e.g. "iPhone" or "Android phone"
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
