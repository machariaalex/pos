<?php

namespace App\Exceptions;

use App\Models\Customer;
use RuntimeException;

class CreditLimitExceededException extends RuntimeException
{
    public function __construct(public Customer $customer, public int $wouldBeBalanceCents)
    {
        parent::__construct(
            "Charging {$customer->name} would take their balance to KES ".number_format($wouldBeBalanceCents / 100, 2)
            .', over their limit of KES '.number_format($customer->credit_limit_cents / 100, 2).'.'
        );
    }
}
