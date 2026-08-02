<?php

namespace App\Livewire\Customers;

use App\Actions\Customers\ComputeDebtAging;
use App\Actions\Customers\RecordCustomerPayment;
use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Customer $customer;

    public bool $showPaymentForm = false;

    public string $amount = '';

    public string $method = 'cash';

    public string $mpesaCode = '';

    public string $notes = '';

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function startPayment(): void
    {
        $this->reset(['amount', 'mpesaCode', 'notes']);
        $this->method = 'cash';
        $this->showPaymentForm = true;
    }

    public function recordPayment(RecordCustomerPayment $action): void
    {
        $data = $this->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,mpesa'],
            'mpesaCode' => [$this->method === 'mpesa' ? 'required' : 'nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $action(
            $this->customer,
            (int) round($data['amount'] * 100),
            $data['method'],
            $data['mpesaCode'] ?: null,
            auth()->user(),
            $data['notes'] ?: null,
        );

        $this->showPaymentForm = false;
        $this->customer->refresh();
    }

    public function render()
    {
        return view('livewire.customers.show', [
            'ledgerEntries' => $this->customer->ledgerEntries()->with(['sale', 'customerPayment'])->orderByDesc('created_at')->orderByDesc('id')->get(),
            'aging' => app(ComputeDebtAging::class)($this->customer),
        ]);
    }
}
