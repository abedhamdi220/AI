<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'id' => (string) Str::uuid(),
                'username' => 'admin5',
                'password' => Hash::make('Mohamad271'),
                'is_admin' => true,
                'designs_limit' => 99999,
                'designs_used' => 0,
                'is_unlimited' => true,
                'email_verified' => true, 
                'created_at' => now(),
            ]
        );
    }
}
