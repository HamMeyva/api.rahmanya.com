<?php

namespace Database\Seeders;

use App\Models\Music\Music;
use Illuminate\Support\Str;
use App\Models\Music\Artist;
use Illuminate\Database\Seeder;
use App\Models\Music\MusicCategory;
use Illuminate\Support\Facades\Schema;

class MusicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Schema::hasTable('musics') && Music::query()->doesntExist() && MusicCategory::query()->doesntExist() && Artist::query()->doesntExist()) {
            $categories = [
                'Pop',
                'Rap',
                'Rock',
                'Elektronik',
                'Jazz'
            ];

            $categoryIds = [];

            foreach ($categories as $category) {
                $cat = MusicCategory::create([
                    'name' => $category
                ]);
                $categoryIds[$category] = $cat->id;
            }

            // Sanatçılar
            $artists = [
                'Tarkan',
                'Ezhel',
                'Sezen Aksu',
                'Ben Fero',
                'Zeynep Bastık'
            ];

            $artistIds = [];

            foreach ($artists as $artist) {
                $a = Artist::create([
                    'name' => $artist
                ]);
                $artistIds[$artist] = $a->id;
            }

            // Müzikler
            $musics = [
                ['Yolla', 'Tarkan', 'Pop'],
                ['Felaket', 'Ezhel', 'Rap'],
                ['Gülümse', 'Sezen Aksu', 'Pop'],
                ['Demet Akalın', 'Ben Fero', 'Rap'],
                ['Uslanmıyor Bu', 'Zeynep Bastık', 'Pop'],
                ['Resimdeki Gözyaşları', 'Sezen Aksu', 'Rock'],
                ['Bir Yerde', 'Ezhel', 'Elektronik'],
                ['Dudu', 'Tarkan', 'Pop'],
                ['Bu Da Geçer', 'Zeynep Bastık', 'Jazz'],
            ];

            foreach ($musics as [$title, $artistName, $categoryName]) {
                $slug = Str::slug($artistName . '-' . $title);

                Music::create([
                    'title' => $title,
                    'artist_id' => $artistIds[$artistName],
                    'music_category_id' => $categoryIds[$categoryName],
                ]);
            }
        }
    }
}
