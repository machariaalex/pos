<?php

namespace App\Livewire\Sales;

use App\Actions\Auth\VerifyApprovalPin;
use App\Actions\Sales\VoidSale;
use App\Exceptions\TooManyPinAttemptsException;
use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Receipt extends Component
{
    public Sale $sale;

    public bool $showVoidModal = false;

    public string $voidReason = '';

    public string $voidPin = '';

    public function mount(Sale $sale): void
    {
        $this->sale = $sale->load(['lines.product', 'payments', 'customer', 'user', 'returns.lines']);
    }

    /**
     * Any logged-in user (including an attendant) can initiate a void —
     * the owner PIN entered in confirmVoid() is the actual authorization
     * boundary, per "void requires owner PIN at the terminal."
     */
    public function startVoid(): void
    {
        $this->voidReason = '';
        $this->voidPin = '';
        $this->showVoidModal = true;
    }

    public function confirmVoid(VoidSale $action): void
    {
        $this->validate([
            'voidReason' => ['required', 'string', 'min:3', 'max:500'],
            'voidPin' => ['required', 'digits:4'],
        ]);

        try {
            $approver = app(VerifyApprovalPin::class)($this->voidPin);
        } catch (TooManyPinAttemptsException $e) {
            $this->addError('voidPin', $e->getMessage());

            return;
        }

        // Voiding is stricter than a return/refund: the spec calls for the
        // owner's PIN specifically here, not any manager's.
        if (! $approver || ! $approver->isOwner()) {
            $this->addError('voidPin', 'That PIN did not match the owner.');

            return;
        }

        $action($this->sale, $this->voidReason, $approver, auth()->user());

        $this->showVoidModal = false;
        $this->sale->refresh();
    }

    public function render()
    {
        return view('livewire.sales.receipt');
    }
}
