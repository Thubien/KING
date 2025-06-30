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
        // Run seeders in order
        $this->call([
            PermissionsAndRolesSeeder::class,
            AdminUserSeeder::class,
            DemoDataSeeder::class,
            CustomerDemoSeeder::class,
            ReturnRequestDemoSeeder::class,
        ]);
        
        $this->command->info('Database seeding completed successfully!');
    }
}
