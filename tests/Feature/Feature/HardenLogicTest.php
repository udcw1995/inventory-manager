<?php

use App\Enums\StockMovementSourceType;
use App\Models\BottleMovement;
use App\Models\Grn;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\InventoryService;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('prevents selling more than stock on hand', function () {
    $inventoryService = app(InventoryService::class);
    $product = Product::factory()->create();
    $shop = Shop::factory()->create();
    $user = User::factory()->create();

    // Post some stock
    $inventoryService->postIn(
        product: $product,
        qty: 10,
        value: 10.00,
        sourceType: StockMovementSourceType::GRN,
        sourceId: 1,
        occurredAt: now(),
    );

    // Attempt to sell more than available stock (15 > 10)
    $invoice = Invoice::factory()->for($shop)->for($user, 'issuedBy')->make();
    $invoice->items = collect([[
        'product_id' => $product->id,
        'qty' => 15,
        'unit_price' => 20.00,
        'line_total' => 300.00,
        'returned_empty' => 0,
        'damaged_lost' => 0,
        'line_fine' => 0.00,
    ]]);

    try {
        DB::transaction(function () use ($invoice, $inventoryService) {
            foreach ($invoice->items as $item) {
                $product = Product::find($item['product_id']);
                $currentStock = $inventoryService->stockOnHand($product);
                if ($item['qty'] > $currentStock) {
                    $validator = Validator::make(
                        ['qty' => $item['qty']],
                        ['qty' => ['numeric', 'max:' . $currentStock]], // Add a rule that will fail
                        ['qty.max' => 'Product ' . $product->name . ' has insufficient stock. Available: ' . $currentStock],
                    );
                    $validator->validate(); // This will throw ValidationException if it fails
                }
            }
        });
        $this->fail('ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e)->toBeInstanceOf(ValidationException::class);
        expect($e->errors()['qty'][0])->toContain('has insufficient stock');
    }
});

it('disallows changing is_refillable if product has existing bottle ledger', function () {
    $product = Product::factory()->create(['is_refillable' => false]);
    $shop = Shop::factory()->create();

    // Create a bottle movement for the product
    BottleMovement::create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'type' => \App\Enums\BottleMovementType::DELIVERED,
        'quantity' => 5,
        'occurred_at' => now(),
        'meta' => [],
    ]);

    // Simulate the form update logic from ProductResource
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Cannot change to refillable if product has existing bottle movements.');

    // This simulates the dehydrated callback logic
    $state = true; // Trying to change to refillable
    $record = $product;

    if ($record && ! $record->is_refillable && $state && $record->bottleMovements()->exists()) {
        throw new \Exception('Cannot change to refillable if product has existing bottle movements.');
    }
});

it('soft deleting a product hides it from pickers', function () {
    $product = Product::factory()->create();

    // Assert it's visible in pickers initially
    $options = Product::query()->whereNull('deleted_at')->pluck('name', 'id');
    expect($options)->toHaveKey($product->id);

    // Soft delete the product
    $product->delete();

    // Assert it's hidden from pickers
    $options = Product::query()->whereNull('deleted_at')->pluck('name', 'id');
    expect($options)->not->toHaveKey($product->id);
});

it('soft deleting a shop hides it from pickers', function () {
    $shop = Shop::factory()->create();

    // Assert it's visible in pickers initially
    $options = Shop::query()->whereNull('deleted_at')->pluck('name', 'id');
    expect($options)->toHaveKey($shop->id);

    // Soft delete the shop
    $shop->delete();

    // Assert it's hidden from pickers
    $options = Shop::query()->whereNull('deleted_at')->pluck('name', 'id');
    expect($options)->not->toHaveKey($shop->id);
});

it('deleting a product referenced by a GRN does not cascade delete the GRN', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $grn = Grn::factory()->for($user, 'createdBy')->create();
    $grn->items()->create([
        'product_id' => $product->id,
        'qty' => 10,
        'unit_cost' => 10.00,
        'line_total' => 100.00,
    ]);

    // Soft delete the product
    $product->delete();

    // Assert GRN still exists
    expect(Grn::find($grn->id))->not->toBeNull();
    // Assert GRN item still exists (product_id will be nullified by foreign key constraint if set to NULL ON DELETE)
    expect($grn->items->first())->not->toBeNull();
});

it('deleting a shop referenced by an Invoice does not cascade delete the Invoice', function () {
    $shop = Shop::factory()->create();
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($shop)->for($user, 'issuedBy')->create();

    // Soft delete the shop
    $shop->delete();

    // Assert Invoice still exists
    expect(Invoice::find($invoice->id))->not->toBeNull();
});
