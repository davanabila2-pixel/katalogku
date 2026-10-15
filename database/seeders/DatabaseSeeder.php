<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL', 'superadmin@example.com');

        if (! User::where('email', $email)->exists()) {
            User::create([
                'name' => env('SUPERADMIN_NAME', 'Superadmin'),
                'email' => $email,
                'password' => env('SUPERADMIN_PASSWORD', 'password'),
                'role' => 'superadmin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->command->info("Superadmin dibuat: {$email}");
        } else {
            $this->command->info('Superadmin sudah ada, dilewati.');
        }
    }
}
