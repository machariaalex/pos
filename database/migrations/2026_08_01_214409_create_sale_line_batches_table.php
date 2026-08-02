<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_line_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->unsignedBigInteger('unit_cost_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_line_batches');
    }
};
