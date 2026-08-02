<?php

namespace App\Livewire\Sales;

use App\Actions\Auth\VerifyApprovalPin;
use App\Actions\Sales\ProcessReturn;
use App\Exceptions\TooManyPinAttemptsException;
use App\Models\Sale;
use App\Models\SaleReturnLine;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Returns extends Component
{
    public Sale $sale;

    /** @var array<int, string> Keyed by sale_line id. */
    public array $quantities = [];

    public string $reason = '';

    public string $pin = '';

    public function mount(Sale $sale): void
    {
        $this->sale = $sale->load(['lines.product']);

        foreach ($this->sale->lines as $line) {
            $this->quantities[$line->id] = '';
        }
    }

    public function alreadyReturned(int $saleLineId): string
    {
        return (string) SaleReturnLine::where('sale_line_id', $saleLineId)->sum('quantity_returned');
    }

    public function availableToReturn(int $saleLineId, string $originalQuantity): string
    {
        return bcsub($originalQuantity, $this->alreadyReturned($saleLineId), 3);
    }

    public function submit(ProcessReturn $action): void
    {
        $this->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'pin' => ['required', 'digits:4'],
        ]);

        $returnLines = [];
        foreach ($this->quantities as $lineId => $quantity) {
            if ($quantity !== '' && bccomp($quantity, '0', 3) > 0) {
                $returnLines[] = ['sale_line_id' => $lineId, 'quantity_returned' => $quantity];
            }
        }

        if (empty($returnLines)) {
            $this->addError('quantities', 'Enter a quantity to return for at least one item.');

            return;
        }

        try {
            $approver = app(VerifyApprovalPin::class)($this->pin);
        } catch (TooManyPinAttemptsException $e) {
            $this->addError('pin', $e->getMessage());

            return;
        }

        if (! $approver) {
            $this->addError('pin', 'That PIN did not match any owner or manager.');

            return;
        }

        try {
            $action($this->sale, $returnLines, $this->reason, $approver, auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->addError('quantities', $e->getMessage());

            return;
        }

        $this->redirect(route('sales.receipt', $this->sale), navigate: false);
    }

    public function render()
    {
        return view('livewire.sales.returns');
    }
}
