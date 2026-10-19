<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first Super Admin. Run once after `php artisan migrate`:
 *
 *     php artisan db:seed --force
 *
 * It asks for the name, email and password in the terminal (nothing is hard-coded).
 * Running it again with an email that already exists changes nothing.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $name  = trim((string) $this->command->ask('Super Admin name', 'Admin'));
        $email = strtolower(trim((string) $this->command->ask('Super Admin email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command->error('That is not a valid email. Nothing was created.');
            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command->warn($email . ' already exists. Nothing was changed.');
            return;
        }

        $password = (string) $this->command->secret('Password (min 8 characters)');
        if (mb_strlen($password) < 8) {
            $this->command->error('Password is too short. Nothing was created.');
            return;
        }

        User::create([
            'name'              => $name !== '' ? $name : 'Admin',
            'email'             => $email,
            'email_verified_at' => now(),
            'password'          => Hash::make($password),
            'type'              => 'Super Admin',
        ]);

        $this->command->info('Super Admin ' . $email . ' created. You can log in now.');
    }
}
