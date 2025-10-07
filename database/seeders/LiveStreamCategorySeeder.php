<?php

namespace Database\Seeders;

use App\Models\LiveStreamCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LiveStreamCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fake kategorileri gerçek veritabanına ekleyelim
        $categories = [
            [
                'name' => 'Sohbet',
                'slug' => 'sohbet',
                'description' => 'Takipçilerinle sohbet et ve etkileşimde bulun',
                'icon' => 'assets/icons/chat_icon.png',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Maç Yorumu',
                'slug' => 'match_commentary',
                'description' => 'Canlı maç yorumları ve analizler yap',
                'icon' => 'assets/icons/match_commentary_icon.png',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Yarışma',
                'slug' => 'competition',
                'description' => 'Yarışmalar düzenle ve ödüller dağıt',
                'icon' => 'assets/icons/competition_icon.png',
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'name' => 'Basketbol',
                'slug' => 'basketball',
                'description' => 'Basketbol ile ilgili içerikler paylaş',
                'icon' => 'assets/icons/basketball_icon.png',
                'is_active' => true,
                'display_order' => 4,
            ],
        ];
        
        foreach ($categories as $category) {
            // Convert boolean to PostgreSQL boolean using DB::raw
            $data = $category;
            $data['is_active'] = DB::raw($category['is_active'] ? 'true' : 'false');
            
            LiveStreamCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $data
            );
        }
    }
}
