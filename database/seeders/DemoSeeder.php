<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DemoSeeder extends Seeder
{
    /** FEFO-ordered batches per product: productId => [['id', 'cost'], ...] */
    private array $productBatches = [];

    /** In-memory stock tracker: batchId => float remaining */
    private array $batchStock = [];

    /** Running credit balance per customer: customerId => int cents */
    private array $customerBalances = [];

    /** Cash (not M-Pesa/credit) received per business day: 'Y-m-d' => int cents */
    private array $dailyCashTotals = [];

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    public function run(): void
    {
        $this->command->info('Clearing existing data...');
        $this->clearData();

        $this->command->info('Seeding categories...');
        $catMap = $this->seedCategories();

        $this->command->info('Seeding products and batches...');
        $products = $this->seedProducts($catMap);

        $this->command->info('Seeding customers...');
        $customers = $this->seedCustomers();

        $owner    = User::where('role', 'owner')->firstOrFail();
        $manager  = User::where('role', 'manager')->firstOrFail();
        $attendant = User::where('role', 'attendant')->firstOrFail();

        $this->command->info('Seeding 6 months of sales (Feb–Jul 2026)...');
        $this->seedSalesHistory($products, $customers, $owner, $manager, $attendant);

        $this->command->info('Seeding customer repayments...');
        $this->seedCustomerPayments($customers, $owner);

        $this->command->info('Seeding cash-up declarations...');
        $this->seedCashUps($attendant, $owner);

        $this->command->info('Syncing customer balance_cents...');
        $this->syncCustomerBalances();

        $this->command->info('DemoSeeder complete.');
    }

    // -------------------------------------------------------------------------
    // Clear
    // -------------------------------------------------------------------------

    private function clearData(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        foreach ([
            'sale_return_lines', 'sale_returns',
            'customer_ledger_entries', 'customer_payments',
            'payments', 'sale_line_batches', 'sale_lines', 'sales',
            'stock_take_lines', 'stock_takes', 'stock_adjustments',
            'batches', 'products', 'categories', 'customers', 'cash_ups',
        ] as $table) {
            DB::table($table)->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name = '{$table}'");
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    // -------------------------------------------------------------------------
    // Categories
    // -------------------------------------------------------------------------

    private function seedCategories(): array
    {
        $names = [
            'Maize Seeds', 'Vegetable Seeds', 'Fertilizers',
            'Herbicides', 'Insecticides & Fungicides',
            'Dairy Feeds', 'Poultry Feeds', 'Pig Feeds',
            'Veterinary Medicines', 'Animal Health',
            'Irrigation & Equipment', 'Soil Amendments', 'General Supplies',
        ];

        $now = now()->toDateTimeString();
        $result = [];
        foreach ($names as $name) {
            $id = DB::table('categories')->insertGetId([
                'name'       => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $result[$name] = $id;
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Products & Batches
    // -------------------------------------------------------------------------

    private function seedProducts(array $catMap): array
    {
        $owner = User::where('role', 'owner')->firstOrFail();
        $now   = now()->toDateTimeString();

        // [category, name, unit, buy_cents, sell_cents, reorder, seasonal]
        // seasonal = sells more during Kenya's long rains planting season (Mar–May)
        $defs = [
            // Maize Seeds (pcs = 2 kg packet)
            ['Maize Seeds',               'Duma 43 F1 (2 kg)',                  'pcs',  65000,  85000, 10, true],
            ['Maize Seeds',               'DK8031 F1 (2 kg)',                   'pcs',  68000,  89000, 10, true],
            ['Maize Seeds',               'H614D OPV (2 kg)',                   'pcs',  30000,  42000, 10, true],
            ['Maize Seeds',               'PH4 OPV (2 kg)',                     'pcs',  28000,  38000, 10, true],
            ['Maize Seeds',               'SC403 F1 (2 kg)',                    'pcs',  72000,  95000,  5, true],

            // Vegetable Seeds (pcs = packet)
            ['Vegetable Seeds',           'Kilele F1 Tomato (10 g)',            'pcs',  32000,  45000, 10, true],
            ['Vegetable Seeds',           'Rambo F1 Sukuma Wiki (50 g)',        'pcs',   7000,  10000, 20, true],
            ['Vegetable Seeds',           'Gloria F1 Cabbage (2500 seeds)',     'pcs',  18000,  25000, 10, true],
            ['Vegetable Seeds',           'Simlaw Red Onion (500 g)',           'pcs',  24000,  33000, 10, true],
            ['Vegetable Seeds',           'Chantenay Carrot (50 g)',            'pcs',   8000,  12000, 15, true],
            ['Vegetable Seeds',           'Roma VF Tomato (50 g)',              'pcs',   6500,   9500, 15, true],

            // Fertilizers (pcs = 50 kg bag)
            ['Fertilizers',               'DAP 18:46:00 (50 kg)',               'pcs', 480000, 520000,  5, true],
            ['Fertilizers',               'CAN 26% (50 kg)',                    'pcs', 320000, 350000,  5, true],
            ['Fertilizers',               'NPK 17:17:17 (50 kg)',               'pcs', 420000, 460000,  5, true],
            ['Fertilizers',               'Mavuno Planting (50 kg)',            'pcs', 450000, 490000,  5, true],
            ['Fertilizers',               'Urea 46% (50 kg)',                   'pcs', 360000, 395000,  5, true],
            ['Fertilizers',               'CAN Bliss Top Dress (50 kg)',        'pcs', 330000, 362000,  5, true],

            // Herbicides (pcs = 1 L bottle)
            ['Herbicides',                'Roundup 360SL (1 L)',                'pcs',  85000, 115000,  5, true],
            ['Herbicides',                'Weedmaster (1 L)',                   'pcs',  78000, 105000,  5, true],
            ['Herbicides',                'Stomp 330EC (1 L)',                  'pcs',  95000, 128000,  5, true],
            ['Herbicides',                'Touchdown IQ (1 L)',                 'pcs', 120000, 155000,  3, true],
            ['Herbicides',                'Bromicide MA (1 L)',                 'pcs',  88000, 118000,  5, true],

            // Insecticides & Fungicides (pcs = bottle/pack)
            ['Insecticides & Fungicides', 'Dursban 480EC (1 L)',               'pcs',  90000, 120000,  5, true],
            ['Insecticides & Fungicides', 'Kingcode Elite 50EC (1 L)',         'pcs', 140000, 185000,  3, true],
            ['Insecticides & Fungicides', 'Tilt 250EC (1 L)',                  'pcs', 165000, 215000,  3, true],
            ['Insecticides & Fungicides', 'Ridomil Gold MZ (1 kg)',            'pcs', 220000, 285000,  3, true],
            ['Insecticides & Fungicides', 'Thunder 145SC (1 L)',               'pcs', 185000, 240000,  3, true],
            ['Insecticides & Fungicides', 'Karate 5EC (1 L)',                  'pcs',  80000, 108000,  5, true],

            // Dairy Feeds (pcs = bag)
            ['Dairy Feeds',               'Unga Dairy Meal 16% (70 kg)',       'pcs', 280000, 320000, 10, false],
            ['Dairy Feeds',               'Enrich Dairy Meal (50 kg)',         'pcs', 200000, 235000, 10, false],
            ['Dairy Feeds',               'Kilimo Dairy Pellets (50 kg)',      'pcs', 215000, 250000, 10, false],
            ['Dairy Feeds',               'Hi-Pro Dairy Concentrates (25 kg)', 'pcs', 185000, 215000,  5, false],

            // Poultry Feeds (pcs = bag)
            ['Poultry Feeds',             'Unga Layers Mash (70 kg)',          'pcs', 310000, 355000, 10, false],
            ['Poultry Feeds',             'Unga Chick Mash (70 kg)',           'pcs', 300000, 345000, 10, false],
            ['Poultry Feeds',             'Jogoo Growers Pellets (50 kg)',     'pcs', 255000, 295000, 10, false],
            ['Poultry Feeds',             'Fugo Layers Concentrate (50 kg)',   'pcs', 275000, 315000,  8, false],
            ['Poultry Feeds',             'Kenchic Broiler Finisher (50 kg)', 'pcs', 290000, 330000,  8, false],

            // Pig Feeds (pcs = bag)
            ['Pig Feeds',                 'Unga Pig Grower (70 kg)',           'pcs', 320000, 365000,  5, false],
            ['Pig Feeds',                 'Pig Starter Meal (50 kg)',          'pcs', 280000, 320000,  5, false],
            ['Pig Feeds',                 'Pigmaster Sow & Weaner (50 kg)',    'pcs', 295000, 335000,  3, false],

            // Veterinary Medicines (pcs)
            ['Veterinary Medicines',      'Terramycin Boluses (24)',           'pcs',  18000,  26000,  5, false],
            ['Veterinary Medicines',      'Penstrep 400 Injection (100 ml)',   'pcs',  32000,  45000,  5, false],
            ['Veterinary Medicines',      'Multivita Livestock (1 L)',         'pcs',  28000,  38000,  5, false],
            ['Veterinary Medicines',      'Deworm Plus Boluses (24)',          'pcs',  22000,  30000,  5, false],
            ['Veterinary Medicines',      'Trypamidium Samorin (50 ml)',       'pcs',  85000, 112000,  3, false],
            ['Veterinary Medicines',      'OTC-10% Oxytetracycline (100 ml)', 'pcs',  24000,  34000,  5, false],

            // Animal Health (pcs)
            ['Animal Health',             'Steladone EC Dip (1 L)',            'pcs',  78000, 105000,  5, false],
            ['Animal Health',             'Biocip Plus Acaricide (1 L)',       'pcs',  85000, 115000,  5, false],
            ['Animal Health',             'Paravax EC Spray (1 L)',            'pcs',  70000,  95000,  5, false],
            ['Animal Health',             'Milking Jelly (500 g)',             'pcs',  12000,  18000, 10, false],

            // Irrigation & Equipment (pcs)
            ['Irrigation & Equipment',    'Drip Tape 16 mm (100 m)',           'pcs', 320000, 420000,  3, false],
            ['Irrigation & Equipment',    'Knapsack Sprayer 16 L',             'pcs', 250000, 330000,  3, false],
            ['Irrigation & Equipment',    'Watering Can 10 L',                 'pcs',  45000,  65000,  5, false],
            ['Irrigation & Equipment',    'Sprinkler Head 3/4"',               'pcs',  35000,  50000,  5, false],

            // Soil Amendments (pcs = bag)
            ['Soil Amendments',           'Lime Agricultural (50 kg)',         'pcs',  38000,  52000, 10, true],
            ['Soil Amendments',           'Humus Plus Organic (25 kg)',        'pcs',  55000,  75000,  5, true],
            ['Soil Amendments',           'Gypsum Agricultural (50 kg)',       'pcs',  48000,  66000,  5, true],
            ['Soil Amendments',           'Sulphate of Iron (10 kg)',          'pcs',  32000,  45000,  5, false],

            // General Supplies (pcs)
            ['General Supplies',          'Jembe Hoe (Heavy Duty)',            'pcs',  38000,  55000, 10, false],
            ['General Supplies',          'Panga (Machete) 18"',               'pcs',  22000,  32000, 10, false],
            ['General Supplies',          'Garden Fork',                       'pcs',  35000,  50000,  5, false],
            ['General Supplies',          'Pruning Shears',                    'pcs',  28000,  40000,  5, false],
            ['General Supplies',          'Poly Bags 6×8" (500 pcs)',          'pcs',  28000,  40000,  5, false],
            ['General Supplies',          'Weighing Scale 50 kg',              'pcs', 120000, 165000,  2, false],
        ];

        $products = [];

        foreach ($defs as $def) {
            [$catName, $name, $unit, $buy, $sell, $reorder, $seasonal] = $def;

            $productId = DB::table('products')->insertGetId([
                'category_id'       => $catMap[$catName],
                'name'              => $name,
                'barcode'           => null,
                'base_unit'         => $unit,
                'buying_price_cents' => $buy,
                'selling_price_cents' => $sell,
                'reorder_level'     => $reorder,
                'is_active'         => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $batches = $this->createBatches($productId, $buy, $owner->id);

            $this->productBatches[$productId] = array_map(
                fn($b) => ['id' => $b['id'], 'cost' => $b['cost']],
                $batches
            );

            foreach ($batches as $b) {
                $this->batchStock[$b['id']] = (float) $b['qty'];
            }

            $products[] = [
                'id'       => $productId,
                'sell'     => $sell,
                'buy'      => $buy,
                'seasonal' => $seasonal,
            ];
        }

        return $products;
    }

    /**
     * Create 2-3 batches for a product received at different points in time.
     * Batch 3 (optional) simulates a mid-season restock in May.
     */
    private function createBatches(int $productId, int $buyCents, int $userId): array
    {
        $now    = now()->toDateTimeString();
        $result = [];

        $batchDefs = [
            // [received_at, qty range, cost variation ±%]
            ['2026-01-15', [80, 150], 3],
            ['2026-03-15', [100, 200], 4],
        ];

        // ~60 % chance of a third batch received late May
        if (rand(0, 9) < 6) {
            $batchDefs[] = ['2026-05-20', [80, 150], 5];
        }

        $counter = 1;
        foreach ($batchDefs as [$receivedAt, [$minQty, $maxQty], $varPct]) {
            $qty  = rand($minQty, $maxQty);
            $vari = rand(-$varPct, $varPct);
            $cost = (int) round($buyCents * (1 + $vari / 100));

            $batchId = DB::table('batches')->insertGetId([
                'product_id'         => $productId,
                'batch_number'       => sprintf('BT-%04d-%s', $productId, str_pad($counter, 2, '0', STR_PAD_LEFT)),
                'expiry_date'        => null,
                'quantity_received'  => $qty,
                'quantity_remaining' => $qty,
                'buying_price_cents' => max(1, $cost),
                'received_at'        => $receivedAt,
                'created_by'         => $userId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $result[] = ['id' => $batchId, 'cost' => max(1, $cost), 'qty' => $qty];
            $counter++;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Customers
    // -------------------------------------------------------------------------

    private function seedCustomers(): array
    {
        $now = now()->toDateTimeString();

        // [name, phone, credit_limit_cents (null = walk-in, no credit)]
        $defs = [
            // Credit customers — farmers who buy on account
            ['John Kamau Njoroge',        '0712345001', 10000000],
            ['Mary Wanjiku Mwangi',       '0723456002',  8000000],
            ['Peter Otieno Ochieng',      '0734567003', 15000000],
            ['Samuel Kiprotich Rono',     '0745678004', 12000000],
            ['David Njeru Kimani',        '0756789005',  5000000],
            ['Patrick Mutua Nzomo',       '0767890006', 10000000],
            ['Charles Kipkemboi Sang',    '0778901007',  7000000],
            ['Alice Auma Okeyo',          '0789012008',  6000000],
            ['Beatrice Mwende Kyalo',     '0710123009',  8000000],
            ['James Mwangi Gitau',        '0721234010',  5000000],
            // Walk-in / cash customers
            ['Grace Nyambura Waweru',     '0732345011',       null],
            ['Joseph Omondi Otieno',      '0743456012',       null],
            ['Catherine Chebet Kiplagat', '0754567013',       null],
            ['Francis Muthomi Njeru',     '0765678014',       null],
            ['Rose Akinyi Odhiambo',      '0776789015',       null],
            ['Stephen Ndungu Kamau',      '0787890016',       null],
            ['Agnes Wanjiru Karanja',     '0798901017',       null],
            ['Michael Ochieng Awino',     '0709012018',       null],
            ['Lucy Cherop Koech',         '0720123019',       null],
            ['Daniel Mwenda Mati',        '0731234020',       null],
            ['Esther Kerubo Omari',       '0742345021',       null],
            ['Philip Kipkurui Bett',      '0753456022',       null],
            ['Margaret Njoki Wainaina',   '0764567023',       null],
            ['Simon Onyango Agoro',       '0775678024',       null],
            ['Naomi Chelagat Toroitich',  '0786789025',       null],
        ];

        $customers = [];
        foreach ($defs as [$name, $phone, $limit]) {
            $id = DB::table('customers')->insertGetId([
                'name'               => $name,
                'phone'              => $phone,
                'credit_limit_cents' => $limit,
                'balance_cents'      => 0,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
            $this->customerBalances[$id] = 0;
            $customers[] = [
                'id'           => $id,
                'name'         => $name,
                'credit_limit' => $limit,
            ];
        }

        return $customers;
    }

    // -------------------------------------------------------------------------
    // Sales History
    // -------------------------------------------------------------------------

    private function seedSalesHistory(
        array $products,
        array $customers,
        User  $owner,
        User  $manager,
        User  $attendant
    ): void {
        $creditCustomers = array_values(
            array_filter($customers, fn($c) => $c['credit_limit'] !== null)
        );

        $userWeights = [
            $owner->id    => 10,
            $manager->id  => 30,
            $attendant->id => 60,
        ];

        $date = Carbon::create(2026, 2, 1);
        $end  = Carbon::create(2026, 7, 31);

        $dailyCounters = []; // 'Y-m-d' => int per-day sale counter

        while ($date->lte($end)) {
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                $date->addDay();
                continue;
            }

            $dateStr = $date->toDateString();
            $month   = $date->month;

            $seasonWeight = match ($month) {
                2       => 0.5,
                3       => 1.5,
                4       => 2.0,
                5       => 1.5,
                6       => 0.7,
                default => 0.6,
            };

            $targetSales = (int) round(6 * $seasonWeight) + rand(0, 2);
            $targetSales = max(2, $targetSales);

            $dailyCounters[$dateStr] = 0;
            $dayCashTotal = 0;

            for ($s = 0; $s < $targetSales; $s++) {
                $dailyCounters[$dateStr]++;
                $saleNumber = 'S-' . $date->format('Ymd') . '-'
                    . str_pad($dailyCounters[$dateStr], 4, '0', STR_PAD_LEFT);

                $completedAt = $date->copy()->setTime(rand(8, 17), rand(0, 59), rand(0, 59));
                $userId      = $this->weightedPickKey($userWeights);

                // Payment method
                $method = $this->weightedPickKey(['cash' => 60, 'mpesa' => 25, 'credit' => 15]);

                $customerId = null;
                if ($method === 'credit' && !empty($creditCustomers)) {
                    $candidate = $creditCustomers[array_rand($creditCustomers)];
                    $balance   = $this->customerBalances[$candidate['id']] ?? 0;
                    if ($balance < $candidate['credit_limit']) {
                        $customerId = $candidate['id'];
                    } else {
                        $method = 'cash'; // credit limit hit — fall back
                    }
                } elseif ($method === 'credit') {
                    $method = 'cash';
                }

                // Pick 1–4 products
                $lineCount = rand(1, min(4, count($products)));
                $picked    = (array) array_rand($products, $lineCount);

                $lines    = [];
                $subtotal = 0;

                foreach ($picked as $idx) {
                    $product = $products[$idx];

                    // Reduce seasonal products in off-season
                    if ($product['seasonal'] && $seasonWeight < 1.0 && rand(0, 9) < 4) {
                        continue;
                    }

                    $qty         = rand(1, 3);
                    $allocations = $this->allocateBatches($product['id'], $qty);
                    if (empty($allocations)) continue;

                    $lineTotal = $product['sell'] * $qty;
                    $subtotal += $lineTotal;

                    $lines[] = [
                        'product_id'       => $product['id'],
                        'quantity'         => $qty,
                        'unit_price_cents' => $product['sell'],
                        'line_total_cents' => $lineTotal,
                        'allocations'      => $allocations,
                    ];
                }

                if (empty($lines)) {
                    $dailyCounters[$dateStr]--;
                    continue;
                }

                $total = $subtotal;

                $saleId = DB::table('sales')->insertGetId([
                    'sale_number'  => $saleNumber,
                    'customer_id'  => $customerId,
                    'user_id'      => $userId,
                    'status'       => 'completed',
                    'subtotal_cents' => $subtotal,
                    'discount_cents' => 0,
                    'total_cents'  => $total,
                    'notes'        => null,
                    'held_at'      => null,
                    'completed_at' => $completedAt->toDateTimeString(),
                    'voided_at'    => null,
                    'void_reason'  => null,
                    'approved_by'  => null,
                    'created_at'   => $completedAt->toDateTimeString(),
                    'updated_at'   => $completedAt->toDateTimeString(),
                ]);

                foreach ($lines as $line) {
                    $saleLineId = DB::table('sale_lines')->insertGetId([
                        'sale_id'          => $saleId,
                        'product_id'       => $line['product_id'],
                        'quantity'         => $line['quantity'],
                        'unit_price_cents' => $line['unit_price_cents'],
                        'discount_cents'   => 0,
                        'line_total_cents' => $line['line_total_cents'],
                        'notes'            => null,
                        'created_at'       => $completedAt->toDateTimeString(),
                        'updated_at'       => $completedAt->toDateTimeString(),
                    ]);

                    foreach ($line['allocations'] as $alloc) {
                        DB::table('sale_line_batches')->insert([
                            'sale_line_id'   => $saleLineId,
                            'batch_id'       => $alloc['batch_id'],
                            'quantity'       => $alloc['quantity'],
                            'unit_cost_cents' => $alloc['unit_cost_cents'],
                            'created_at'     => $completedAt->toDateTimeString(),
                            'updated_at'     => $completedAt->toDateTimeString(),
                        ]);
                    }
                }

                $mpesaCode = $method === 'mpesa'
                    ? 'QA' . strtoupper(substr(md5((string) $saleId . $dateStr), 0, 8))
                    : null;

                DB::table('payments')->insert([
                    'sale_id'     => $saleId,
                    'method'      => $method,
                    'amount_cents' => $total,
                    'mpesa_code'  => $mpesaCode,
                    'customer_id' => $method === 'credit' ? $customerId : null,
                    'created_at'  => $completedAt->toDateTimeString(),
                    'updated_at'  => $completedAt->toDateTimeString(),
                ]);

                if ($method === 'credit' && $customerId) {
                    $this->customerBalances[$customerId] += $total;
                    DB::table('customer_ledger_entries')->insert([
                        'customer_id'        => $customerId,
                        'type'               => 'charge',
                        'amount_cents'       => $total,
                        'sale_id'            => $saleId,
                        'customer_payment_id' => null,
                        'running_balance_cents' => $this->customerBalances[$customerId],
                        'notes'              => null,
                        'created_by'         => $userId,
                        'created_at'         => $completedAt->toDateTimeString(),
                    ]);
                }

                if ($method === 'cash') {
                    $dayCashTotal += $total;
                }
            }

            $this->dailyCashTotals[$dateStr] = $dayCashTotal;
            $date->addDay();
        }

        // Bulk-flush batch quantities back to the database
        foreach ($this->batchStock as $batchId => $remaining) {
            DB::table('batches')
                ->where('id', $batchId)
                ->update(['quantity_remaining' => $remaining]);
        }
    }

    // -------------------------------------------------------------------------
    // Customer Repayments
    // -------------------------------------------------------------------------

    private function seedCustomerPayments(array $customers, User $owner): void
    {
        $creditCustomers = array_filter($customers, fn($c) => $c['credit_limit'] !== null);

        $startTs = Carbon::create(2026, 2, 15)->timestamp;
        $endTs   = Carbon::create(2026, 7, 20)->timestamp;

        foreach ($creditCustomers as $customer) {
            $totalCharged = $this->customerBalances[$customer['id']] ?? 0;
            if ($totalCharged <= 0) continue;

            // Generate 3–5 partial payments spread across the period
            $count = rand(3, 5);
            $dates = [];
            for ($i = 0; $i < $count; $i++) {
                $dates[] = rand($startTs, $endTs);
            }
            sort($dates); // chronological order

            foreach ($dates as $ts) {
                $outstanding = $this->customerBalances[$customer['id']];
                if ($outstanding <= 0) break;

                // Pay 30–60 % of outstanding balance
                $fraction = rand(30, 60) / 100;
                $amount   = (int) round($outstanding * $fraction);
                if ($amount < 10000) continue; // skip < KES 100

                $method    = $this->weightedPickKey(['cash' => 30, 'mpesa' => 70]);
                $mpesaCode = $method === 'mpesa'
                    ? 'CX' . strtoupper(substr(md5($customer['id'] . $ts), 0, 8))
                    : null;

                $payDate = Carbon::createFromTimestamp($ts)->setTime(rand(9, 16), rand(0, 59));

                $paymentId = DB::table('customer_payments')->insertGetId([
                    'customer_id'  => $customer['id'],
                    'amount_cents' => $amount,
                    'method'       => $method,
                    'mpesa_code'   => $mpesaCode,
                    'received_by'  => $owner->id,
                    'notes'        => null,
                    'created_at'   => $payDate->toDateTimeString(),
                    'updated_at'   => $payDate->toDateTimeString(),
                ]);

                $this->customerBalances[$customer['id']] -= $amount;

                DB::table('customer_ledger_entries')->insert([
                    'customer_id'        => $customer['id'],
                    'type'               => 'payment',
                    'amount_cents'       => $amount,
                    'sale_id'            => null,
                    'customer_payment_id' => $paymentId,
                    'running_balance_cents' => $this->customerBalances[$customer['id']],
                    'notes'              => null,
                    'created_by'         => $owner->id,
                    'created_at'         => $payDate->toDateTimeString(),
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Cash-ups
    // -------------------------------------------------------------------------

    private function seedCashUps(User $attendant, User $owner): void
    {
        foreach ($this->dailyCashTotals as $dateStr => $expectedCents) {
            // Declared amount ±3 % variance
            $varPct   = rand(-3, 3);
            $variance = (int) round($expectedCents * $varPct / 100);
            $declared = max(0, $expectedCents + $variance);

            // 80 % of days the attendant declares; owner covers the rest
            $user = rand(0, 9) < 8 ? $attendant : $owner;

            DB::table('cash_ups')->insert([
                'business_date'       => $dateStr,
                'user_id'             => $user->id,
                'expected_cash_cents' => $expectedCents,
                'declared_cash_cents' => $declared,
                'variance_cents'      => $declared - $expectedCents,
                'notes'               => null,
                'approved_by'         => null,
                'created_at'          => Carbon::parse($dateStr)
                    ->setTime(18, rand(0, 59))
                    ->toDateTimeString(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Sync customer balance_cents
    // -------------------------------------------------------------------------

    private function syncCustomerBalances(): void
    {
        foreach ($this->customerBalances as $customerId => $balance) {
            DB::table('customers')
                ->where('id', $customerId)
                ->update(['balance_cents' => $balance]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * FIFO (all expiry_date = null) allocation from in-memory stock.
     * Returns [] if there is not enough stock across all batches.
     */
    private function allocateBatches(int $productId, float $qty): array
    {
        $batches     = $this->productBatches[$productId] ?? [];
        $allocations = [];
        $remaining   = $qty;

        foreach ($batches as $batch) {
            if ($remaining <= 0.0) break;
            $available = $this->batchStock[$batch['id']] ?? 0.0;
            if ($available <= 0.0) continue;

            $take = min($remaining, $available);
            $this->batchStock[$batch['id']] -= $take;
            $remaining -= $take;

            $allocations[] = [
                'batch_id'       => $batch['id'],
                'quantity'       => $take,
                'unit_cost_cents' => $batch['cost'],
            ];
        }

        if ($remaining > 0.001) {
            // Not enough stock — roll back in-memory deductions
            foreach ($allocations as $alloc) {
                $this->batchStock[$alloc['batch_id']] += $alloc['quantity'];
            }
            return [];
        }

        return $allocations;
    }

    /**
     * Weighted random pick: returns the key whose weight wins the random draw.
     * Weights are positive integers; keys can be int|string.
     */
    private function weightedPickKey(array $weights): int|string
    {
        $total      = array_sum($weights);
        $rand       = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $key;
            }
        }

        return array_key_last($weights);
    }
}
