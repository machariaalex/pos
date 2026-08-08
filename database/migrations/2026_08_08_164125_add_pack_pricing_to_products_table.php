<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Optional bulk pricing, e.g. a 50kg bag at a discounted total
            // vs. the per-kg selling_price_cents. pack_size is expressed in
            // the product's selling_unit. Only meaningful alongside
            // selling_unit — see Product::hasBulkPack().
            $table->decimal('pack_size', 12, 3)->nullable()->after('units_per_base');
            $table->unsignedInteger('pack_price_cents')->nullable()->after('pack_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pack_size', 'pack_price_cents']);
        });
    }
};
