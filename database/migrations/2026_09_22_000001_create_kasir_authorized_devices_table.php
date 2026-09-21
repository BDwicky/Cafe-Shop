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
        Schema::create('kasir_authorized_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('device_token_hash', 64)->unique();
            $table->string('device_type', 32)->default('tablet');
            $table->string('platform', 64)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_revoked')->default(false)->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_active_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kasir_authorized_devices');
    }
};
