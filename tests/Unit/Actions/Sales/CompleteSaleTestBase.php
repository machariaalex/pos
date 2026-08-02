<?php

namespace Tests\Unit\Actions\Sales;

use App\Actions\Sales\AllocateFefoBatches;
use App\Actions\Sales\CompleteSale;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;

abstract class CompleteSaleTestBase extends SalesActionTestCase
{
    protected function complete(array $lines, ?Customer $customer, array $payments, User $cashier): Sale
    {
        return (new CompleteSale(new AllocateFefoBatches))($lines, $customer, $payments, $cashier);
    }
}
