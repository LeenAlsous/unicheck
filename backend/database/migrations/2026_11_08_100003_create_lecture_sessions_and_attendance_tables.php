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
        Schema::create('lecture_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->timestamp('opened_at')->nullable();
            // When check-in closes automatically; the instructor can close it earlier.
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            // Key that signs the rotating QR codes. Stored encrypted, never sent to students.
            $table->text('qr_secret');
            $table->unsignedSmallInteger('qr_step_seconds')->default(10);
            $table->timestamps();

            $table->index(['section_id', 'status']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20);
            $table->string('source', 20);
            // Server time of the check-in or manual change.
            $table->timestamp('recorded_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // GPS accuracy reported by the phone.
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->json('flags')->nullable();
            // Instructor note for manual changes, e.g. an excuse reason.
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['lecture_session_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('lecture_sessions');
    }
};
