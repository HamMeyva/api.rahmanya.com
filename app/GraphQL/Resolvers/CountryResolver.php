<?php

namespace App\GraphQL\Resolvers;

use App\Models\Common\Country;
use Illuminate\Support\Collection;

class CountryResolver
{
    /**
     * Tüm ülkeleri getirir
     */
    public function fetchAllCountries(): array
    {
        $countries = Country::select(['id', 'name', 'iso2', 'iso3', 'native', 'phone_code'])
            ->orderBy('name')
            ->get();

        return [
            'success' => true,
            'countries' => $countries,
        ];
    }
}
