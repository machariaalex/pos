<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'owner@agrovet.test')->firstOrFail();

        $customers = [
            [
                'name' => 'John Mwangi',
                'phone' => '0711223344',
                'credit_limit_cents' => 2000000, // KES 20,000
                'history' => [
                    // [type, amount_kes, days_ago, note]
                    ['charge', 8000, 75, 'Opening balance — credit sale before system rollout'],
                    ['charge', 4500, 40, 'Opening balance — credit sale before system rollout'],
                    ['payment', 6000, 20, 'Cash payment after maize harvest'],
                ],
            ],
            [
                'name' => 'Mary Wambui',
                'phone' => '0722334455',
                'credit_limit_cents' => 1000000, // KES 10,000
                'history' => [
                    ['charge', 6000, 50, 'Opening balance — credit sale before system rollout'],
                    ['payment', 6000, 10, 'M-Pesa payment'],
                ],
            ],
            [
                'name' => 'Samuel Kiptoo',
                'phone' => '0733445566',
                'credit_limit_cents' => 3000000, // KES 30,000
                'history' => [
                    ['charge', 15000, 95, 'Opening balance — credit sale before system rollout'],
                    ['charge', 9000, 65, 'Opening balance — credit sale before system rollout'],
                    ['charge', 5000, 20, 'Opening balance — credit sale before system rollout'],
                ],
            ],
            [
                'name' => 'Ann Chebet',
                'phone' => '0700112233',
                'credit_limit_cents' => null, // no limit set
                'history' => [
                    ['charge', 3000, 15, 'Opening balance — credit sale before system rollout'],
                    ['payment', 1000, 5, 'Part payment, cash'],
                ],
            ],
            [
                'name' => 'David Otieno',
                'phone' => '0744556677',
                'credit_limit_cents' => 500000, // KES 5,000
                'history' => [], // walk-in / cash customer with no credit history yet
            ],
        ];

        foreach ($customers as $c) {
            $customer = Customer::create([
                'name' => $c['name'],
                'phone' => $c['phone'],
                'credit_limit_cents' => $c['credit_limit_cents'],
                'balance_cents' => 0,
            ]);

            $balance = 0;

            foreach ($c['history'] as [$type, $amountKes, $daysAgo, $note]) {
                $amountCents = $amountKes * 100;
                $createdAt = Carbon::now()->subDays($daysAgo);

                if ($type === 'charge') {
                    $balance += $amountCents;
                    CustomerLedgerEntry::create([
                        'customer_id' => $customer->id,
                        'type' => CustomerLedgerEntry::TYPE_CHARGE,
                        'amount_cents' => $amountCents,
                        'sale_id' => null,
                        'customer_payment_id' => null,
                        'running_balance_cents' => $balance,
                        'notes' => $note,
                        'created_by' => $owner->id,
                        'created_at' => $createdAt,
                    ]);
                } else {
                    $payment = CustomerPayment::create([
                        'customer_id' => $customer->id,
                        'amount_cents' => $amountCents,
                        'method' => 'cash',
                        'received_by' => $owner->id,
                        'notes' => $note,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $balance -= $amountCents;
                    CustomerLedgerEntry::create([
                        'customer_id' => $customer->id,
                        'type' => CustomerLedgerEntry::TYPE_PAYMENT,
                        'amount_cents' => $amountCents,
                        'sale_id' => null,
                        'customer_payment_id' => $payment->id,
                        'running_balance_cents' => $balance,
                        'notes' => $note,
                        'created_by' => $owner->id,
                        'created_at' => $createdAt,
                    ]);
                }
            }

            $customer->update(['balance_cents' => $balance]);
        }
    }
}
