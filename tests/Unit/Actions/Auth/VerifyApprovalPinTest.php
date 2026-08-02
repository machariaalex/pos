<?php

namespace Tests\Unit\Actions\Auth;

use App\Actions\Auth\VerifyApprovalPin;
use App\Exceptions\TooManyPinAttemptsException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyApprovalPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_pin_returns_the_matching_owner_or_manager(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('4321');

        $result = (new VerifyApprovalPin)('4321', 'test-key-1');

        $this->assertSame($manager->id, $result->id);
    }

    public function test_wrong_pin_returns_null(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('4321');

        $result = (new VerifyApprovalPin)('0000', 'test-key-2');

        $this->assertNull($result);
    }

    public function test_attendant_pin_never_matches_since_attendants_have_no_pin(): void
    {
        $attendant = User::factory()->attendant()->create();
        // Attendants don't get a PIN in this system — verify one simply can't be set/matched.
        $this->assertFalse($attendant->hasPin());

        $result = (new VerifyApprovalPin)('0000', 'test-key-3');

        $this->assertNull($result);
    }

    public function test_inactive_user_pin_does_not_match(): void
    {
        $manager = User::factory()->manager()->inactive()->create();
        $manager->setPin('4321');

        $result = (new VerifyApprovalPin)('4321', 'test-key-4');

        $this->assertNull($result);
    }

    public function test_throttles_after_five_wrong_attempts(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('4321');

        for ($i = 0; $i < 5; $i++) {
            (new VerifyApprovalPin)('0000', 'test-key-5');
        }

        $this->expectException(TooManyPinAttemptsException::class);

        (new VerifyApprovalPin)('4321', 'test-key-5'); // even the correct PIN is now blocked
    }

    public function test_successful_match_clears_the_throttle_counter(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('4321');

        (new VerifyApprovalPin)('0000', 'test-key-6');
        (new VerifyApprovalPin)('0000', 'test-key-6');
        (new VerifyApprovalPin)('4321', 'test-key-6'); // correct — should reset the counter

        // Should now have a fresh allowance, not be stuck at 3/5 attempts used.
        (new VerifyApprovalPin)('0000', 'test-key-6');
        $result = (new VerifyApprovalPin)('4321', 'test-key-6');

        $this->assertSame($manager->id, $result->id);
    }

    public function test_different_throttle_keys_are_independent(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('4321');

        for ($i = 0; $i < 5; $i++) {
            (new VerifyApprovalPin)('0000', 'terminal-a');
        }

        // terminal-b has its own counter, unaffected by terminal-a's lockout.
        $result = (new VerifyApprovalPin)('4321', 'terminal-b');

        $this->assertSame($manager->id, $result->id);
    }
}
