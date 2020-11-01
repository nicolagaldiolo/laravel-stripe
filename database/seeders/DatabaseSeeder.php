<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->create([
            'name' => 'Nicola',
            'email' => 'galdiolo.nicola@gmail.com',
            'password' => Hash::make('12345678')
        ]);

        \App\Models\Plan::factory()->create([
            'stripe_id' => 'price_1HeZzjC5g2b0G4Z4q3Fnvu4M',
            'name' => 'Monthly',
            'price' => 500,
        ]);

        \App\Models\Plan::factory()->create([
            'stripe_id' => 'price_1HfjgFC5g2b0G4Z4vOl5njLi',
            'name' => 'Yearly',
            'price' => 6000,
        ]);
    }
}
