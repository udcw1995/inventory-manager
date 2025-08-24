<?php

namespace App\Livewire\Grn;

use App\Enums\DocumentType;
use App\Enums\StockMovementSourceType;
use App\Models\Grn;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\NumberGeneratorService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

class CreateGrn extends Component
{
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

    public function mount()
    {
        $this->delivered_at = now()->format('Y-m-d\TH:i');
        $this->initializeItems();
    }

    public function initializeItems()
    {
        $products = Product::where('active', true)->orderBy('name')->get();
        
        $this->items = $products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_flavor' => $product->flavor,
                'is_refillable' => $product->is_refillable,
                'qty' => 0,
                'unit_cost' => $product->purchase_cost,
                'line_total' => 0,
            ];
        })->toArray();
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
        try {
            Log::info('GRN Save method called', [
                'user_id' => Auth::id(),
                'items_count' => count($this->items),
                'delivery_person_name' => $this->delivery_person_name,
                'delivery_person_contact' => $this->delivery_person_contact,
                'vehicle_no' => $this->vehicle_no,
                'delivered_at' => $this->delivered_at
            ]);

            // Debug: Check if we have products
            if (empty($this->items)) {
                Log::warning('GRN Save: No items found');
                session()->flash('error', 'No products available. Please create products first.');
                return;
            }

            Log::info('GRN Save: Starting validation');
            $this->validate();
            Log::info('GRN Save: Validation passed');

            // Filter items with quantity > 0
            $itemsWithQuantity = collect($this->items)->filter(function ($item) {
                return ($item['qty'] ?? 0) > 0;
            });

            Log::info('GRN Save: Items with quantity', [
                'total_items' => count($this->items),
                'items_with_qty' => $itemsWithQuantity->count()
            ]);

            if ($itemsWithQuantity->isEmpty()) {
                Log::warning('GRN Save: No items with quantity > 0');
                session()->flash('error', 'Please add at least one item with quantity greater than 0.');
                return;
            }

            Log::info('GRN Save: Starting transaction');
            DB::transaction(function () use ($itemsWithQuantity) {
                $numberGeneratorService = app(NumberGeneratorService::class);
                $inventoryService = app(InventoryService::class);

                Log::info('GRN Save: Generating code');
                $code = $numberGeneratorService->next(DocumentType::GRN);
                Log::info('GRN Save: Generated code', ['code' => $code]);

                // Create GRN
                $grn = Grn::create([
                    'code' => $code,
                    'delivery_person_name' => $this->delivery_person_name,
                    'delivery_person_contact' => $this->delivery_person_contact,
                    'vehicle_no' => $this->vehicle_no,
                    'delivered_at' => $this->delivered_at,
                    'total_cost' => $this->totalCost,
                    'total_items' => $this->totalItems,
                    'total_refillable' => $this->totalRefillable,
                    'total_non_refillable' => $this->totalNonRefillable,
                    'created_by' => Auth::id(),
                ]);

                Log::info('GRN Save: GRN created', ['grn_id' => $grn->id, 'code' => $grn->code]);

                // Create GRN Items and Stock Movements
                foreach ($itemsWithQuantity as $item) {
                    $grnItem = $grn->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'unit_cost' => $item['unit_cost'],
                        'line_total' => $item['line_total'],
                    ]);

                    Log::info('GRN Save: GRN item created', [
                        'grn_item_id' => $grnItem->id,
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty']
                    ]);

                    // Post inventory movement
                    $product = Product::find($item['product_id']);
                    $inventoryService->postIn(
                        product: $product,
                        qty: $item['qty'],
                        value: $item['unit_cost'],
                        sourceType: StockMovementSourceType::GRN,
                        sourceId: $grn->id,
                        occurredAt: $grn->delivered_at,
                        meta: ['grn_item_id' => $grnItem->id],
                    );

                    Log::info('GRN Save: Inventory posted', [
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty']
                    ]);
                }

                Log::info('GRN Save: Transaction completed successfully', ['grn_code' => $grn->code]);
                session()->flash('message', "GRN {$grn->code} created successfully!");
                
                // Use Livewire redirect for better compatibility
                $this->redirect(route('filament.admin.resources.grns.index'), navigate: true);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('GRN Save: Validation error', ['errors' => $e->errors()]);
            // Re-throw validation exceptions so they show properly
            throw $e;
        } catch (\Exception $e) {
            Log::error('GRN Save: Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'data' => $this->items
            ]);
            session()->flash('error', 'Error creating GRN: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.grn.create-grn');
    }
}
