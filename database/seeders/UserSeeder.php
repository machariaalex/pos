<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@agrovet.test'],
            [
                'name' => 'Wanjiru Kamau',
                'phone' => '0722100001',
                'role' => User::ROLE_OWNER,
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $owner->setPin('1234');

        $manager = User::firstOrCreate(
            ['email' => 'manager@agrovet.test'],
            [
                'name' => 'Peter Otieno',
                'phone' => '0722100002',
                'role' => User::ROLE_MANAGER,
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $manager->setPin('5678');

        User::firstOrCreate(
            ['email' => 'attendant@agrovet.test'],
            [
                'name' => 'Grace Njeri',
                'phone' => '0722100003',
                'role' => User::ROLE_ATTENDANT,
                'password' => 'password',
                'is_active' => true,
            ]
        );
    }
}
