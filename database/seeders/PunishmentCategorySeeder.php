<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PunishmentCategory;
use Illuminate\Support\Facades\Schema;

class PunishmentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Schema::hasTable('punishment_categories') && PunishmentCategory::query()->doesntExist()) {
            $categories = [
                'Video' => [
                    'Futbol ve Spor Dışı İçerik',
                    'Tehdit ve Hakaret',
                    'Kurumsal Sınırları Aşan Cinsellik',
                    'Şiddet',
                    'Terör',
                    'Telif Hakları',
                    'Siyasi Propaganda',
                    'Genel İbare',
                ],
                'Mesajlaşma ve Yorum' => [
                    'Kusurları',
                ],
                'Canlı Yayın' => [
                    'Tehdit ve Hakaret',
                    'Kurumsal Sınırları Aşan Cinsellik',
                    'Şiddet',
                    'Terör',
                    'Telif Hakları',
                    'Siyasi Propaganda',
                    'Genel İbare',
                ],
                'Diğer' => [
                    'İhraç',
                ]
            ];

            foreach ($categories as $main => $subcategories) {
                $parent = PunishmentCategory::create([
                    'name' => $main,
                    'parent_id' => null
                ]);

                foreach ($subcategories as $child) {
                    PunishmentCategory::create([
                        'name' => $child,
                        'parent_id' => $parent->id
                    ]);
                }
            }
        }
    }
}