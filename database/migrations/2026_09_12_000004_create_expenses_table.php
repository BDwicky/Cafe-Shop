<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->default('other'); // restock, operational, packaging, maintenance, other
            $table->string('title');
            $table->unsignedInteger('amount'); // rupiah
            $table->date('expense_date');
            $table->string('payment_method')->default('cash'); // cash, transfer, qris
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->timestamps();

            $table->index(['category', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
