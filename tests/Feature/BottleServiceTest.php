<?php

use App\Enums\BottleMovementType;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use App\Services\BottleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('increases shop balance on delivery', function () {
    $service = new BottleService;
    $shop = Shop::factory()->create(['refillable_balance' => 0]);
    $product = Product::factory()->create();

    $service->deliver($shop, $product, 10);

    $shop->refresh();
    expect($shop->refillable_balance)->toBe(10);
    $this->assertDatabaseHas('bottle_movements', [
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'type' => BottleMovementType::DELIVERED,
        'quantity' => 10,
    ]);
});

it('decreases shop balance on return', function () {
    $service = new BottleService;
    $shop = Shop::factory()->create(['refillable_balance' => 0]);
    $product = Product::factory()->create();

    // Deliver 10 bottles first
    $service->deliver($shop, $product, 10);

    $service->returned($shop, $product, 5);

    $shop->refresh();
    expect($shop->refillable_balance)->toBe(5);
    $this->assertDatabaseHas('bottle_movements', [
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'type' => BottleMovementType::RETURNED,
        'quantity' => 5,
    ]);
});

it('decreases shop balance on damaged', function () {
    $service = new BottleService;
    $shop = Shop::factory()->create(['refillable_balance' => 0]);
    $product = Product::factory()->create();

    // Deliver 10 bottles first
    $service->deliver($shop, $product, 10);

    $service->damaged($shop, $product, 3);

    $shop->refresh();
    expect($shop->refillable_balance)->toBe(7);
    $this->assertDatabaseHas('bottle_movements', [
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'type' => BottleMovementType::DAMAGED,
        'quantity' => 3,
    ]);
});

it('recalculates shop balance correctly', function () {
    $service = new BottleService;
    $shop = Shop::factory()->create(['refillable_balance' => 0]);
    $product = Product::factory()->create();

    // Deliver 20
    $service->deliver($shop, $product, 20, null, Carbon::now()->subDays(3));

    // Return 5
    $service->returned($shop, $product, 5, null, Carbon::now()->subDays(2));

    // Damaged 3
    $service->damaged($shop, $product, 3, null, Carbon::now()->subDays(1));

    // Recalculate
    $balance = $service->recalculateShopBalance($shop->id);

    expect($balance)->toBe(20 - 5 - 3); // Expected: 12
    $shop->refresh();
    expect($shop->refillable_balance)->toBe(12);
});

it('associates bottle movements with an invoice', function () {
    $service = new BottleService;
    $shop = Shop::factory()->create();
    $product = Product::factory()->create();
    $invoice = Invoice::factory()->create();

    $movement = $service->deliver($shop, $product, 10, $invoice);

    expect($movement->invoice_id)->toBe($invoice->id);
    $this->assertDatabaseHas('bottle_movements', [
        'id' => $movement->id,
        'invoice_id' => $invoice->id,
    ]);
});
