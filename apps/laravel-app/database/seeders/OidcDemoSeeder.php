<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class OidcDemoSeeder extends Seeder
{
    /**
     * Seed the application's database with a demo user.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => 'password',
            ]
        );
    }
}
