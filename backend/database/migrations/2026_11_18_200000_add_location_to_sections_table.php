<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Each section can have its own location, set by its instructor: the building, the room
    // number and the GPS point students must be near to check in. It doesn't change the room
    // for other sections. A lecture no longer needs a room when its section has a location.
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('building', 100)->nullable();
            $table->string('room_number', 50)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });

        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['building', 'room_number', 'latitude', 'longitude']);
        });
    }
};
