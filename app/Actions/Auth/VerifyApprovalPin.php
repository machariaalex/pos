<?php

namespace App\Actions\Auth;

use App\Exceptions\TooManyPinAttemptsException;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

class VerifyApprovalPin
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300;

    /**
     * Find which active owner/manager a terminal-entered PIN belongs to.
     * The approving user doesn't have to be the one currently logged in —
     * this is how a manager approves a void without the attendant logging out.
     *
     * A 4-digit PIN only has 10,000 combinations, so attempts are throttled
     * per terminal (IP) the same way login attempts are.
     */
    public function __invoke(string $pin, ?string $throttleKey = null): ?User
    {
        $key = 'pin-attempt:'.($throttleKey ?? request()?->ip() ?? 'cli');

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw new TooManyPinAttemptsException(RateLimiter::availableIn($key));
        }

        $match = User::query()
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_MANAGER])
            ->where('is_active', true)
            ->whereNotNull('pin_hash')
            ->get()
            ->first(fn (User $user) => $user->verifyPin($pin));

        if ($match) {
            RateLimiter::clear($key);
        } else {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        }

        return $match;
    }
}
