<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Schema::hasTable('admins') && Admin::query()->doesntExist()) {
            Admin::query()->create([
                'email' => 'info@kodfixer.com',
                'first_name' => 'Batuhan',
                'last_name' => 'Ustun',
                'password' => 'Batuhan.123',
            ]);
        }
    }
}
