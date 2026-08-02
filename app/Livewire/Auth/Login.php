<?php

namespace App\Livewire\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        // Trim before validating: the "email" format rule can reject
        // leading/trailing whitespace that autofill or copy-paste often adds.
        $this->email = trim($this->email);

        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Normalize the same way the User model stores emails (lowercase)
        // so a typed case difference doesn't silently fail the lookup.
        $email = Str::lower($this->email);

        $throttleKey = $email.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many login attempts. Try again in {$seconds} seconds.");

            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Auth::attempt(['email' => $email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            AuditLog::record('login.failed', description: "Failed login attempt for {$email}");
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        if (! $user->is_active) {
            Auth::logout();
            $this->addError('email', 'This account has been deactivated. Contact the owner.');

            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();
        AuditLog::record('login.success', $user, description: "{$user->name} logged in");

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
