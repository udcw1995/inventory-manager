<?php

use App\Models\BottleMovement;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('a GRN belongs to a user and has many items', function () {
    $user = User::factory()->create();
    $grn = Grn::factory()->for($user, 'createdBy')->create();
    $grnItem = GrnItem::factory()->for($grn)->create();

    expect($grn->createdBy)->toBeInstanceOf(User::class);
    expect($grn->createdBy->id)->toBe($user->id);
    expect($grn->items)->toHaveCount(1);
    expect($grn->items->first())->toBeInstanceOf(GrnItem::class);
    expect($grn->items->first()->id)->toBe($grnItem->id);
});

it('a GRN item belongs to a GRN and a product', function () {
    $grn = Grn::factory()->create();
    $product = Product::factory()->create();
    $grnItem = GrnItem::factory()->for($grn)->for($product)->create();

    expect($grnItem->grn)->toBeInstanceOf(Grn::class);
    expect($grnItem->grn->id)->toBe($grn->id);
    expect($grnItem->product)->toBeInstanceOf(Product::class);
    expect($grnItem->product->id)->toBe($product->id);
});

it('an Invoice belongs to a shop and a user and has many items', function () {
    $shop = Shop::factory()->create();
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($shop)->for($user, 'issuedBy')->create();
    $invoiceItem = InvoiceItem::factory()->for($invoice)->create();

    expect($invoice->shop)->toBeInstanceOf(Shop::class);
    expect($invoice->shop->id)->toBe($shop->id);
    expect($invoice->issuedBy)->toBeInstanceOf(User::class);
    expect($invoice->issuedBy->id)->toBe($user->id);
    expect($invoice->items)->toHaveCount(1);
    expect($invoice->items->first())->toBeInstanceOf(InvoiceItem::class);
    expect($invoice->items->first()->id)->toBe($invoiceItem->id);
});

it('an Invoice item belongs to an Invoice and a product', function () {
    $invoice = Invoice::factory()->create();
    $product = Product::factory()->create();
    $invoiceItem = InvoiceItem::factory()->for($invoice)->for($product)->create();

    expect($invoiceItem->invoice)->toBeInstanceOf(Invoice::class);
    expect($invoiceItem->invoice->id)->toBe($invoice->id);
    expect($invoiceItem->product)->toBeInstanceOf(Product::class);
    expect($invoiceItem->product->id)->toBe($product->id);
});

it('a Stock Movement belongs to a product and can be a reversal of another stock movement', function () {
    $product = Product::factory()->create();
    $stockMovement = StockMovement::factory()->for($product)->create();
    $reversalMovement = StockMovement::factory()->for($product)->for($stockMovement, 'reversalOf')->create();

    expect($stockMovement->product)->toBeInstanceOf(Product::class);
    expect($stockMovement->product->id)->toBe($product->id);
    expect($reversalMovement->reversalOf)->toBeInstanceOf(StockMovement::class);
    expect($reversalMovement->reversalOf->id)->toBe($stockMovement->id);
});

it('a Bottle Movement belongs to a shop, a product and can belong to an invoice', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create();
    $invoice = Invoice::factory()->create();
    $bottleMovement = BottleMovement::factory()->for($shop)->for($product)->for($invoice)->create();

    expect($bottleMovement->shop)->toBeInstanceOf(Shop::class);
    expect($bottleMovement->shop->id)->toBe($shop->id);
    expect($bottleMovement->product)->toBeInstanceOf(Product::class);
    expect($bottleMovement->product->id)->toBe($product->id);
    expect($bottleMovement->invoice)->toBeInstanceOf(Invoice::class);
    expect($bottleMovement->invoice->id)->toBe($invoice->id);
});
