<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => AppSetting::TYPE_BOOLEAN,
            ],
            [
                'key' => 'android_version',
                'value' => '1.0.0',
                'type' => AppSetting::TYPE_STRING,
            ],
            [
                'key' => 'ios_version',
                'value' => '1.0.0',
                'type' => AppSetting::TYPE_STRING,
            ],
            [
                'key' => 'contact_phone',
                'value' => '+90 555 000 0000',
                'type' => AppSetting::TYPE_STRING,
            ],
            [
                'key' => 'contact_email',
                'value' => 'info@ornekfirma.com',
                'type' => AppSetting::TYPE_STRING,
            ],
            [
                'key' => 'contact_address',
                'value' => 'İstanbul, Türkiye',
                'type' => AppSetting::TYPE_TEXT_EDITOR,
            ],
            [
                'key' => 'contact_company_name',
                'value' => 'Örnek Firma A.Ş.',
                'type' => AppSetting::TYPE_STRING,
            ],
            [
                'key' => 'contact_tax_office',
                'value' => 'Kadıköy',
                'type' => AppSetting::TYPE_STRING,
            ],
            [
                'key' => 'contact_mersis_no',
                'value' => '1234567890123456',
                'type' => AppSetting::TYPE_STRING,
            ],
            [
                'key' => 'ad_interval',
                'value' => '10',
                'type' => AppSetting::TYPE_INTEGER,
            ],
            [
                'key' => 'terms_conditions',
                'value' => 'Buraya Şartlar ve Koşullar metni gelecek.',
                'type' => AppSetting::TYPE_TEXT_EDITOR,
            ],
            [
                'key' => 'purchase_contract',
                'value' => 'Buraya Satın Alım Sözleşmesi metni gelecek.',
                'type' => AppSetting::TYPE_TEXT_EDITOR,
            ],
            [
                'key' => 'user_agreement',
                'value' => 'Buraya Kullanıcı Sözleşmesi metni gelecek.',
                'type' => AppSetting::TYPE_TEXT_EDITOR,
            ],
            [
                'key' => 'privacy_policy',
                'value' => 'Buraya Gizlilik Politikası metni gelecek.',
                'type' => AppSetting::TYPE_TEXT_EDITOR,
            ],
            [
                'key' => 'about_us',
                'value' => 'Buraya Hakkımızda metni gelecek.',
                'type' => AppSetting::TYPE_TEXT_EDITOR,
            ],
        ];

        foreach ($settings as $setting) {
            $exists = AppSetting::query()->where('key', $setting['key'])->exists();
            if ($exists) continue;


            AppSetting::create([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'type' => $setting['type'],
            ]);
        }
    }
}
