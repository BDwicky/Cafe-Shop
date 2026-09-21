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
        Schema::create('music_banned_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('youtube_id', 64)->nullable()->index();
            $table->string('title')->nullable()->index();
            $table->string('artist')->nullable();
            $table->string('reason')->nullable();
            $table->string('banned_by', 64)->default('kasir');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('music_banned_tracks');
    }
};
