<?php

namespace App\Livewire\Admin\Users;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = User::ROLE_ATTENDANT;

    public string $password = '';

    public string $pin = '';

    /** @var array<int, string> */
    public array $permissions = [];

    public function mount(): void
    {
        Gate::authorize('manage-users');
    }

    public function startCreate(): void
    {
        $this->reset(['editingUserId', 'name', 'email', 'phone', 'password', 'pin', 'permissions']);
        $this->role = User::ROLE_ATTENDANT;
        $this->showForm = true;
    }

    public function startEdit(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->role;
        $this->password = '';
        $this->pin = '';
        $this->permissions = $user->permissions ?? [];
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-users');

        $this->email = Str::lower(trim($this->email));

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in([User::ROLE_OWNER, User::ROLE_MANAGER, User::ROLE_ATTENDANT])],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'min:8'],
            'pin' => ['nullable', 'digits:4'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_keys(User::PERMISSIONS))],
        ]);

        // Owners already have full access by default — permission grants are
        // meaningless (and confusing) for that role, so never persist them.
        $permissions = $data['role'] === User::ROLE_OWNER ? [] : array_values($data['permissions']);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $before = $user->only(['name', 'email', 'phone', 'role']);
            $permissionsBefore = $user->permissions ?? [];
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'role' => $data['role'],
                'permissions' => $permissions,
            ]);
            if ($data['password']) {
                $user->password = $data['password'];
            }
            $user->save();

            if ($this->pin) {
                $user->setPin($this->pin);
            }

            AuditLog::record('user.updated', $user, "Updated user {$user->email}", $before, $user->only(['name', 'email', 'phone', 'role']));

            if ($permissionsBefore !== $permissions) {
                AuditLog::record(
                    'user.permissions_updated',
                    $user,
                    "Updated permissions for {$user->email}",
                    ['permissions' => $permissionsBefore],
                    ['permissions' => $permissions],
                );
            }
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'role' => $data['role'],
                'permissions' => $permissions,
                'password' => $data['password'],
                'is_active' => true,
            ]);

            if ($this->pin) {
                $user->setPin($this->pin);
            }

            AuditLog::record('user.created', $user, "Created user {$user->email} ({$user->role})");
        }

        $this->showForm = false;
        $this->reset(['editingUserId', 'name', 'email', 'phone', 'password', 'pin', 'permissions']);
    }

    public function toggleActive(int $userId): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->addError('form', 'You cannot deactivate your own account.');

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        AuditLog::record(
            $user->is_active ? 'user.activated' : 'user.deactivated',
            $user,
            "{$user->email} was ".($user->is_active ? 'activated' : 'deactivated')
        );
    }

    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
