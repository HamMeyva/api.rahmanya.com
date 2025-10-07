<?php

namespace Database\Seeders;

use App\Models\BannedWord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BannedWordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //BannedWord::truncate();
        if (Schema::hasTable('banned_words') && BannedWord::query()->doesntExist()) {
            $bannedWords = [
                "salak",
                "mal",
                "aptal",
                "gerizekalı",
                "dangalak",
                "şerefsiz",
                //...
            ];


            foreach ($bannedWords as $bannedWord) {
                BannedWord::createOrFirst([
                    'word' => $bannedWord
                ]);
            }
        }
    }
}
