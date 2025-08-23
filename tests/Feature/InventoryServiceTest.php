<?php

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementSourceType;
use App\Models\Grn;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can post an IN movement', function () {
    $service = new InventoryService;
    $product = Product::factory()->create();
    $grn = Grn::factory()->create();

    $movement = $service->postIn(
        product: $product,
        qty: 10,
        value: 100.00,
        sourceType: StockMovementSourceType::GRN,
        sourceId: $grn->id,
        occurredAt: Carbon::now(),
        meta: ['note' => 'Test IN movement'],
    );

    expect($movement)->toBeInstanceOf(StockMovement::class);
    expect($movement->product_id)->toBe($product->id);
    expect($movement->direction)->toBe(StockMovementDirection::IN);
    expect($movement->quantity)->toBe(10);
    expect((float)$movement->value)->toBe(100.00);
    expect($movement->source_type)->toBe(StockMovementSourceType::GRN);
    expect($movement->source_id)->toBe($grn->id);
    expect($movement->meta)->toEqual(['note' => 'Test IN movement']);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'direction' => StockMovementDirection::IN,
        'quantity' => 10,
        'value' => 100.00,
        'source_type' => StockMovementSourceType::GRN,
        'source_id' => $grn->id,
    ]);
});

it('can post an OUT movement', function () {
    $service = new InventoryService;
    $product = Product::factory()->create();
    $invoice = Invoice::factory()->create();

    $movement = $service->postOut(
        product: $product,
        qty: 5,
        value: 50.00,
        sourceType: StockMovementSourceType::INVOICE,
        sourceId: $invoice->id,
        occurredAt: Carbon::now(),
        meta: ['note' => 'Test OUT movement'],
    );

    expect($movement)->toBeInstanceOf(StockMovement::class);
    expect($movement->product_id)->toBe($product->id);
    expect($movement->direction)->toBe(StockMovementDirection::OUT);
    expect($movement->quantity)->toBe(5);
    expect((float)$movement->value)->toBe(50.00);
    expect($movement->source_type)->toBe(StockMovementSourceType::INVOICE);
    expect($movement->source_id)->toBe($invoice->id);
    expect($movement->meta)->toEqual(['note' => 'Test OUT movement']);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'direction' => StockMovementDirection::OUT,
        'quantity' => 5,
        'value' => 50.00,
        'source_type' => StockMovementSourceType::INVOICE,
        'source_id' => $invoice->id,
    ]);
});

it('can reverse an IN movement', function () {
    $service = new InventoryService;
    $product = Product::factory()->create();
    $grn = Grn::factory()->create();

    $originalMovement = $service->postIn(
        product: $product,
        qty: 10,
        value: 100.00,
        sourceType: StockMovementSourceType::GRN,
        sourceId: $grn->id,
        occurredAt: Carbon::now(),
    );

    $reversedMovement = $service->reverseMovement($originalMovement->id);

    expect($reversedMovement)->toBeInstanceOf(StockMovement::class);
    expect($reversedMovement->product_id)->toBe($product->id);
    expect($reversedMovement->direction)->toBe(StockMovementDirection::OUT); // IN movement reversed by OUT
    expect($reversedMovement->quantity)->toBe(10);
    expect((float)$reversedMovement->value)->toBe(100.00);
    expect($reversedMovement->source_type)->toBe(StockMovementSourceType::GRN);
    expect($reversedMovement->source_id)->toBe($grn->id);
    expect($reversedMovement->reversal_of_id)->toBe($originalMovement->id);
    $this->assertDatabaseHas('stock_movements', [
        'id' => $reversedMovement->id,
        'product_id' => $product->id,
        'direction' => StockMovementDirection::OUT,
        'quantity' => 10,
        'value' => 100.00,
        'source_type' => StockMovementSourceType::GRN,
        'source_id' => $grn->id,
        'reversal_of_id' => $originalMovement->id,
    ]);
});

it('can reverse an OUT movement', function () {
    $service = new InventoryService;
    $product = Product::factory()->create();
    $invoice = Invoice::factory()->create();

    $originalMovement = $service->postOut(
        product: $product,
        qty: 5,
        value: 50.00,
        sourceType: StockMovementSourceType::INVOICE,
        sourceId: $invoice->id,
        occurredAt: Carbon::now(),
    );

    $reversedMovement = $service->reverseMovement($originalMovement->id);

    expect($reversedMovement)->toBeInstanceOf(StockMovement::class);
    expect($reversedMovement->product_id)->toBe($product->id);
    expect($reversedMovement->direction)->toBe(StockMovementDirection::IN); // OUT movement reversed by IN
    expect($reversedMovement->quantity)->toBe(5);
    expect((float)$reversedMovement->value)->toBe(50.00);
    expect($reversedMovement->source_type)->toBe(StockMovementSourceType::INVOICE);
    expect($reversedMovement->source_id)->toBe($invoice->id);
    expect($reversedMovement->reversal_of_id)->toBe($originalMovement->id);
    $this->assertDatabaseHas('stock_movements', [
        'id' => $reversedMovement->id,
        'product_id' => $product->id,
        'direction' => StockMovementDirection::IN,
        'quantity' => 5,
        'value' => 50.00,
        'source_type' => StockMovementSourceType::INVOICE,
        'source_id' => $invoice->id,
        'reversal_of_id' => $originalMovement->id,
    ]);
});

it('calculates stock on hand correctly with IN and OUT movements', function () {
    $service = new InventoryService;
    $product = Product::factory()->create();

    // Post IN movements
    $service->postIn($product, 10, 100.00, StockMovementSourceType::GRN, 1, Carbon::now());
    $service->postIn($product, 5, 50.00, StockMovementSourceType::GRN, 2, Carbon::now());

    // Post OUT movements
    $service->postOut($product, 3, 30.00, StockMovementSourceType::INVOICE, 1, Carbon::now());
    $service->postOut($product, 2, 20.00, StockMovementSourceType::INVOICE, 2, Carbon::now());

    expect($service->stockOnHand($product))->toBe(10 + 5 - 3 - 2); // Expected: 10
});

it('calculates stock on hand correctly with reversals', function () {
    $service = new InventoryService;
    $product = Product::factory()->create();

    // Original IN movement
    $originalIn = $service->postIn($product, 10, 100.00, StockMovementSourceType::GRN, 1, Carbon::now());

    // Original OUT movement
    $originalOut = $service->postOut($product, 3, 30.00, StockMovementSourceType::INVOICE, 1, Carbon::now());

    // Reverse original IN movement
    $service->reverseMovement($originalIn->id);

    // Reverse original OUT movement
    $service->reverseMovement($originalOut->id);

    // Expected stock: (10 - 10) + (3 - 3) = 0
    expect($service->stockOnHand($product))->toBe(0);
});

it('calculates stock on hand correctly with mixed movements and reversals', function () {
    $service = new InventoryService;
    $product = Product::factory()->create();

    // Initial IN
    $service->postIn($product, 20, 200.00, StockMovementSourceType::GRN, 1, Carbon::now()); // +20

    // First OUT
    $firstOutMovement = $service->postOut($product, 5, 50.00, StockMovementSourceType::INVOICE, 1, Carbon::now()); // -5

    // Second IN
    $service->postIn($product, 10, 100.00, StockMovementSourceType::GRN, 2, Carbon::now()); // +10

    // Reverse first OUT
    $service->reverseMovement($firstOutMovement->id); // +5 (reverses -5)

    // Final stock: 20 - 5 + 10 + 5 = 30
    expect($service->stockOnHand($product))->toBe(30);
});
