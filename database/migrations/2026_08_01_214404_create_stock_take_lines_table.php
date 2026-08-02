<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_take_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_take_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_lines');
    }
};
