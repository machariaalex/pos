<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $receivedBy = User::where('email', 'manager@agrovet.test')->firstOrFail();

        $products = [
            // Feeds — sold loose by kg from 50kg bags.
            ['cat' => 'Feeds', 'name' => 'Dairy Meal', 'unit' => 'kg', 'buy' => 52, 'sell' => 65, 'reorder' => 100, 'batches' => [
                ['bag_kg' => 50, 'bags' => 6, 'expiry' => '+9 months'],
                ['bag_kg' => 50, 'bags' => 4, 'expiry' => '+45 days'],
            ]],
            ['cat' => 'Feeds', 'name' => 'Layers Mash', 'unit' => 'kg', 'buy' => 50, 'sell' => 62, 'reorder' => 100, 'batches' => [
                ['bag_kg' => 50, 'bags' => 8, 'expiry' => '+8 months'],
            ]],
            ['cat' => 'Feeds', 'name' => 'Broiler Starter', 'unit' => 'kg', 'buy' => 58, 'sell' => 72, 'reorder' => 75, 'batches' => [
                ['bag_kg' => 50, 'bags' => 5, 'expiry' => '+7 months'],
            ]],
            ['cat' => 'Feeds', 'name' => 'Broiler Finisher', 'unit' => 'kg', 'buy' => 56, 'sell' => 70, 'reorder' => 75, 'batches' => [
                ['bag_kg' => 50, 'bags' => 5, 'expiry' => '+7 months'],
            ]],
            ['cat' => 'Feeds', 'name' => 'Chick Mash', 'unit' => 'kg', 'buy' => 55, 'sell' => 68, 'reorder' => 50, 'batches' => [
                ['bag_kg' => 50, 'bags' => 4, 'expiry' => '+6 months'],
            ]],
            ['cat' => 'Feeds', 'name' => 'Pig Finisher Pellets', 'unit' => 'kg', 'buy' => 54, 'sell' => 67, 'reorder' => 50, 'batches' => [
                ['bag_kg' => 50, 'bags' => 3, 'expiry' => '+6 months'],
            ]],
            ['cat' => 'Feeds', 'name' => 'Rabbit Pellets', 'unit' => 'kg', 'buy' => 60, 'sell' => 75, 'reorder' => 30, 'batches' => [
                ['bag_kg' => 25, 'bags' => 4, 'expiry' => '+6 months'],
            ]],
            ['cat' => 'Feeds', 'name' => 'Calf Pellets', 'unit' => 'kg', 'buy' => 53, 'sell' => 66, 'reorder' => 50, 'batches' => [
                ['bag_kg' => 50, 'bags' => 3, 'expiry' => '+9 months'],
            ]],

            // Vet Medicines — batch/expiry critical, sold per ml/pcs.
            ['cat' => 'Vet Medicines', 'name' => 'Oxytetracycline LA 20% 100ml', 'unit' => 'ml', 'buy' => 12, 'sell' => 20, 'reorder' => 500, 'batches' => [
                ['size_ml' => 100, 'units' => 10, 'expiry' => '+14 months'],
                ['size_ml' => 100, 'units' => 6, 'expiry' => '+20 days'],
            ]],
            ['cat' => 'Vet Medicines', 'name' => 'Ivermectin 1% Injectable 50ml', 'unit' => 'ml', 'buy' => 18, 'sell' => 28, 'reorder' => 300, 'batches' => [
                ['size_ml' => 50, 'units' => 12, 'expiry' => '+16 months'],
            ]],
            ['cat' => 'Vet Medicines', 'name' => 'Amoxicillin Bolus', 'unit' => 'pcs', 'buy' => 15, 'sell' => 25, 'reorder' => 100, 'batches' => [
                ['size_ml' => null, 'units' => 200, 'expiry' => '+10 months'],
            ]],
            ['cat' => 'Vet Medicines', 'name' => 'Albendazole Dewormer 2.5L', 'unit' => 'ml', 'buy' => 4, 'sell' => 7, 'reorder' => 2000, 'batches' => [
                ['size_ml' => 2500, 'units' => 4, 'expiry' => '-10 days'],
                ['size_ml' => 2500, 'units' => 6, 'expiry' => '+11 months'],
            ]],
            ['cat' => 'Vet Medicines', 'name' => 'Multivitamin Injectable 100ml', 'unit' => 'ml', 'buy' => 10, 'sell' => 17, 'reorder' => 400, 'batches' => [
                ['size_ml' => 100, 'units' => 8, 'expiry' => '+50 days'],
            ]],
            ['cat' => 'Vet Medicines', 'name' => 'Newcastle Disease Vaccine (dose)', 'unit' => 'pcs', 'buy' => 8, 'sell' => 15, 'reorder' => 100, 'batches' => [
                ['size_ml' => null, 'units' => 300, 'expiry' => '+5 months'],
            ]],
            ['cat' => 'Vet Medicines', 'name' => 'ECF Vaccine (Infacine, dose)', 'unit' => 'pcs', 'buy' => 90, 'sell' => 140, 'reorder' => 20, 'batches' => [
                ['size_ml' => null, 'units' => 40, 'expiry' => '-3 days'],
            ]],
            ['cat' => 'Vet Medicines', 'name' => 'Penicillin-Streptomycin 100ml', 'unit' => 'ml', 'buy' => 14, 'sell' => 22, 'reorder' => 400, 'batches' => [
                ['size_ml' => 100, 'units' => 8, 'expiry' => '+13 months'],
            ]],

            // Agrochemicals.
            ['cat' => 'Agrochemicals', 'name' => 'Roundup Herbicide 1L', 'unit' => 'ml', 'buy' => 8, 'sell' => 13, 'reorder' => 2000, 'batches' => [
                ['size_ml' => 1000, 'units' => 10, 'expiry' => '+20 months'],
            ]],
            ['cat' => 'Agrochemicals', 'name' => 'Duduthrin 5EC Insecticide 1L', 'unit' => 'ml', 'buy' => 12, 'sell' => 19, 'reorder' => 1000, 'batches' => [
                ['size_ml' => 1000, 'units' => 6, 'expiry' => '+18 months'],
            ]],
            ['cat' => 'Agrochemicals', 'name' => 'Ridomil Gold Fungicide 1kg', 'unit' => 'g', 'buy' => 4, 'sell' => 7, 'reorder' => 3000, 'batches' => [
                ['size_ml' => 1000, 'units' => 5, 'expiry' => '+55 days'],
            ]],
            ['cat' => 'Agrochemicals', 'name' => 'Karate 5EC Insecticide 1L', 'unit' => 'ml', 'buy' => 11, 'sell' => 18, 'reorder' => 1000, 'batches' => [
                ['size_ml' => 1000, 'units' => 8, 'expiry' => '+17 months'],
            ]],
            ['cat' => 'Agrochemicals', 'name' => 'Actara 25WG 100g', 'unit' => 'g', 'buy' => 18, 'sell' => 28, 'reorder' => 500, 'batches' => [
                ['size_ml' => 100, 'units' => 10, 'expiry' => '+22 months'],
            ]],
            ['cat' => 'Agrochemicals', 'name' => 'Copper Fungicide 1kg', 'unit' => 'g', 'buy' => 3, 'sell' => 6, 'reorder' => 3000, 'batches' => [
                ['size_ml' => 1000, 'units' => 6, 'expiry' => '+19 months'],
            ]],

            // Seeds.
            ['cat' => 'Seeds', 'name' => 'Hybrid Maize Seed DK8031 2kg', 'unit' => 'kg', 'buy' => 550, 'sell' => 720, 'reorder' => 10, 'batches' => [
                ['size_ml' => 2, 'units' => 15, 'expiry' => '+23 months'],
            ]],
            ['cat' => 'Seeds', 'name' => 'Beans Seed Rosecoco 2kg', 'unit' => 'kg', 'buy' => 220, 'sell' => 300, 'reorder' => 10, 'batches' => [
                ['size_ml' => 2, 'units' => 12, 'expiry' => '+11 months'],
            ]],
            ['cat' => 'Seeds', 'name' => 'Tomato Seed Cal-J (packet)', 'unit' => 'pcs', 'buy' => 150, 'sell' => 220, 'reorder' => 15, 'batches' => [
                ['size_ml' => null, 'units' => 30, 'expiry' => '+14 months'],
            ]],
            ['cat' => 'Seeds', 'name' => 'Kale Seed Sukuma Wiki (packet)', 'unit' => 'pcs', 'buy' => 80, 'sell' => 130, 'reorder' => 15, 'batches' => [
                ['size_ml' => null, 'units' => 40, 'expiry' => '+40 days'],
            ]],
            ['cat' => 'Seeds', 'name' => 'Onion Seed Red Creole (packet)', 'unit' => 'pcs', 'buy' => 180, 'sell' => 260, 'reorder' => 10, 'batches' => [
                ['size_ml' => null, 'units' => 20, 'expiry' => '+16 months'],
            ]],

            // Equipment — non-perishable, no expiry.
            ['cat' => 'Equipment', 'name' => 'Knapsack Sprayer 16L', 'unit' => 'pcs', 'buy' => 2200, 'sell' => 3200, 'reorder' => 5, 'batches' => [
                ['size_ml' => null, 'units' => 8, 'expiry' => null],
            ]],
            ['cat' => 'Equipment', 'name' => 'Wheelbarrow', 'unit' => 'pcs', 'buy' => 3800, 'sell' => 5200, 'reorder' => 3, 'batches' => [
                ['size_ml' => null, 'units' => 5, 'expiry' => null],
            ]],
            ['cat' => 'Equipment', 'name' => 'Panga (Machete)', 'unit' => 'pcs', 'buy' => 450, 'sell' => 700, 'reorder' => 10, 'batches' => [
                ['size_ml' => null, 'units' => 15, 'expiry' => null],
            ]],
            ['cat' => 'Equipment', 'name' => 'Jembe (Hoe)', 'unit' => 'pcs', 'buy' => 500, 'sell' => 800, 'reorder' => 10, 'batches' => [
                ['size_ml' => null, 'units' => 12, 'expiry' => null],
            ]],
            ['cat' => 'Equipment', 'name' => 'Gumboots (pair)', 'unit' => 'pcs', 'buy' => 700, 'sell' => 1100, 'reorder' => 8, 'batches' => [
                ['size_ml' => null, 'units' => 14, 'expiry' => null],
            ]],

            // Other.
            ['cat' => 'Other', 'name' => 'Sisal Twine (roll)', 'unit' => 'pcs', 'buy' => 250, 'sell' => 400, 'reorder' => 10, 'batches' => [
                ['size_ml' => null, 'units' => 20, 'expiry' => null],
            ]],
            ['cat' => 'Other', 'name' => 'Cattle Ear Tags (pack of 25)', 'unit' => 'pcs', 'buy' => 600, 'sell' => 950, 'reorder' => 5, 'batches' => [
                ['size_ml' => null, 'units' => 10, 'expiry' => null],
            ]],
            ['cat' => 'Other', 'name' => 'Disposable Syringe 10ml (pack of 10)', 'unit' => 'pcs', 'buy' => 180, 'sell' => 300, 'reorder' => 10, 'batches' => [
                ['size_ml' => null, 'units' => 20, 'expiry' => null],
            ]],
        ];

        foreach ($products as $index => $p) {
            $product = Product::create([
                'category_id' => $categories[$p['cat']],
                'name' => $p['name'],
                'barcode' => sprintf('AGV%06d', $index + 1),
                'base_unit' => $p['unit'],
                'buying_price_cents' => $p['buy'] * 100,
                'selling_price_cents' => $p['sell'] * 100,
                'reorder_level' => $p['reorder'],
                'is_active' => true,
            ]);

            foreach ($p['batches'] as $bIndex => $b) {
                $quantity = isset($b['bag_kg'])
                    ? $b['bag_kg'] * $b['bags']
                    : ($b['size_ml'] !== null ? $b['size_ml'] * $b['units'] : $b['units']);

                Batch::create([
                    'product_id' => $product->id,
                    'batch_number' => sprintf('B%04d-%d', $index + 1, $bIndex + 1),
                    'expiry_date' => $b['expiry'] ? Carbon::now()->modify($b['expiry']) : null,
                    'quantity_received' => $quantity,
                    'quantity_remaining' => $quantity,
                    'buying_price_cents' => $p['buy'] * 100,
                    'received_at' => Carbon::now()->subDays(random_int(5, 60)),
                    'created_by' => $receivedBy->id,
                ]);
            }
        }
    }
}
