<?php

namespace App\Livewire\Inventory\ReceiveStock;

use App\Actions\Inventory\ReceiveBatch;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $search = '';

    public ?int $productId = null;

    public string $batchNumber = '';

    public string $expiryDate = '';

    public string $quantityReceived = '';

    public string $buyingPrice = '';

    public string $receivedAt = '';

    public ?string $lastReceivedMessage = null;

    public ?int $supplierId = null;

    public string $supplierSearch = '';

    public bool $showSupplierForm = false;

    public string $newSupplierName = '';

    public string $newSupplierPhone = '';

    public function mount(): void
    {
        Gate::authorize('adjust-stock');

        $productId = request()->integer('product');

        if ($productId && Product::where('id', $productId)->exists()) {
            $this->selectProduct($productId);
        }
    }

    public function selectProduct(int $productId): void
    {
        Gate::authorize('adjust-stock');

        $product = Product::findOrFail($productId);

        $this->productId = $product->id;
        $this->search = '';
        $this->batchNumber = '';
        $this->expiryDate = '';
        $this->quantityReceived = '';
        $this->buyingPrice = number_format($product->buying_price_cents / 100, 2, '.', '');
        $this->receivedAt = now()->toDateString();
        $this->lastReceivedMessage = null;
    }

    public function clearProduct(): void
    {
        $this->reset(['productId', 'batchNumber', 'expiryDate', 'quantityReceived', 'buyingPrice', 'receivedAt',
            'supplierId', 'supplierSearch', 'showSupplierForm', 'newSupplierName', 'newSupplierPhone']);
    }

    public function selectSupplier(int $supplierId): void
    {
        $this->supplierId = $supplierId;
        $this->supplierSearch = '';
    }

    public function clearSupplier(): void
    {
        $this->supplierId = null;
    }

    public function addSupplier(): void
    {
        Gate::authorize('adjust-stock');

        $data = $this->validate([
            'newSupplierName' => ['required', 'string', 'max:255'],
            'newSupplierPhone' => ['nullable', 'string', 'max:20'],
        ]);

        $supplier = Supplier::create([
            'name' => $data['newSupplierName'],
            'phone' => $this->newSupplierPhone ?: null,
        ]);

        $this->supplierId = $supplier->id;
        $this->newSupplierName = '';
        $this->newSupplierPhone = '';
        $this->showSupplierForm = false;
    }

    public function receive(ReceiveBatch $action): void
    {
        Gate::authorize('adjust-stock');

        if (! $this->productId) {
            return;
        }

        $product = Product::findOrFail($this->productId);
        $canSeeBuyingPrice = Gate::allows('view-buying-price');

        $data = $this->validate([
            'batchNumber' => ['nullable', 'string', 'max:100'],
            'expiryDate' => ['nullable', 'date'],
            'quantityReceived' => ['required', 'numeric', 'gt:0'],
            'buyingPrice' => [$canSeeBuyingPrice ? 'required' : 'nullable', 'numeric', 'min:0'],
            'receivedAt' => ['required', 'date'],
        ]);

        // Without view-buying-price, ignore whatever was submitted for
        // buyingPrice (a hidden field can still be tampered with client-side)
        // and carry the product's last known cost forward instead — the
        // field is never zero-filled here since batches feed COGS directly.
        $buyingPriceCents = $canSeeBuyingPrice
            ? (int) round($data['buyingPrice'] * 100)
            : $product->buying_price_cents;

        $action(
            $product,
            $data['batchNumber'] ?: null,
            $data['expiryDate'] ?: null,
            (string) $data['quantityReceived'],
            $buyingPriceCents,
            $data['receivedAt'],
            auth()->user(),
            $this->supplierId,
        );

        $this->lastReceivedMessage = "Received {$data['quantityReceived']} {$product->base_unit} of {$product->name}.";
        $this->clearProduct();
    }

    public function render()
    {
        return view('livewire.inventory.receive-stock.index', [
            'searchResults' => $this->search !== ''
                ? Product::where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")->orWhere('barcode', 'like', "%{$this->search}%");
                })->orderBy('name')->limit(10)->get()
                : collect(),
            'selectedProduct' => $this->productId ? Product::find($this->productId) : null,
            'selectedSupplier' => $this->supplierId ? Supplier::find($this->supplierId) : null,
            'supplierResults' => $this->supplierSearch !== ''
                ? Supplier::where('name', 'like', "%{$this->supplierSearch}%")->orderBy('name')->limit(8)->get()
                : collect(),
            'recentReceipts' => Batch::with(['product', 'supplier'])->latest('created_at')->limit(10)->get(),
        ]);
    }
}
