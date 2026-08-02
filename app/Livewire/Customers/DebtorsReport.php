<?php

namespace App\Livewire\Customers;

use App\Actions\Customers\ComputeDebtAging;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DebtorsReport extends Component
{
    public function mount(): void
    {
        Gate::authorize('view-reports');
    }

    public function render()
    {
        $agingAction = app(ComputeDebtAging::class);

        $debtors = Customer::where('balance_cents', '>', 0)
            ->orderByDesc('balance_cents')
            ->get()
            ->map(fn (Customer $customer) => [
                'customer' => $customer,
                'aging' => $agingAction($customer),
            ]);

        $totals = [
            'current' => $debtors->sum(fn ($d) => $d['aging']['current']),
            'days_30' => $debtors->sum(fn ($d) => $d['aging']['days_30']),
            'days_60' => $debtors->sum(fn ($d) => $d['aging']['days_60']),
            'days_90_plus' => $debtors->sum(fn ($d) => $d['aging']['days_90_plus']),
        ];

        return view('livewire.customers.debtors-report', [
            'debtors' => $debtors,
            'totals' => $totals,
            'grandTotal' => array_sum($totals),
        ]);
    }
}
