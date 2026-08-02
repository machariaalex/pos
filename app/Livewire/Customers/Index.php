<?php

namespace App\Livewire\Customers;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingCustomerId = null;

    public string $name = '';

    public string $phone = '';

    public string $creditLimit = '';

    public bool $hasCreditLimit = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->reset(['editingCustomerId', 'name', 'phone', 'creditLimit', 'hasCreditLimit']);
        $this->showForm = true;
    }

    public function startEdit(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->editingCustomerId = $customer->id;
        $this->name = $customer->name;
        $this->phone = (string) $customer->phone;
        $this->hasCreditLimit = $customer->hasCreditLimit();
        $this->creditLimit = $customer->hasCreditLimit()
            ? number_format($customer->credit_limit_cents / 100, 2, '.', '')
            : '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^(?:\+?254|0)[17]\d{8}$/'],
            'creditLimit' => [$this->hasCreditLimit ? 'required' : 'nullable', 'numeric', 'min:0'],
        ]);

        $creditLimitCents = null;
        if ($this->hasCreditLimit && Gate::allows('manage-credit-limit')) {
            $creditLimitCents = (int) round($data['creditLimit'] * 100);
        }

        if ($this->editingCustomerId) {
            $customer = Customer::findOrFail($this->editingCustomerId);
            $before = $customer->only(['name', 'phone', 'credit_limit_cents']);

            $attributes = ['name' => $data['name'], 'phone' => $data['phone']];
            if (Gate::allows('manage-credit-limit')) {
                $attributes['credit_limit_cents'] = $creditLimitCents;
            }

            $customer->update($attributes);

            AuditLog::record('customer.updated', $customer, "Updated customer {$customer->name}", $before, $customer->only(['name', 'phone', 'credit_limit_cents']));
        } else {
            $customer = Customer::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'credit_limit_cents' => Gate::allows('manage-credit-limit') ? $creditLimitCents : null,
                'balance_cents' => 0,
            ]);

            AuditLog::record('customer.created', $customer, "Created customer {$customer->name}");
        }

        $this->showForm = false;
    }

    public function render()
    {
        $customers = Customer::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.customers.index', [
            'customers' => $customers,
            'canManageCreditLimit' => Gate::allows('manage-credit-limit'),
        ]);
    }
}
