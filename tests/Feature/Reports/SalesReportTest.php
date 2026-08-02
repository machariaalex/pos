<?php

namespace Tests\Feature\Reports;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Reports\SalesReport;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_grouping_by_product_shows_correct_totals(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::create(['name' => 'Feeds']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Dairy Meal', 'base_unit' => 'kg',
            'buying_price_cents' => 5000, 'selling_price_cents' => 6500, 'reorder_level' => 10,
        ]);
        Batch::create([
            'product_id' => $product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 100, 'quantity_remaining' => 100,
            'buying_price_cents' => 5000, 'received_at' => Carbon::now(), 'created_by' => $manager->id,
        ]);

        app(CompleteSale::class)(
            [['product_id' => $product->id, 'quantity' => '3', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 19500]], $manager,
        );

        Livewire::actingAs($manager)
            ->test(SalesReport::class)
            ->set('groupBy', 'product')
            ->assertSee('Dairy Meal')
            ->assertSee('195.00');
    }

    public function test_totals_reflect_the_selected_date_range(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::create(['name' => 'Feeds']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Dairy Meal', 'base_unit' => 'kg',
            'buying_price_cents' => 5000, 'selling_price_cents' => 6500, 'reorder_level' => 10,
        ]);
        Batch::create([
            'product_id' => $product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 100, 'quantity_remaining' => 100,
            'buying_price_cents' => 5000, 'received_at' => Carbon::now(), 'created_by' => $manager->id,
        ]);

        $sale = app(CompleteSale::class)(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]], $manager,
        );
        $sale->update(['completed_at' => Carbon::today()->subDays(40)]);

        Livewire::actingAs($manager)
            ->test(SalesReport::class)
            ->assertSet('dateFrom', Carbon::today()->subDays(29)->toDateString())
            ->assertSee('Total sales');

        // The sale is outside the default 30-day window, so totals should be zero.
        $component = Livewire::actingAs($manager)->test(SalesReport::class);
        $html = $component->html();
        $this->assertStringContainsString('0.00', $html);
    }
}
