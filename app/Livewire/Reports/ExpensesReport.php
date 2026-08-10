<?php

namespace App\Livewire\Reports;

use App\Actions\Reports\ComputeExpensesForRange;
use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ExpensesReport extends Component
{
    public string $dateFrom;

    public string $dateTo;

    public string $groupBy = 'none';

    public ?string $activePreset = null;

    public function mount(): void
    {
        Gate::authorize('manage-expenses');

        $this->setToday();
    }

    public function setToday(): void
    {
        $this->dateFrom = Carbon::today()->toDateString();
        $this->dateTo = Carbon::today()->toDateString();
        $this->activePreset = 'today';
    }

    public function setThisWeek(): void
    {
        $this->dateFrom = Carbon::today()->startOfWeek()->toDateString();
        $this->dateTo = Carbon::today()->toDateString();
        $this->activePreset = 'week';
    }

    public function setThisMonth(): void
    {
        $this->dateFrom = Carbon::today()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::today()->toDateString();
        $this->activePreset = 'month';
    }

    public function updatedDateFrom(): void
    {
        $this->activePreset = null;
    }

    public function updatedDateTo(): void
    {
        $this->activePreset = null;
    }

    private function baseExpensesQuery()
    {
        return Expense::whereDate('incurred_on', '>=', $this->dateFrom)
            ->whereDate('incurred_on', '<=', $this->dateTo);
    }

    public function render()
    {
        $totalCents = (new ComputeExpensesForRange)(Carbon::parse($this->dateFrom), Carbon::parse($this->dateTo));

        return view('livewire.reports.expenses-report', [
            'totalCents' => $totalCents,
            'byCategory' => $this->groupBy === 'category' ? $this->byCategory() : collect(),
            'entries' => $this->groupBy === 'none'
                ? $this->baseExpensesQuery()->with(['category', 'createdBy'])->latest('incurred_on')->latest('id')->get()
                : collect(),
        ]);
    }

    private function byCategory()
    {
        return Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereDate('expenses.incurred_on', '>=', $this->dateFrom)
            ->whereDate('expenses.incurred_on', '<=', $this->dateTo)
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc(DB::raw('sum(expenses.amount_cents)'))
            ->get([
                'expense_categories.name',
                DB::raw('count(expenses.id) as expense_count'),
                DB::raw('sum(expenses.amount_cents) as total_cents'),
            ]);
    }
}
