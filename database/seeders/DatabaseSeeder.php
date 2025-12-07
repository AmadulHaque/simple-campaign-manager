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
        Contact::factory()->count(100)->create();

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
