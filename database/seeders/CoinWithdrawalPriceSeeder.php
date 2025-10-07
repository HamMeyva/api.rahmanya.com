<?php

namespace Database\Seeders;

use App\Models\Coin\CoinWithdrawalPrice;
use App\Models\Common\Currency;
use Illuminate\Database\Seeder;

class CoinWithdrawalPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPrice = 1.00;

        Currency::all()->each(function ($currency) use ($defaultPrice) {
            $exists = CoinWithdrawalPrice::query()->where('currency_id', $currency->id)->exists();
            if (!$exists) {
                CoinWithdrawalPrice::query()->create([
                    'currency_id' => $currency->id,
                    'coin_unit_price' => $defaultPrice,
                ]);
            }
        });
    }
}
