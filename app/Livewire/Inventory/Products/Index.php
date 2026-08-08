<?php

namespace App\Livewire\Inventory\Products;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $categoryFilter = null;

    public bool $lowStockOnly = false;

    public bool $showForm = false;

    public ?int $editingProductId = null;

    public string $name = '';

    public ?int $categoryId = null;

    public string $barcode = '';

    public string $baseUnit = Product::UNIT_KG;

    public bool $hasSellingUnit = false;

    public string $sellingUnit = '';

    public string $unitsPerBase = '1';

    public bool $hasBulkPack = false;

    public string $packSize = '';

    public string $packPrice = '';

    public string $buyingPrice = '';

    public string $sellingPrice = '';

    public string $reorderLevel = '0';

    public bool $isActive = true;

    public bool $showCategoryForm = false;

    public string $newCategoryName = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLowStockOnly(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        Gate::authorize('edit-price');

        $this->reset([
            'editingProductId', 'name', 'categoryId', 'barcode', 'buyingPrice', 'sellingPrice', 'reorderLevel',
            'showCategoryForm', 'newCategoryName', 'hasSellingUnit', 'sellingUnit', 'unitsPerBase',
            'hasBulkPack', 'packSize', 'packPrice',
        ]);
        $this->baseUnit = Product::UNIT_KG;
        $this->unitsPerBase = '1';
        $this->isActive = true;
        $this->showForm = true;
    }

    public function startEdit(int $productId): void
    {
        Gate::authorize('edit-price');

        $product = Product::findOrFail($productId);
        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->categoryId = $product->category_id;
        $this->barcode = (string) $product->barcode;
        $this->baseUnit = $product->base_unit;
        $this->hasSellingUnit = $product->selling_unit !== null;
        $this->sellingUnit = $product->selling_unit ?? '';
        $this->unitsPerBase = $product->selling_unit ? (string) $product->units_per_base : '1';
        $this->hasBulkPack = $product->hasBulkPack();
        $this->packSize = $product->pack_size !== null ? (string) $product->pack_size : '';
        $this->packPrice = $product->pack_price_cents !== null ? number_format($product->pack_price_cents / 100, 2, '.', '') : '';
        $this->buyingPrice = number_format($product->buying_price_cents / 100, 2, '.', '');
        $this->sellingPrice = number_format($product->selling_price_cents / 100, 2, '.', '');
        $this->reorderLevel = (string) $product->reorder_level;
        $this->isActive = $product->is_active;
        $this->showCategoryForm = false;
        $this->newCategoryName = '';
        $this->showForm = true;
    }

    public function addCategory(): void
    {
        Gate::authorize('edit-price');

        $data = $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
        ]);

        $category = Category::create(['name' => $data['newCategoryName']]);
        AuditLog::record('category.created', $category, "Created category {$category->name}");

        $this->categoryId = $category->id;
        $this->newCategoryName = '';
        $this->showCategoryForm = false;
    }

    public function save(): void
    {
        Gate::authorize('edit-price');

        $canSeeBuyingPrice = Gate::allows('view-buying-price');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'categoryId' => ['required', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($this->editingProductId)],
            'baseUnit' => ['required', Rule::in(Product::UNITS)],
            'sellingUnit' => [$this->hasSellingUnit ? 'required' : 'nullable', Rule::in(Product::UNITS)],
            'unitsPerBase' => [$this->hasSellingUnit ? 'required' : 'nullable', 'numeric', 'gt:0'],
            'packSize' => [$this->hasBulkPack ? 'required' : 'nullable', 'numeric', 'gt:0'],
            'packPrice' => [$this->hasBulkPack ? 'required' : 'nullable', 'numeric', 'gt:0'],
            'buyingPrice' => [$canSeeBuyingPrice ? 'required' : 'nullable', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'reorderLevel' => ['required', 'numeric', 'min:0'],
        ]);

        $attributes = [
            'category_id' => $data['categoryId'],
            'name' => $data['name'],
            'barcode' => $this->barcode ?: null,
            'base_unit' => $data['baseUnit'],
            'selling_unit' => $this->hasSellingUnit ? $data['sellingUnit'] : null,
            'units_per_base' => $this->hasSellingUnit ? $data['unitsPerBase'] : 1,
            'pack_size' => $this->hasBulkPack ? $data['packSize'] : null,
            'pack_price_cents' => $this->hasBulkPack ? (int) round($data['packPrice'] * 100) : null,
            'selling_price_cents' => (int) round($data['sellingPrice'] * 100),
            'reorder_level' => $data['reorderLevel'],
            'is_active' => $this->isActive,
        ];

        if ($canSeeBuyingPrice) {
            $attributes['buying_price_cents'] = (int) round($data['buyingPrice'] * 100);
        } elseif (! $this->editingProductId) {
            $attributes['buying_price_cents'] = 0;
        }

        if ($this->editingProductId) {
            $product = Product::findOrFail($this->editingProductId);
            $before = $product->only(['name', 'buying_price_cents', 'selling_price_cents', 'is_active']);
            $product->update($attributes);

            $priceChanged = $before['buying_price_cents'] !== ($attributes['buying_price_cents'] ?? $before['buying_price_cents'])
                || $before['selling_price_cents'] !== $attributes['selling_price_cents'];

            AuditLog::record(
                $priceChanged ? 'product.price_changed' : 'product.updated',
                $product,
                "Updated product {$product->name}",
                $before,
                $product->only(['name', 'buying_price_cents', 'selling_price_cents', 'is_active']),
            );
        } else {
            $product = Product::create($attributes);
            AuditLog::record('product.created', $product, "Created product {$product->name}");
        }

        $this->showForm = false;
    }

    public function render()
    {
        $products = Product::query()
            ->with('category')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%");
            }))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->lowStockOnly, fn ($q) => $q->whereRaw(
                '(select coalesce(sum(quantity_remaining), 0) from batches where batches.product_id = products.id) <= products.reorder_level'
            ))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.inventory.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
