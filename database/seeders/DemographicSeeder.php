<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Demographic\Gender;
use App\Models\Demographic\AgeRange;
use App\Models\Demographic\Language;
use App\Models\Demographic\Os;
use App\Models\Demographic\Placement;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DemographicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Schema::hasTable('placements') && Placement::query()->doesntExist()) {
            foreach (Placement::$placements as $name) {
                Placement::create([
                    'name' => $name,
                ]);
            }
        }

        if (Schema::hasTable('genders') && Gender::query()->doesntExist()) {
            foreach (Gender::$genders as $name) {
                Gender::create([
                    'name' => $name,
                ]);
            }
        }

        if (Schema::hasTable('age_ranges') && AgeRange::query()->doesntExist()) {
            foreach (AgeRange::$ageRanges as $name) {
                AgeRange::create([
                    'name' => $name,
                ]);
            }
        }

        if (Schema::hasTable('languages') && Language::query()->doesntExist()) {
            foreach (Language::$languages as $name) {
                Language::create([
                    'name' => $name,
                ]);
            }
        }

        if (Schema::hasTable('oses') && Os::query()->doesntExist()) {
            foreach (Os::$oses as $name) {
                Os::create([
                    'name' => $name,
                ]);
            }
        }
    }
}
