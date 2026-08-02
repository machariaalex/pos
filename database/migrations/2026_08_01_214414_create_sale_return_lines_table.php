<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_line_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_returned', 12, 3);
            $table->unsignedBigInteger('refund_amount_cents');
            $table->foreignId('restocked_batch_id')->constrained('batches')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_lines');
    }
};
