<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('category')->default('other');
            $table->string('unit')->default('gr'); // gr, ml, pcs
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->unsignedInteger('cost_per_unit')->default(0); // modal per unit dalam rupiah
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
