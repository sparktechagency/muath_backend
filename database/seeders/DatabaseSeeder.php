<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        User::create([
            'full_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'ADMIN',
            'status' => 'Active',
        ]);

        User::create([
            'full_name' => 'User one',
            'email' => 'user.one@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'role' => 'USER',
            'status' => 'Active',
        ]);

        Banner::create([
            'banner' => null
        ]);
    }
}
