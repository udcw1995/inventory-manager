<?php

namespace Tests\Feature;

use App\Livewire\Grn\ListGrn;
use App\Models\Grn;
use App\Models\User;
use App\Services\NumberGeneratorService;
use App\Enums\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GrnListTest extends TestCase
{
    use RefreshDatabase;

    public function test_grn_list_component_renders()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ListGrn::class)
            ->assertStatus(200)
            ->assertSee('Good Received Notes (GRNs)');
    }

    public function test_grn_list_shows_grns()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test GRNs
        $numberGenerator = app(NumberGeneratorService::class);
        $grn1 = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'John Doe',
            'delivery_person_contact' => '1234567890',
            'vehicle_no' => 'ABC-123',
            'delivered_at' => now(),
            'total_cost' => 100.00,
            'total_items' => 10,
            'total_refillable' => 5,
            'total_non_refillable' => 5,
            'created_by' => $user->id,
        ]);

        $grn2 = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'Jane Smith',
            'delivery_person_contact' => '9876543210',
            'vehicle_no' => 'XYZ-789',
            'delivered_at' => now()->subDays(5),
            'total_cost' => 200.00,
            'total_items' => 20,
            'total_refillable' => 10,
            'total_non_refillable' => 10,
            'created_by' => $user->id,
        ]);

        Livewire::test(ListGrn::class)
            ->assertSee($grn1->code)
            ->assertSee($grn2->code)
            ->assertSee('John Doe')
            ->assertSee('Jane Smith');
    }

    public function test_grn_list_search_functionality()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $numberGenerator = app(NumberGeneratorService::class);
        $grn1 = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'John Doe',
            'delivery_person_contact' => '1234567890',
            'vehicle_no' => 'ABC-123',
            'delivered_at' => now(),
            'total_cost' => 100.00,
            'total_items' => 10,
            'total_refillable' => 5,
            'total_non_refillable' => 5,
            'created_by' => $user->id,
        ]);

        $grn2 = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'Jane Smith',
            'delivery_person_contact' => '9876543210',
            'vehicle_no' => 'XYZ-789',
            'delivered_at' => now(),
            'total_cost' => 200.00,
            'total_items' => 20,
            'total_refillable' => 10,
            'total_non_refillable' => 10,
            'created_by' => $user->id,
        ]);

        Livewire::test(ListGrn::class)
            ->set('search', 'John')
            ->assertSee($grn1->code)
            ->assertDontSee($grn2->code)
            ->set('search', 'Jane')
            ->assertSee($grn2->code)
            ->assertDontSee($grn1->code);
    }

    public function test_grn_list_year_filter()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $numberGenerator = app(NumberGeneratorService::class);
        
        // Create GRN for current year
        $currentYearGrn = Grn::create([
            'code' => $numberGenerator->next(DocumentType::GRN),
            'delivery_person_name' => 'Current Year',
            'delivery_person_contact' => '1234567890',
            'vehicle_no' => 'CUR-123',
            'delivered_at' => now(),
            'total_cost' => 100.00,
            'total_items' => 10,
            'total_refillable' => 5,
            'total_non_refillable' => 5,
            'created_by' => $user->id,
        ]);

        $component = Livewire::test(ListGrn::class);
        
        // Test that GRN is visible initially
        $component->assertSee($currentYearGrn->code);

        // Test filtering by current year
        $component->set('filterYear', (string) now()->year)
                  ->assertSee($currentYearGrn->code);

        // Test filtering by a different year (should show no results)
        $component->set('filterYear', '2020')
                  ->assertDontSee($currentYearGrn->code);
    }
}
