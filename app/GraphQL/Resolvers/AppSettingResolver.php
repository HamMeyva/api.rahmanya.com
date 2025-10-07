<?php

namespace App\GraphQL\Resolvers;

use App\Models\AppSetting;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AppSettingResolver
{
    public function getAppSettings($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $keys = [
            'maintenance_mode',
            'android_version',
            'ios_version',
            'contact_phone',
            'contact_email',
            'contact_address',
            'contact_company_name',
            'contact_tax_office',
            'contact_mersis_no',
            'terms_conditions',
            'purchase_contract',
            'user_agreement',
            'privacy_policy',
            'about_us',
        ];


        $appSettings = [];
        foreach ($keys as $key) {
            $appSettings[] = AppSetting::getSetting($key, false);
        }

        return [
            'success' => true,
            'data' => $appSettings
        ];
    }
}
