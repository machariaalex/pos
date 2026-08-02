<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_ups', function (Blueprint $table) {
            $table->id();
            $table->date('business_date');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('expected_cash_cents');
            $table->unsignedBigInteger('declared_cash_cents');
            $table->bigInteger('variance_cents');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['business_date', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_ups');
    }
};
