<?php

namespace Database\Seeders;

use App\Models\Administrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $username = config('services.ecocycle.admin_username');
        $password = config('services.ecocycle.admin_password');

        if (! $username || ! $password) {
            return;
        }

        Administrator::updateOrCreate(
            ['email' => 'ssyahmidhabit@gmail.com'],
            ['name' => $username, 'password' => Hash::make($password)],
        );
    }
}
