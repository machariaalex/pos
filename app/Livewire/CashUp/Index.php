<?php

namespace App\Livewire\CashUp;

use App\Actions\Cash\DeclareCashUp;
use App\Actions\Reports\ComputeDailySummary;
use App\Models\CashUp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $declaredAmount = '';

    public string $notes = '';

    public function declare(DeclareCashUp $action): void
    {
        $data = $this->validate([
            'declaredAmount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $action(Carbon::today(), auth()->user(), (int) round($data['declaredAmount'] * 100), $data['notes'] ?: null);

        $this->reset(['declaredAmount', 'notes']);
    }

    public function render()
    {
        $today = Carbon::today();
        $canSeeAll = Gate::allows('view-reports');

        $todaysCashUp = CashUp::whereDate('business_date', $today->toDateString())
            ->where('user_id', auth()->id())
            ->first();

        $expectedToday = app(ComputeDailySummary::class)($today, auth()->user())['cash_expected_cents'];

        $history = CashUp::with('user')
            ->when(! $canSeeAll, fn ($q) => $q->where('user_id', auth()->id()))
            ->latest('business_date')
            ->latest('id')
            ->limit(30)
            ->get();

        return view('livewire.cash-up.index', [
            'todaysCashUp' => $todaysCashUp,
            'expectedToday' => $expectedToday,
            'history' => $history,
        ]);
    }
}
