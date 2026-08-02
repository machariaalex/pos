<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AuditLog\Index as AuditLogIndex;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_audit_log(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('audit-log.index'))->assertOk();
    }

    public function test_manager_cannot_access_audit_log(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('audit-log.index'))->assertForbidden();
    }

    public function test_attendant_cannot_access_audit_log(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('audit-log.index'))->assertForbidden();
    }

    public function test_filtering_by_action_narrows_results(): void
    {
        $owner = User::factory()->owner()->create();
        AuditLog::record('sale.completed', description: 'Test sale');
        AuditLog::record('sale.voided', description: 'Test void');

        Livewire::actingAs($owner)
            ->test(AuditLogIndex::class)
            ->set('action', 'sale.voided')
            ->assertSee('Test void')
            ->assertDontSee('Test sale');
    }

    public function test_filtering_by_user_narrows_results(): void
    {
        $owner = User::factory()->owner()->create();
        $manager = User::factory()->manager()->create();
        AuditLog::record('product.updated', description: 'By owner', actor: $owner);
        AuditLog::record('product.updated', description: 'By manager', actor: $manager);

        Livewire::actingAs($owner)
            ->test(AuditLogIndex::class)
            ->set('userId', $manager->id)
            ->assertSee('By manager')
            ->assertDontSee('By owner');
    }
}
