<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->json('ingredients')->nullable()->after('description');
            $table->json('nutrition')->nullable()->after('ingredients');
            $table->string('flavor_notes')->nullable()->after('nutrition');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['ingredients', 'nutrition', 'flavor_notes']);
        });
    }
};
