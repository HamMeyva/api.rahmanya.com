<?php

namespace Database\Seeders;

use App\Models\Demographic\Gender;
use App\Models\Demographic\Language;
use App\Models\Relations\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(User::doesntExist()) {
            User::query()->create([
                'id' => '9ef352fa-06dc-4cfb-b3b9-f343c7df5726',
                'collection_uuid' => '1d3e5dfd-122a-4b77-aa07-970763d557d0',
                'name' => 'Batuhan',
                'surname' => 'Üstün',
                'nickname' => 'batustun',
                'email' => 'batustun@gmail.com',
                'birthday' => Carbon::parse('14-03-1983')->format('Y-m-d'),
                'primary_team_id' => 8,
                'phone' => '905057408699',
                'bio' => 'Fenerbahçe fanatiği',
                'slogan' => 'Yaşasın fener!',
                'gender_id' => Gender::MALE,
                'fcm_token' => 1,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'preferred_language_id' => Language::TR,
                'password' => 'password',
            ]);
            User::query()->create([
                'id' => '9ef352fa-9b40-4fa6-8733-cdcc92a5cdbc',
                'collection_uuid' => '48224119-cc94-4a84-9db7-60105b67d3ba',
                'name' => 'Mehmet',
                'surname' => 'Yalovalı',
                'nickname' => 'mehmet',
                'email' => 'demo@demo.com',
                'birthday' => Carbon::parse('14-03-1983')->format('Y-m-d'),
                'primary_team_id' => 9,
                'phone' => '905057408699',
                'bio' => 'Galatasaray fanatiği',
                'slogan' => 'yaşasın cimbombom',
                'gender_id' => Gender::MALE,
                'fcm_token' => 1,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'preferred_language_id' => Language::TR,
                'password' => 'password',
            ]);
            User::query()->create([
                'id' => '9f03a930-970d-41a7-967d-17df2daecd23',
                'collection_uuid' => '2dad4606-5a55-46ec-be0f-f32cef13b708',
                'name' => 'Bilal',
                'surname' => 'Yıldırım',
                'nickname' => 'bashkan',
                'email' => 'bilal.yildirim@gmail.com',
                'birthday' => Carbon::parse('14-03-1983')->format('Y-m-d'),
                'primary_team_id' => 4,
                'phone' => '905057408698',
                'bio' => 'Beşiktaş fanatiği',
                'slogan' => 'yaşasın beşiktaş!',
                'gender_id' => Gender::MALE,
                'fcm_token' => 1,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'preferred_language_id' => Language::TR,
                'password' => 'password',
            ]);
            User::query()->create([
                'id' => '9f03fc41-83ad-4025-abae-501a5f37d19d',
                'collection_uuid' => '74d4d414-f710-492b-878c-8d2dccf25858',
                'name' => 'Kartal',
                'surname' => 'Doruk',
                'nickname' => 'kartaldoruk',
                'email' => 'kartaldoruk@gmail.com',
                'birthday' => Carbon::parse('14-03-1983')->format('Y-m-d'),
                'primary_team_id' => 4,
                'phone' => '905057408697',
                'bio' => 'Beşiktaş fanatiği',
                'slogan' => 'yaşasın beşiktaş!',
                'gender_id' => Gender::MALE,
                'fcm_token' => 1,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'preferred_language_id' => Language::TR,
                'password' => 'password',
            ]);
        }
    }
}
