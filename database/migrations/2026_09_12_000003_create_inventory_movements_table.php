<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // purchase, sale, void_return, waste, adjustment
            $table->decimal('quantity', 10, 2); // nilai mutasi (+ masuk, - keluar)
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->nullableMorphs('reference'); // reference_type & reference_id (Order, Expense, etc.)
            $table->unsignedInteger('cost_per_unit')->nullable(); // rupiah
            $table->unsignedInteger('total_cost')->nullable(); // rupiah
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['ingredient_id', 'type']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
