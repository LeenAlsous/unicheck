<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A student's excuse for missing the lectures of a section on one day,
    // e.g. a medical report or an exam at the same time. The instructor approves or rejects it.
    public function up(): void
    {
        Schema::create('excuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->date('date'); // the day of the missed lecture
            $table->string('reason', 20); // "medical", "exam" or "other"
            $table->string('note', 500)->nullable();
            $table->string('photo_path'); // the photo of the document, in storage/app/private/excuses
            $table->string('status', 20)->default('pending'); // "pending", "approved" or "rejected"
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excuses');
    }
};
