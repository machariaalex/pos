<?php

namespace App\Livewire\Reports;

use App\Actions\Reports\ComputeProfitForRange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProfitReport extends Component
{
    public string $dateFrom;

    public string $dateTo;

    public function mount(): void
    {
        Gate::authorize('view-profit');

        $this->dateFrom = Carbon::today()->subDays(29)->toDateString();
        $this->dateTo = Carbon::today()->toDateString();
    }

    public function render()
    {
        $result = app(ComputeProfitForRange::class)(Carbon::parse($this->dateFrom), Carbon::parse($this->dateTo));

        return view('livewire.reports.profit-report', $result);
    }
}
