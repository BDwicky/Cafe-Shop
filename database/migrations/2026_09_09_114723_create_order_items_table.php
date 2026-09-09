<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained()->nullOnDelete(); // snapshot aman saat menu dihapus
            $table->string('menu_name');
            $table->unsignedInteger('price'); // snapshot harga saat transaksi
            $table->unsignedInteger('qty');
            $table->unsignedInteger('line_total');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
