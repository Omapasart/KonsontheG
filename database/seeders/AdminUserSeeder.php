<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('admins.accounts', []) as $account) {
            $email = strtolower(trim((string) ($account['email'] ?? '')));
            $name = trim((string) ($account['name'] ?? ''));
            $password = (string) ($account['password'] ?? '');

            if ($email === '' || $password === '' || $name === '') {
                continue;
            }

            $user = User::query()->where('email', $email)->first();

            if ($user) {
                if (! $user->is_admin) {
                    $user->forceFill(['is_admin' => true])->save();
                }

                continue;
            }

            User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
