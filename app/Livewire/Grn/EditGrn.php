<?php

namespace App\Livewire\Grn;

use App\Enums\StockMovementSourceType;
use App\Models\Grn;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Computed;

class EditGrn extends Component
{
    public Grn $grn;

    #[Validate('required|string|max:255')]
    public $delivery_person_name = '';

    #[Validate('required|string|max:255')]
    public $delivery_person_contact = '';

    #[Validate('required|string|max:255')]
    public $vehicle_no = '';

    #[Validate('required|date')]
    public $delivered_at;

    public $items = [];
    public $showCreateProductModal = false;

    // New product form fields
    #[Validate('required|string|max:255')]
    public $newProductSku = '';

    #[Validate('required|string|max:255')]
    public $newProductName = '';

    #[Validate('nullable|string|max:255')]
    public $newProductFlavor = '';

    #[Validate('required|boolean')]
    public $newProductIsRefillable = false;

    #[Validate('required|numeric|min:0')]
    public $newProductPurchaseCost = 0;

    #[Validate('required|numeric|min:0')]
    public $newProductSellingPrice = 0;

    #[Validate('nullable|numeric|min:0')]
    public $newProductFinePerDamaged = 0;

    // Totals
    public $totalCost = 0;
    public $totalItems = 0;
    public $totalRefillable = 0;
    public $totalNonRefillable = 0;

    public function mount(Grn $grn)
    {
        $this->grn = $grn;
        $this->delivery_person_name = $grn->delivery_person_name;
        $this->delivery_person_contact = $grn->delivery_person_contact;
        $this->vehicle_no = $grn->vehicle_no;
        $this->delivered_at = $grn->delivered_at->format('Y-m-d\TH:i');
        
        $this->initializeItems();
    }

    public function initializeItems()
    {
        $products = Product::where('active', true)->orderBy('name')->get();
        $existingItems = $this->grn->items->keyBy('product_id');
        
        $this->items = $products->map(function ($product) use ($existingItems) {
            $existingItem = $existingItems->get($product->id);
            
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_flavor' => $product->flavor,
                'is_refillable' => $product->is_refillable,
                'qty' => $existingItem ? $existingItem->qty : 0,
                'unit_cost' => $existingItem ? $existingItem->unit_cost : $product->purchase_cost,
                'line_total' => $existingItem ? $existingItem->line_total : 0,
            ];
        })->toArray();

