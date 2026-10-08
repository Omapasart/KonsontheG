<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrNew([
            'email' => env('ADMIN_EMAIL', 'admin@konsonthego.test'),
        ]);

        $user->name = env('ADMIN_NAME', 'KONSONTHEGO Admin');
        $user->is_admin = true;

        if (! $user->exists) {
            $user->password = env('ADMIN_PASSWORD', 'password');
        }

        $user->save();
    }
}
