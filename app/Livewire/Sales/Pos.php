<?php

namespace App\Livewire\Sales;

use App\Actions\Auth\VerifyApprovalPin;
use App\Actions\Sales\CompleteSale;
use App\Actions\Sales\HoldSale;
use App\Actions\Sales\ResumeSale;
use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\TooManyPinAttemptsException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Pos extends Component
{
    public string $search = '';

    /** @var array<int, array{product_id:int,name:string,base_unit:string,selling_unit:string,units_per_base:string,quantity:string,unit_price_cents:int,discount_cents:int,allows_fractional:bool,is_pack:bool}> */
    public array $cart = [];

    public int $nextCartLineId = 1;

    public ?int $customerId = null;

    public string $customerSearch = '';

    /** @var array<int, array{method:string,amount:string,mpesa_code:string}> */
    public array $payments = [];

    public string $notes = '';

    public bool $showHeldSales = false;

    public bool $showCreditOverrideModal = false;

    public string $overridePin = '';

    public ?array $creditLimitWarning = null;

    public function addFromSearch(): void
    {
        $term = trim($this->search);

        if ($term === '') {
            return;
        }

        $exactBarcode = Product::where('barcode', $term)->where('is_active', true)->first();

        if ($exactBarcode) {
            $this->addProduct($exactBarcode->id);

            return;
        }

        $matches = $this->searchProducts($term);

        if ($matches->count() === 1) {
            $this->addProduct($matches->first()->id);
        }
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        foreach ($this->cart as $lineId => $line) {
            if ($line['product_id'] === $productId && ! $line['is_pack']) {
                $increment = $product->allowsFractionalQuantity() ? '1' : '1';
                $this->cart[$lineId]['quantity'] = bcadd($line['quantity'], $increment, 3);
                $this->search = '';

                return;
            }
        }

        $this->cart[$this->nextCartLineId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'base_unit' => $product->base_unit,
            'selling_unit' => $product->effectiveSellingUnit(),
            'units_per_base' => $product->effectiveUnitsPerBase(),
            'quantity' => '1',
            'unit_price_cents' => $product->selling_price_cents,
            'discount_cents' => 0,
            'allows_fractional' => $product->allowsFractionalQuantity(),
            'is_pack' => false,
        ];
        $this->nextCartLineId++;
        $this->search = '';
    }

    /** Adds one whole bulk pack (e.g. a 50kg bag) at its discounted rate — a
     * separate cart line from any loose/retail line for the same product,
     * since the two have different per-unit prices (see Product::hasBulkPack()). */
    public function addProductPack(int $productId): void
    {
        $product = Product::findOrFail($productId);

        if (! $product->hasBulkPack()) {
            return;
        }

        foreach ($this->cart as $lineId => $line) {
            if ($line['product_id'] === $productId && $line['is_pack']) {
                $this->cart[$lineId]['quantity'] = bcadd($line['quantity'], (string) $product->pack_size, 3);
                $this->search = '';

                return;
            }
        }

        $this->cart[$this->nextCartLineId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'base_unit' => $product->base_unit,
            'selling_unit' => $product->effectiveSellingUnit(),
            'units_per_base' => $product->effectiveUnitsPerBase(),
            'quantity' => (string) $product->pack_size,
            'unit_price_cents' => $product->packUnitPriceCents(),
            'discount_cents' => 0,
            'allows_fractional' => $product->allowsFractionalQuantity(),
            'is_pack' => true,
        ];
        $this->nextCartLineId++;
        $this->search = '';
    }

    public function removeLine(int $lineId): void
    {
        unset($this->cart[$lineId]);
    }

    public function updateLineDiscount(int $lineId, string $value): void
    {
        if (! Gate::allows('apply-discount')) {
            return;
        }

        $this->cart[$lineId]['discount_cents'] = max(0, (int) round(((float) $value) * 100));
    }

    public function selectCustomer(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->customerSearch = '';
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;

        foreach ($this->payments as $i => $payment) {
            if ($payment['method'] === Payment::METHOD_CREDIT) {
                unset($this->payments[$i]);
            }
        }
    }

    public function addPayment(string $method): void
    {
        $this->payments[] = [
            'method' => $method,
            'amount' => number_format(max(0, $this->remainingCents()) / 100, 2, '.', ''),
            'mpesa_code' => '',
        ];
    }

    public function removePayment(int $index): void
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
    }

    public function holdSale(HoldSale $action): void
    {
        if (empty($this->cart)) {
            return;
        }

        $action($this->buildLines(), $this->selectedCustomer(), auth()->user(), $this->notes ?: null);

        $this->resetCart();
    }

    public function resumeSale(int $saleId, ResumeSale $action): void
    {
        $sale = Sale::findOrFail($saleId);
        $resumed = $action($sale);

        $this->cart = [];
        foreach ($resumed['lines'] as $lineData) {
            $product = Product::find($lineData['product_id']);
            $this->cart[$this->nextCartLineId] = [
                'product_id' => $lineData['product_id'],
                'name' => $product?->name ?? 'Unknown product',
                'base_unit' => $product?->base_unit ?? '',
                'quantity' => $lineData['quantity'],
                'unit_price_cents' => $lineData['unit_price_cents'],
                'discount_cents' => $lineData['discount_cents'],
                'allows_fractional' => $product?->allowsFractionalQuantity() ?? true,
                'is_pack' => false,
            ];
            $this->nextCartLineId++;
        }
        $this->customerId = $resumed['customer_id'];
        $this->payments = [];
        $this->showHeldSales = false;
    }

    public function completeSale(CompleteSale $action, bool $withOverride = false, ?User $overrideApprover = null): void
    {
        if (empty($this->cart)) {
            $this->addError('cart', 'The cart is empty.');

            return;
        }

        $customer = $this->selectedCustomer();

        foreach ($this->payments as $payment) {
            if ($payment['method'] === Payment::METHOD_CREDIT && ! $customer) {
                $this->addError('payments', 'Select a customer before taking a credit payment.');

                return;
            }
        }

        try {
            $sale = $action(
                $this->buildLines(),
                $customer,
                $this->buildPayments(),
                auth()->user(),
                $this->notes ?: null,
                overrideCreditLimit: $withOverride,
                overrideApprover: $overrideApprover,
            );

            $this->resetCart();
            $this->redirect(route('sales.receipt', $sale), navigate: false);
        } catch (CreditLimitExceededException $e) {
            $this->creditLimitWarning = [
                'customer' => $e->customer->name,
                'message' => $e->getMessage(),
            ];
            $this->showCreditOverrideModal = true;
        } catch (InsufficientStockException|InvalidArgumentException $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function confirmCreditOverride(CompleteSale $action): void
    {
        try {
            $approver = app(VerifyApprovalPin::class)($this->overridePin);
        } catch (TooManyPinAttemptsException $e) {
            $this->addError('overridePin', $e->getMessage());

            return;
        }

        if (! $approver || ! $approver->isOwner()) {
            $this->addError('overridePin', 'That PIN is not valid for an owner override.');

            return;
        }

        $this->showCreditOverrideModal = false;
        $this->overridePin = '';
        $this->completeSale($action, withOverride: true, overrideApprover: $approver);
    }

    private function buildLines(): array
    {
        return array_values(array_map(fn ($line) => [
            'product_id' => $line['product_id'],
            'quantity' => $line['quantity'],
            'unit_price_cents' => $line['unit_price_cents'],
            'discount_cents' => $line['discount_cents'],
            'units_per_base' => $line['units_per_base'] ?? '1',
        ], $this->cart));
    }

    private function buildPayments(): array
    {
        return array_values(array_map(fn ($payment) => [
            'method' => $payment['method'],
            'amount_cents' => (int) round(((float) $payment['amount']) * 100),
            'mpesa_code' => $payment['mpesa_code'] ?: null,
        ], $this->payments));
    }

    private function selectedCustomer(): ?Customer
    {
        return $this->customerId ? Customer::find($this->customerId) : null;
    }

    private function resetCart(): void
    {
        $this->cart = [];
        $this->payments = [];
        $this->customerId = null;
        $this->notes = '';
        $this->search = '';
    }

    private function searchProducts(string $term)
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('barcode', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    public function lineTotalCents(array $line): int
    {
        $gross = (int) round((float) bcmul($line['quantity'], (string) $line['unit_price_cents'], 6));

        return $gross - $line['discount_cents'];
    }

    public function subtotalCents(): int
    {
        return array_sum(array_map(
            fn ($line) => (int) round((float) bcmul($line['quantity'], (string) $line['unit_price_cents'], 6)),
            $this->cart
        ));
    }

    public function discountCents(): int
    {
        return array_sum(array_column($this->cart, 'discount_cents'));
    }

    public function totalCents(): int
    {
        return $this->subtotalCents() - $this->discountCents();
    }

    public function paidCents(): int
    {
        return array_sum(array_map(fn ($p) => (int) round(((float) ($p['amount'] ?: 0)) * 100), $this->payments));
    }

    public function remainingCents(): int
    {
        return $this->totalCents() - $this->paidCents();
    }

    public function render()
    {
        return view('livewire.sales.pos', [
            'searchResults' => $this->search !== '' ? $this->searchProducts(trim($this->search)) : collect(),
            'customerResults' => $this->customerSearch !== ''
                ? Customer::where('name', 'like', "%{$this->customerSearch}%")->orWhere('phone', 'like', "%{$this->customerSearch}%")->limit(10)->get()
                : collect(),
            'selectedCustomer' => $this->selectedCustomer(),
            'heldSales' => Sale::where('status', Sale::STATUS_HELD)->with('customer')->latest('held_at')->get(),
            'canDiscount' => Gate::allows('apply-discount'),
            'canSeeBuyingPrice' => Gate::allows('view-buying-price'),
        ]);
    }
}
