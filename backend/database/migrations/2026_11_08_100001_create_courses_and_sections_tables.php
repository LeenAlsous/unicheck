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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            // Absences allowed before the student and instructor are alerted.
            $table->unsignedTinyInteger('max_absences')->default(6);
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('number', 10);
            $table->string('semester', 20);
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            // Room used for new sessions unless the instructor picks another.
            $table->foreignId('default_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'semester', 'number']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['section_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('courses');
    }
};
