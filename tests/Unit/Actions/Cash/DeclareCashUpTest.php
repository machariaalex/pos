<?php

namespace Tests\Unit\Actions\Cash;

use App\Actions\Cash\DeclareCashUp;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Unit\Actions\Reports\ReportsActionTestCase;

class DeclareCashUpTest extends ReportsActionTestCase
{
    public function test_declaring_matches_expected_cash_shows_zero_variance(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);
        $today = Carbon::today();

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]], $user, $today,
        );

        $cashUp = app(DeclareCashUp::class)($today, $user, 6500);

        $this->assertSame(6500, $cashUp->expected_cash_cents);
        $this->assertSame(6500, $cashUp->declared_cash_cents);
        $this->assertSame(0, $cashUp->variance_cents);
    }

    public function test_declaring_less_than_expected_shows_negative_variance(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);
        $today = Carbon::today();

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]], $user, $today,
        );

        $cashUp = app(DeclareCashUp::class)($today, $user, 6000); // 500 short

        $this->assertSame(-500, $cashUp->variance_cents);
    }

    public function test_redeclaring_the_same_day_updates_rather_than_duplicates(): void
    {
        $user = User::factory()->attendant()->create();
        $today = Carbon::today();

        app(DeclareCashUp::class)($today, $user, 1000);
        app(DeclareCashUp::class)($today, $user, 2000);

        $this->assertDatabaseCount('cash_ups', 1);
        $this->assertDatabaseHas('cash_ups', ['user_id' => $user->id, 'declared_cash_cents' => 2000]);
    }

    public function test_different_attendants_can_each_declare_the_same_day(): void
    {
        $alice = User::factory()->attendant()->create();
        $bob = User::factory()->attendant()->create();
        $today = Carbon::today();

        app(DeclareCashUp::class)($today, $alice, 1000);
        app(DeclareCashUp::class)($today, $bob, 2000);

        $this->assertDatabaseCount('cash_ups', 2);
    }

    public function test_cash_up_is_audit_logged(): void
    {
        $user = User::factory()->attendant()->create();
        $today = Carbon::today();

        app(DeclareCashUp::class)($today, $user, 1000);

        $this->assertDatabaseHas('audit_logs', ['action' => 'cash_up.declared', 'user_id' => $user->id]);
    }
}
