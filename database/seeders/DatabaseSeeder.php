<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@mediavault.test'],
            [
                'name' => 'Administrator',
                'password' => 'admin12345',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ],
        );

        User::firstOrCreate(
            ['email' => 'user@mediavault.test'],
            [
                'name' => 'User Demo',
                'password' => 'user12345',
                'role' => User::ROLE_USER,
                'is_active' => true,
            ],
        );
    }
}
