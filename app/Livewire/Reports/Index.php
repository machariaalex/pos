<?php

namespace App\Livewire\Reports;

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('view-reports');
    }

    public function render()
    {
        return view('livewire.reports.index');
    }
}
