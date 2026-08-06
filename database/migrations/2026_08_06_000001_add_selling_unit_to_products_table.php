<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // When selling_unit is null it means "same as base_unit, no conversion".
            // units_per_base tells you how many selling units fit in one base unit.
            // E.g. base_unit=pcs (bag), selling_unit=kg, units_per_base=50.
            $table->string('selling_unit')->nullable()->after('base_unit');
            $table->decimal('units_per_base', 12, 3)->default(1)->after('selling_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['selling_unit', 'units_per_base']);
        });
    }
};
