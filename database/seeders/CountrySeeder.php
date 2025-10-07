<?php

namespace Database\Seeders;

use App\Models\Common\City;
use App\Models\Common\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run()
    {
        if (Country::query()->exists()) {
            return false;
        }

        ini_set('memory_limit', '-1');

        if (Storage::disk('local')->exists('/public/lcsc/city-state-country.sql')) {
            DB::unprepared(Storage::get('/public/lcsc/city-state-country.sql'));
            $this->info("#### Old Sql Truncated and new Sql Imported Successfully.. ####");
            return;
        }

        if (!Storage::disk('local')->exists('public/lcsc/city-state-country.json')) {
            $remoteJson = 'https://raw.githubusercontent.com/siberfx/countries-states-cities-database/master/json/countries+states+cities.json';

            $fileSize = $this->remoteFileSize($remoteJson);

            $this->info('Fetching countries from remote source...[' . $fileSize . ']');
            Storage::put('public/lcsc/city-state-country.json', $this->remoteGet($remoteJson));
        }

        $this->info('Importing countries from local to database ');

        $countries = json_decode(Storage::get('public/lcsc/city-state-country.json'));

        $selectedCountries = [
            'Turkey', // Türkçe
            'Cyprus', // Türkçe

            'United States', // İngilizce
            'United Kingdom', // İngilizce
            'Canada', // İngilizce
            'Australia', // İngilizce
            'New Zealand', // İngilizce

            'Spain', // İspanyolca
            'Mexico', // İspanyolca
            'Argentina', // İspanyolca
            'Colombia', // İspanyolca
            'Chile', // İspanyolca

            'Italy', // İtalyanca
            'San Marino', // İtalyanca
            'Vatican City', // İtalyanca

            'Portugal', // Portekizce
            'Brazil', // Portekizce
            'Angola', // Portekizce
            'Mozambique', // Portekizce

            'France', // Fransızca
            'Belgium', // Fransızca
            'Switzerland', // Fransızca
            'Luxembourg', // Fransızca
            'Canada', // Fransızca

            'Germany', // Almanca
            'Austria', // Almanca
            'Switzerland', // Almanca
            'Liechtenstein', // Almanca

            'Russia', // Rusça
            'Belarus', // Rusça
            'Kazakhstan', // Rusça
            'Kyrgyzstan', // Rusça
        ];

        foreach ($countries as $countryData) {
            if (in_array($countryData->name, $selectedCountries)) {
                $country = Country::query()->updateOrCreate(
                    [
                        "name" => $countryData->name,
                    ]
                    ,
                    [
                        "iso3" => $countryData->iso3,
                        "iso2" => $countryData->iso2,
                        "numeric_code" => $countryData->numeric_code,
                        "phone_code" => $countryData?->phone_code ?? null,
                        "capital" => $countryData->capital,
                        "currency" => $countryData->currency,
                        "currency_symbol" => $countryData->currency_symbol,
                        "tld" => $countryData->tld,
                        "native" => $countryData->native,
                        "region" => $countryData->region,
                        "latitude" => $countryData->latitude,
                        "longitude" => $countryData->longitude,
                    ]
                );
                $this->info("Adding " . $country->name . " state and cities... ");

                foreach ($countryData->states as $stateData) {
                    $state = City::query()->updateOrCreate(
                        [
                            "country_id" => $country->id,
                            "name" => $stateData->name,
                        ]
                        ,
                        [
                            "code" => $stateData->state_code,
                            "latitude" => $stateData->latitude,
                            "longitude" => $stateData->longitude,
                        ]
                    );
                }
            }

            $this->info("#### Done. ####");
        }
    }

    /**
     * Get Remote File
     *
     * @param [string] $url
     * @return bool|string
     */
    private function remoteGet($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * Get file size from remote source
     *
     * @param [string] $url
     * @return string
     */
    private function remoteFileSize($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        $data = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        $bytes = $size;
        if ($bytes > 0) {
            $unit = (int) log($bytes, 1024);
            $units = array('B', 'KB', 'MB', 'GB');
            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / (1024 ** $unit), $units[$unit]);
            }
        }
        return $bytes;
    }

    /**
     * Console output
     *
     * @param [string] $string
     * @return void
     */
    private function info($string)
    {
        echo $string . PHP_EOL;
    }
}
