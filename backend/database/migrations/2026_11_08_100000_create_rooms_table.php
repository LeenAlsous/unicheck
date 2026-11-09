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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('building', 100);
            $table->string('name', 50);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            // Building-sized: indoor GPS is not accurate enough for a room-sized radius.
            $table->unsignedSmallInteger('radius_meters')->default(75);
            $table->timestamps();

            $table->unique(['building', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
