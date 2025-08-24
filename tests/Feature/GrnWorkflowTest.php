<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\StockMovementSourceType;
use App\Models\Grn;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\NumberGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_grn_creation_increases_inventory()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create products
        $product1 = Product::factory()->create([
            'name' => 'Test Product 1',
            'purchase_cost' => 10.00,
            'active' => true,
            'is_refillable' => true,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Test Product 2', 
            'purchase_cost' => 15.00,
            'active' => true,
            'is_refillable' => false,
        ]);

        // Check initial stock
        $inventoryService = app(InventoryService::class);
        $this->assertEquals(0, $inventoryService->stockOnHand($product1));
        $this->assertEquals(0, $inventoryService->stockOnHand($product2));

        // Create GRN
        $numberGenerator = app(NumberGeneratorService::class);
        $grn = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'John Doe',
            'delivery_person_contact' => '1234567890',
            'vehicle_no' => 'ABC-123',
            'delivered_at' => now(),
            'total_cost' => 250.00,
            'total_items' => 20,
            'total_refillable' => 10,
            'total_non_refillable' => 10,
            'created_by' => $user->id,
        ]);

        // Create GRN items
        $grnItem1 = $grn->items()->create([
            'product_id' => $product1->id,
            'qty' => 10,
            'unit_cost' => 10.00,
            'line_total' => 100.00,
        ]);

        $grnItem2 = $grn->items()->create([
            'product_id' => $product2->id,
            'qty' => 10,
            'unit_cost' => 15.00,
            'line_total' => 150.00,
        ]);

        // Post inventory movements
        $inventoryService->postIn(
            product: $product1,
            qty: 10,
            value: 10.00,
            sourceType: StockMovementSourceType::GRN,
            sourceId: $grn->id,
            occurredAt: $grn->delivered_at,
            meta: ['grn_item_id' => $grnItem1->id],
        );

        $inventoryService->postIn(
            product: $product2,
            qty: 10,
            value: 15.00,
            sourceType: StockMovementSourceType::GRN,
            sourceId: $grn->id,
            occurredAt: $grn->delivered_at,
            meta: ['grn_item_id' => $grnItem2->id],
        );

        // Check stock after GRN
        $this->assertEquals(10, $inventoryService->stockOnHand($product1));
        $this->assertEquals(10, $inventoryService->stockOnHand($product2));

        // Test GRN totals
        $this->assertEquals(20, $grn->total_items);
        $this->assertEquals(10, $grn->total_refillable);
        $this->assertEquals(10, $grn->total_non_refillable);
        $this->assertEquals(250.00, $grn->total_cost);
    }

    public function test_grn_edit_adjusts_inventory_correctly()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a product
        $product = Product::factory()->create([
            'purchase_cost' => 10.00,
            'active' => true,
        ]);

        $inventoryService = app(InventoryService::class);

        // Create initial GRN with 10 items
        $numberGenerator = app(NumberGeneratorService::class);
        $grn = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'John Doe',
            'delivery_person_contact' => '1234567890',
            'vehicle_no' => 'ABC-123',
            'delivered_at' => now(),
            'total_cost' => 100.00,
            'total_items' => 10,
            'total_refillable' => 10,
            'total_non_refillable' => 0,
            'created_by' => $user->id,
        ]);

        $grnItem = $grn->items()->create([
            'product_id' => $product->id,
            'qty' => 10,
            'unit_cost' => 10.00,
            'line_total' => 100.00,
        ]);

        // Post initial movement
        $inventoryService->postIn(
            product: $product,
            qty: 10,
            value: 10.00,
            sourceType: StockMovementSourceType::GRN,
            sourceId: $grn->id,
            occurredAt: $grn->delivered_at,
            meta: ['grn_item_id' => $grnItem->id],
        );

        $this->assertEquals(10, $inventoryService->stockOnHand($product));

        // Simulate editing: reverse old movements
        $movements = $grn->stockMovements()->where('source_id', $grn->id)->where('product_id', $product->id)->get();
        foreach ($movements as $movement) {
            $inventoryService->reverseMovement($movement->id);
        }

        // Update GRN with 15 items
        $grn->items()->delete();
        $newGrnItem = $grn->items()->create([
            'product_id' => $product->id,
            'qty' => 15,
            'unit_cost' => 10.00,
            'line_total' => 150.00,
        ]);

        $grn->update([
            'total_cost' => 150.00,
            'total_items' => 15,
        ]);

        // Post new movement
        $inventoryService->postIn(
            product: $product,
            qty: 15,
            value: 10.00,
            sourceType: StockMovementSourceType::GRN,
            sourceId: $grn->id,
            occurredAt: $grn->delivered_at,
            meta: ['grn_item_id' => $newGrnItem->id],
        );

        // Stock should now be 15
        $this->assertEquals(15, $inventoryService->stockOnHand($product));
    }

    public function test_grn_deletion_reverses_inventory()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a product
        $product = Product::factory()->create([
            'purchase_cost' => 10.00,
            'active' => true,
        ]);

        $inventoryService = app(InventoryService::class);

        // Create GRN
        $numberGenerator = app(NumberGeneratorService::class);
        $grn = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'John Doe',
            'delivery_person_contact' => '1234567890',
            'vehicle_no' => 'ABC-123',
            'delivered_at' => now(),
            'total_cost' => 100.00,
            'total_items' => 10,
            'total_refillable' => 10,
            'total_non_refillable' => 0,
            'created_by' => $user->id,
        ]);

        $grnItem = $grn->items()->create([
            'product_id' => $product->id,
            'qty' => 10,
            'unit_cost' => 10.00,
            'line_total' => 100.00,
        ]);

        // Post movement
        $inventoryService->postIn(
            product: $product,
            qty: 10,
            value: 10.00,
            sourceType: StockMovementSourceType::GRN,
            sourceId: $grn->id,
            occurredAt: $grn->delivered_at,
            meta: ['grn_item_id' => $grnItem->id],
        );

        $this->assertEquals(10, $inventoryService->stockOnHand($product));

        // Delete GRN: reverse movements
        foreach ($grn->items as $item) {
            $movements = $grn->stockMovements()->where('source_id', $grn->id)->where('product_id', $item->product_id)->get();
            foreach ($movements as $movement) {
                $inventoryService->reverseMovement($movement->id);
            }
        }

        $grn->delete();

        // Stock should be back to 0
        $this->assertEquals(0, $inventoryService->stockOnHand($product));
        $this->assertSoftDeleted($grn);
    }
}
