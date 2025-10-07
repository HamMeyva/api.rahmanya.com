<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\MusicSeeder;
use Database\Seeders\AppSettingSeeder;
use Database\Seeders\BannedWordSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AppSettingSeeder::class,
            RoleSeeder::class,
            CurrencySeeder::class,
            TeamSeeder::class,
            DemographicSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            CountrySeeder::class,
            CoinWithdrawalPriceSeeder::class,
            ReportProblemSeeder::class,
            PunishmentCategorySeeder::class,
            LiveStreamCategorySeeder::class,
            MusicSeeder::class,
            BannedWordSeeder::class,
        ]);
    }
}
