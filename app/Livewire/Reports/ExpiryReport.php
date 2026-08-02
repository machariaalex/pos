<?php

namespace App\Livewire\Reports;

use App\Models\Batch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ExpiryReport extends Component
{
    public string $filter = 'all'; // all | expired | expiring_soon

    public function mount(): void
    {
        Gate::authorize('view-reports');
    }

    public function render()
    {
        $batches = Batch::with('product')
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->when($this->filter === 'expired', fn ($q) => $q->where('expiry_date', '<', Carbon::today()))
            ->when($this->filter === 'expiring_soon', fn ($q) => $q->whereBetween('expiry_date', [Carbon::today(), Carbon::today()->addDays(60)]))
            ->orderBy('expiry_date')
            ->get();

        return view('livewire.reports.expiry-report', ['batches' => $batches]);
    }
}
