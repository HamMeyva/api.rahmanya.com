<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Common\Currency;
use Illuminate\Support\Facades\Schema;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */use WithoutModelEvents;
    public function run(): void
    {
        if (Schema::hasTable('currencies') && Currency::query()->doesntExist()) {
            $data = [
                ['code' => 'TRY', 'name' => 'Türk Lirası', 'symbol' => '₺'],
                ['code' => 'USD', 'name' => 'Dolar', 'symbol' => '$'],
                ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ];

            foreach ($data as $currency) {
                if (Currency::query()->where('code', $currency['code'])->doesntExist()) {
                    Currency::query()->create([
                        'code' => $currency['code'],
                        'name' => $currency['name'],
                        'symbol' => $currency['symbol'],
                    ]);
                }
            }
        }
    }
}
