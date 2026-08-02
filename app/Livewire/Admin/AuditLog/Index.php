<?php

namespace App\Livewire\Admin\AuditLog;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $action = '';

    public ?int $userId = null;

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        Gate::authorize('view-audit-log');
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($this->action, fn ($q) => $q->where('action', 'like', "%{$this->action}%"))
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest('created_at')
            ->paginate(30);

        return view('livewire.admin.audit-log.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
