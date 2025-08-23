<?php

use App\Enums\StockMovementSourceType;
use App\Models\Grn;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\BottleService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GRN Invariants
it('soft deleting a GRN reverses stock movements', function () {
    $inventoryService = app(InventoryService::class);
    $product = Product::factory()->create();
    $user = User::factory()->create();

    // Create GRN
    $grn = Grn::factory()->for($user, 'createdBy')->create();
    $grn->items()->create([
        'product_id' => $product->id,
        'qty' => 10,
        'unit_cost' => 10.00,
        'line_total' => 100.00,
    ]);

    // Manually post IN movement (as it's done in CreateGrn page)
    $inventoryService->postIn(
        product: $product,
        qty: 10,
        value: 10.00,
        sourceType: StockMovementSourceType::GRN,
        sourceId: $grn->id,
        occurredAt: $grn->delivered_at,
        meta: ['grn_item_id' => $grn->id],
    );

    expect($inventoryService->stockOnHand($product))->toBe(10);

    // Soft delete GRN
    $grn->delete();

    // Expect stock to be reversed
    expect($inventoryService->stockOnHand($product))->toBe(0);
});

it('restoring a GRN reposts stock movements', function () {
    $inventoryService = app(InventoryService::class);
    $product = Product::factory()->create();
    $user = User::factory()->create();

    // Create GRN
    $grn = Grn::factory()->for($user, 'createdBy')->create();
    $grn->items()->create([
        'product_id' => $product->id,
        'qty' => 10,
        'unit_cost' => 10.00,
        'line_total' => 100.00,
    ]);

    // Manually post IN movement
    $inventoryService->postIn(
        product: $product,
        qty: 10,
        value: 10.00,
        sourceType: StockMovementSourceType::GRN,
        sourceId: $grn->id,
        occurredAt: $grn->delivered_at,
        meta: ['grn_item_id' => $grn->id],
    );

    $grn->delete(); // Soft delete
    expect($inventoryService->stockOnHand($product))->toBe(0);

    $grn->restore(); // Restore

    // Expect stock to be re-posted
    expect($inventoryService->stockOnHand($product))->toBe(10);
});

// Invoice Invariants
it('soft deleting an Invoice reverses stock and bottle movements', function () {
    $inventoryService = app(InventoryService::class);
    $bottleService = app(BottleService::class);
    $productRefillable = Product::factory()->refillable()->create();
    $productNonRefillable = Product::factory()->create(['is_refillable' => false]);
    $shop = Shop::factory()->create();
    $user = User::factory()->create();

    // Create Invoice
    $invoice = Invoice::factory()->for($shop)->for($user, 'issuedBy')->create();
    $invoice->items()->create([
        'product_id' => $productRefillable->id,
        'qty' => 5,
        'unit_price' => 50.00,
        'line_total' => 250.00,
        'returned_empty' => 2,
        'damaged_lost' => 1,
        'line_fine' => 5.00,
    ]);
    $invoice->items()->create([
        'product_id' => $productNonRefillable->id,
        'qty' => 3,
        'unit_price' => 30.00,
        'line_total' => 90.00,
    ]);

    // Manually post movements (as done in CreateInvoice page)
    $inventoryService->postOut(
        product: $productRefillable,
        qty: 5,
        value: 50.00,
        sourceType: StockMovementSourceType::INVOICE,
        sourceId: $invoice->id,
        occurredAt: $invoice->issued_at,
        meta: ['invoice_item_id' => $invoice->id],
    );
    $inventoryService->postOut(
        product: $productNonRefillable,
        qty: 3,
        value: 30.00,
        sourceType: StockMovementSourceType::INVOICE,
        sourceId: $invoice->id,
        occurredAt: $invoice->issued_at,
        meta: ['invoice_item_id' => $invoice->id],
    );

    $bottleService->deliver($shop, $productRefillable, 5, $invoice, $invoice->issued_at);
    $bottleService->returned($shop, $productRefillable, 2, $invoice, $invoice->issued_at);
    $bottleService->damaged($shop, $productRefillable, 1, $invoice, $invoice->issued_at);

    // Initial stock and balance
    expect($inventoryService->stockOnHand($productRefillable))->toBe(-5);
    expect($inventoryService->stockOnHand($productNonRefillable))->toBe(-3);
    expect($shop->refresh()->refillable_balance)->toBe(5 - 2 - 1); // 2

    // Soft delete Invoice
    $invoice->delete();

    // Expect stock and bottle balance to be reversed
    expect($inventoryService->stockOnHand($productRefillable))->toBe(0);
    expect($inventoryService->stockOnHand($productNonRefillable))->toBe(0);
    expect($shop->refresh()->refillable_balance)->toBe(0);
});

it('restoring an Invoice reposts stock and bottle movements', function () {
    $inventoryService = app(InventoryService::class);
    $bottleService = app(BottleService::class);
    $productRefillable = Product::factory()->refillable()->create();
    $productNonRefillable = Product::factory()->create(['is_refillable' => false]);
    $shop = Shop::factory()->create();
    $user = User::factory()->create();

    // Create Invoice
    $invoice = Invoice::factory()->for($shop)->for($user, 'issuedBy')->create();
    $invoice->items()->create([
        'product_id' => $productRefillable->id,
        'qty' => 5,
        'unit_price' => 50.00,
        'line_total' => 250.00,
        'returned_empty' => 2,
        'damaged_lost' => 1,
        'line_fine' => 5.00,
    ]);
    $invoice->items()->create([
        'product_id' => $productNonRefillable->id,
        'qty' => 3,
        'unit_price' => 30.00,
        'line_total' => 90.00,
    ]);

    // Manually post movements
    $inventoryService->postOut(
        product: $productRefillable,
        qty: 5,
        value: 50.00,
        sourceType: StockMovementSourceType::INVOICE,
        sourceId: $invoice->id,
        occurredAt: $invoice->issued_at,
        meta: ['invoice_item_id' => $invoice->id],
    );
    $inventoryService->postOut(
        product: $productNonRefillable,
        qty: 3,
        value: 30.00,
        sourceType: StockMovementSourceType::INVOICE,
        sourceId: $invoice->id,
        occurredAt: $invoice->issued_at,
        meta: ['invoice_item_id' => $invoice->id],
    );

    $bottleService->deliver($shop, $productRefillable, 5, $invoice, $invoice->issued_at);
    $bottleService->returned($shop, $productRefillable, 2, $invoice, $invoice->issued_at);
    $bottleService->damaged($shop, $productRefillable, 1, $invoice, $invoice->issued_at);

    $invoice->delete(); // Soft delete
    expect($inventoryService->stockOnHand($productRefillable))->toBe(0);
    expect($inventoryService->stockOnHand($productNonRefillable))->toBe(0);
    expect($shop->refresh()->refillable_balance)->toBe(0);

    $invoice->restore(); // Restore

    // Expect stock and bottle balance to be re-posted
    expect($inventoryService->stockOnHand($productRefillable))->toBe(-5);
    expect($inventoryService->stockOnHand($productNonRefillable))->toBe(-3);
    expect($shop->refresh()->refillable_balance)->toBe(5 - 2 - 1); // 2
});