        $this->calculateTotals();
    }

    #[Computed]
    public function activeProducts()
    {
        return Product::where('active', true)->orderBy('name')->get();
    }

    public function updatedItems()
    {
        $this->calculateTotals();
    }

    public function updateLineTotal($index)
    {
        $qty = (float) ($this->items[$index]['qty'] ?? 0);
        $unitCost = (float) ($this->items[$index]['unit_cost'] ?? 0);
        $this->items[$index]['line_total'] = $qty * $unitCost;
        
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalCost = 0;
        $this->totalItems = 0;
        $this->totalRefillable = 0;
        $this->totalNonRefillable = 0;

        foreach ($this->items as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $lineTotal = (float) ($item['line_total'] ?? 0);
            
            if ($qty > 0) {
                $this->totalCost += $lineTotal;
                $this->totalItems += $qty;
                
                if ($item['is_refillable']) {
                    $this->totalRefillable += $qty;
                } else {
                    $this->totalNonRefillable += $qty;
                }
            }
        }
    }

    public function openCreateProductModal()
    {
        $this->showCreateProductModal = true;
        $this->resetNewProductForm();
    }

    public function closeCreateProductModal()
    {
        $this->showCreateProductModal = false;
        $this->resetNewProductForm();
    }

    public function resetNewProductForm()
    {
        $this->newProductSku = '';
        $this->newProductName = '';
        $this->newProductFlavor = '';
        $this->newProductIsRefillable = false;
        $this->newProductPurchaseCost = 0;
        $this->newProductSellingPrice = 0;
        $this->newProductFinePerDamaged = 0;
    }

    public function createProduct()
    {
        $this->validate([
            'newProductSku' => 'required|string|max:255|unique:products,sku',
            'newProductName' => 'required|string|max:255',
            'newProductFlavor' => 'nullable|string|max:255',
            'newProductIsRefillable' => 'required|boolean',
            'newProductPurchaseCost' => 'required|numeric|min:0',
            'newProductSellingPrice' => 'required|numeric|min:0',
            'newProductFinePerDamaged' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create([
            'sku' => $this->newProductSku,
            'name' => $this->newProductName,
            'flavor' => $this->newProductFlavor,
            'is_refillable' => $this->newProductIsRefillable,
            'purchase_cost' => $this->newProductPurchaseCost,
            'selling_price' => $this->newProductSellingPrice,
            'fine_per_damaged' => $this->newProductFinePerDamaged,
            'active' => true,
        ]);

        // Add the new product to the items array
        $this->items[] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_flavor' => $product->flavor,
            'is_refillable' => $product->is_refillable,
            'qty' => 0,
            'unit_cost' => $product->purchase_cost,
            'line_total' => 0,
        ];

        $this->closeCreateProductModal();
        session()->flash('message', 'Product created successfully!');
    }

    public function save()
    {
        $this->validate();

        // Filter items with quantity > 0
        $itemsWithQuantity = collect($this->items)->filter(function ($item) {
            return ($item['qty'] ?? 0) > 0;
        });

        if ($itemsWithQuantity->isEmpty()) {
            session()->flash('error', 'Please add at least one item with quantity greater than 0.');
            return;
        }

        DB::transaction(function () use ($itemsWithQuantity) {
            $inventoryService = app(InventoryService::class);

            // Reverse all existing stock movements
            foreach ($this->grn->items as $item) {
                $movements = $this->grn->stockMovements()
                    ->where('source_id', $this->grn->id)
                    ->where('product_id', $item->product_id)
                    ->get();

                foreach ($movements as $movement) {
                    $inventoryService->reverseMovement($movement->id);
                }
            }

            // Update GRN basic info
            $this->grn->update([
                'delivery_person_name' => $this->delivery_person_name,
                'delivery_person_contact' => $this->delivery_person_contact,
                'vehicle_no' => $this->vehicle_no,
                'delivered_at' => $this->delivered_at,
                'total_cost' => $this->totalCost,
                'total_items' => $this->totalItems,
                'total_refillable' => $this->totalRefillable,
                'total_non_refillable' => $this->totalNonRefillable,
            ]);

            // Delete all existing items
            $this->grn->items()->delete();

            // Create new items and stock movements
            foreach ($itemsWithQuantity as $item) {
                $grnItem = $this->grn->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['line_total'],
                ]);

                // Post inventory movement
                $product = Product::find($item['product_id']);
                $inventoryService->postIn(
                    product: $product,
                    qty: $item['qty'],
                    value: $item['unit_cost'],
                    sourceType: StockMovementSourceType::GRN,
                    sourceId: $this->grn->id,
                    occurredAt: $this->grn->delivered_at,
                    meta: ['grn_item_id' => $grnItem->id],
                );
            }

            session()->flash('message', "GRN {$this->grn->code} updated successfully!");
            $this->redirect(route('filament.admin.resources.grns.index'), navigate: true);
        });
    }

    public function delete()
    {
        DB::transaction(function () {
            $inventoryService = app(InventoryService::class);

            // Reverse all movements associated with this GRN
            foreach ($this->grn->items as $item) {
                $movements = $this->grn->stockMovements()
                    ->where('source_id', $this->grn->id)
                    ->where('product_id', $item->product_id)
                    ->get();

                foreach ($movements as $movement) {
                    $inventoryService->reverseMovement($movement->id);
                }
            }

            $this->grn->delete();
            session()->flash('message', "GRN {$this->grn->code} deleted successfully!");
            $this->redirect(route('filament.admin.resources.grns.index'), navigate: true);
        });
    }

    public function render()
    {
        return view('livewire.grn.edit-grn');
    }
}
