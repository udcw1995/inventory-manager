<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Create 6 refillable products (3 flavors x 2 SKUs)
        $flavors = ['Vanilla', 'Chocolate', 'Strawberry'];
        foreach ($flavors as $flavor) {
            Product::factory()->count(2)->state(['flavor' => $flavor])->refillable()->create();
        }

        // Create 6 non-refillable products
        Product::factory()->count(6)->create(['is_refillable' => false, 'fine_per_damaged' => null]);

        // Create 5 shops
        Shop::factory()->count(5)->create();

        // Create users and assign roles
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $adminUser->assignRole('admin');

        $keeperUser = User::factory()->create([
            'name' => 'Keeper User',
            'email' => 'keeper@example.com',
            'password' => 'password',
        ]);
        $keeperUser->assignRole('keeper');

        $deliveryUser = User::factory()->create([
            'name' => 'Delivery User',
            'email' => 'delivery@example.com',
            'password' => 'password',
        ]);
        $deliveryUser->assignRole('delivery');
    }
}
