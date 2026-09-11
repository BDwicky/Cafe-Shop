<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('prep_status', 20)->default('pending')->after('status');
            $table->timestamp('ready_at')->nullable()->after('prep_status');
            $table->timestamp('announced_at')->nullable()->after('ready_at');
            $table->timestamp('completed_at')->nullable()->after('announced_at');

            $table->index('prep_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['prep_status']);
            $table->dropColumn(['prep_status', 'ready_at', 'announced_at', 'completed_at']);
        });
    }
};
