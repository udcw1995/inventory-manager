<?php

namespace App\Console\Commands;

use App\Enums\DocumentType;
use App\Enums\StockMovementSourceType;
use App\Models\Grn;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\BottleService;
use App\Services\InventoryService;
use App\Services\NumberGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;

class AppDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds demo data and echoes URLs for a clickable tour.';

    public function handle(
        InventoryService $inventoryService,
        BottleService $bottleService,
        NumberGeneratorService $numberGeneratorService,
    ): void {
        $this->info('Seeding demo data...');

        // Clear and re-seed database
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Database migrated and seeded.');

        // Create a refillable product for demo
        $refillableProduct = Product::factory()->refillable()->create([
            'name' => 'Demo Refillable Product',
            'sku' => 'DEMO-REF-001',
        ]);

        // Create a non-refillable product for demo
        $nonRefillableProduct = Product::factory()->create([
            'name' => 'Demo Non-Refillable Product',
            'sku' => 'DEMO-NON-001',
            'is_refillable' => false,
            'fine_per_damaged' => null,
        ]);

        // Create a demo shop
        $demoShop = Shop::factory()->create([
            'name' => 'Demo Shop',
            'owner_name' => 'Demo Shop Owner',
        ]);

        // Get demo users
        $adminUser = User::where('email', 'admin@example.com')->first();
        $deliveryUser = User::where('email', 'delivery@example.com')->first();

        // Seed demo GRN
        $this->info('Creating demo GRN...');
        $grn = Grn::factory()->for($adminUser, 'createdBy')->create([
            'code' => $numberGeneratorService->next(DocumentType::GRN),
            'delivered_at' => Carbon::now(),
        ]);
        $grn->items()->create([
            'product_id' => $refillableProduct->id,
            'qty' => 100,
            'unit_cost' => $refillableProduct->purchase_cost,
            'line_total' => 100 * $refillableProduct->purchase_cost,
        ]);
        $grn->items()->create([
            'product_id' => $nonRefillableProduct->id,
            'qty' => 50,
            'unit_cost' => $nonRefillableProduct->purchase_cost,
            'line_total' => 50 * $nonRefillableProduct->purchase_cost,
        ]);

        // Post IN movements for GRN
        $inventoryService->postIn(
            product: $refillableProduct,
            qty: 100,
            value: $refillableProduct->purchase_cost,
            sourceType: StockMovementSourceType::GRN,
            sourceId: $grn->id,
            occurredAt: $grn->delivered_at,
            meta: ['grn_item_id' => $grn->id],
        );
        $inventoryService->postIn(
            product: $nonRefillableProduct,
            qty: 50,
            value: $nonRefillableProduct->purchase_cost,
            sourceType: StockMovementSourceType::GRN,
            sourceId: $grn->id,
            occurredAt: $grn->delivered_at,
            meta: ['grn_item_id' => $grn->id],
        );
        $this->info('Demo GRN created.');

        // Seed two Invoices with returns/damaged
        $this->info('Creating demo Invoices...');

        // Invoice 1: Sales with some returns
        $invoice1 = Invoice::factory()->for($demoShop)->for($deliveryUser, 'issuedBy')->create([
            'code' => $numberGeneratorService->next(DocumentType::INV),
            'issued_at' => Carbon::now()->subDays(5),
            'total_items' => 10,
            'total_refillable' => 10,
            'total_non_refillable' => 0,
            'returned_refillable_total' => 2,
            'damaged_lost_total' => 0,
            'total_cost' => 10 * $refillableProduct->selling_price,
            'fines_total' => 0,
            'final_total' => 10 * $refillableProduct->selling_price,
        ]);
        $invoice1->items()->create([
            'product_id' => $refillableProduct->id,
            'qty' => 10,
            'unit_price' => $refillableProduct->selling_price,
            'line_total' => 10 * $refillableProduct->selling_price,
            'returned_empty' => 2,
            'damaged_lost' => 0,
            'line_fine' => 0,
        ]);

        // Post OUT movement for Invoice 1
        $inventoryService->postOut(
            product: $refillableProduct,
            qty: 10,
            value: $refillableProduct->selling_price,
            sourceType: StockMovementSourceType::INVOICE,
            sourceId: $invoice1->id,
            occurredAt: $invoice1->issued_at,
            meta: ['invoice_item_id' => $invoice1->id],
        );
        $bottleService->deliver($demoShop, $refillableProduct, 10, $invoice1, $invoice1->issued_at);
        $bottleService->returned($demoShop, $refillableProduct, 2, $invoice1, $invoice1->issued_at);

        // Invoice 2: Sales with some damaged items
        $invoice2 = Invoice::factory()->for($demoShop)->for($deliveryUser, 'issuedBy')->create([
            'code' => $numberGeneratorService->next(DocumentType::INV),
            'issued_at' => Carbon::now()->subDays(2),
            'total_items' => 5,
            'total_refillable' => 5,
            'total_non_refillable' => 0,
            'returned_refillable_total' => 0,
            'damaged_lost_total' => 1,
            'total_cost' => 5 * $refillableProduct->selling_price,
            'fines_total' => 1 * $refillableProduct->fine_per_damaged,
            'final_total' => (5 * $refillableProduct->selling_price) + (1 * $refillableProduct->fine_per_damaged),
        ]);
        $invoice2->items()->create([
            'product_id' => $refillableProduct->id,
            'qty' => 5,
            'unit_price' => $refillableProduct->selling_price,
            'line_total' => 5 * $refillableProduct->selling_price,
            'returned_empty' => 0,
            'damaged_lost' => 1,
            'line_fine' => 1 * $refillableProduct->fine_per_damaged,
        ]);

        // Post OUT movement for Invoice 2
        $inventoryService->postOut(
            product: $refillableProduct,
            qty: 5,
            value: $refillableProduct->selling_price,
            sourceType: StockMovementSourceType::INVOICE,
            sourceId: $invoice2->id,
            occurredAt: $invoice2->issued_at,
            meta: ['invoice_item_id' => $invoice2->id],
        );
        $bottleService->deliver($demoShop, $refillableProduct, 5, $invoice2, $invoice2->issued_at);
        $bottleService->damaged($demoShop, $refillableProduct, 1, $invoice2, $invoice2->issued_at);

        // Recalculate shop balance after all movements
        $bottleService->recalculateShopBalance($demoShop->id);

        $this->info('Demo Invoices created.');

        $this->info('Demo data seeding complete. You can now explore the application:');
        $this->info('------------------------------------------------------------------');
        $this->info('Admin Login: admin@example.com / password');
        $this->info('Keeper Login: keeper@example.com / password');
        $this->info('Delivery Login: delivery@example.com / password');
        $this->info('------------------------------------------------------------------');
        $this->info('Filament Dashboard: ' . URL::to('admin'));
        $this->info('Demo GRN: ' . URL::to('admin/grns/' . $grn->id . '/edit'));
        $this->info('Demo Invoice 1: ' . URL::to('admin/invoices/' . $invoice1->id . '/edit'));
        $this->info('Demo Invoice 2: ' . URL::to('admin/invoices/' . $invoice2->id . '/edit'));
        $this->info('------------------------------------------------------------------');
    }
}
