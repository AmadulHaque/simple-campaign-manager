<?php

namespace Database\Seeders;

use App\Models\Contact;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10000)->create();
        Contact::factory()->count(20000)->create();

        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'              => 'Al Karim',
                'password'          => '12345678',
                'email_verified_at' => now(),
            ]
        );
    }
}
