<?php

namespace Database\Seeders;

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
        // Always run permissions seeder
        $this->call([
            PermissionsAndRolesSeeder::class,
        ]);

        // Check if this is initial setup (no users exist)
        if (\App\Models\User::count() === 0) {
            $this->command->info('Initial setup detected. Running all seeders...');
            $this->call([
                AdminUserSeeder::class,
                DemoDataSeeder::class,
                CustomerDemoSeeder::class,
                ReturnRequestDemoSeeder::class,
            ]);
        } else {
            $this->command->info('Existing data found. Skipping demo data seeders.');
        }
        
        $this->command->info('Database seeding completed successfully!');
    }
}
