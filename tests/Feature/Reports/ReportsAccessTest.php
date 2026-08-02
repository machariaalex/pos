<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_cannot_access_reports_hub(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('reports.index'))->assertForbidden();
    }

    public function test_manager_can_access_reports_hub(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.index'))->assertOk();
    }

    public function test_manager_can_access_sales_report(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.sales'))->assertOk();
    }

    public function test_manager_cannot_access_profit_report(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.profit'))->assertForbidden();
    }

    public function test_owner_can_access_profit_report(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('reports.profit'))->assertOk();
    }

    public function test_manager_can_access_stock_valuation(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.stock-valuation'))->assertOk();
    }

    public function test_attendant_cannot_access_stock_valuation(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('reports.stock-valuation'))->assertForbidden();
    }

    public function test_manager_can_access_fast_slow_movers(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.fast-slow-movers'))->assertOk();
    }

    public function test_manager_can_access_expiry_report(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.expiry'))->assertOk();
    }
}
