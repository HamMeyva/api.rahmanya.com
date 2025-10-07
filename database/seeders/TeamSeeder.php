<?php

namespace Database\Seeders;

use App\Models\Relations\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $dataSet = [
            [
                'name' => 'Adana Demirspor',
                'colors' => [
                    'color1' => '#0033A1', // Mavi
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/9/9e/Adana_Demirspor.png',
            ],
            [
                'name' => 'Alanyaspor',
                'colors' => [
                    'color1' => '#FF7F00', // Turuncu
                    'color2' => '#008000', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/7/7f/Alanyaspor_logo.png',
            ],
            [
                'name' => 'Antalyaspor',
                'colors' => [
                    'color1' => '#FF0000', // Kırmızı
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/7/7e/Antalyaspor_logo.png',
            ],
            [
                'name' => 'Beşiktaş',
                'colors' => [
                    'color1' => '#000000', // Siyah
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/2/2a/Be%C5%9Fikta%C5%9F_JK.png',
            ],
            [
                'name' => 'Bodrum FK',
                'colors' => [
                    'color1' => '#008000', // Yeşil
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/3/3e/Bodrumspor_logo.png',
            ],
            [
                'name' => 'Çaykur Rizespor',
                'colors' => [
                    'color1' => '#008000', // Yeşil
                    'color2' => '#0000FF', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/5/5b/%C3%87aykur_Rizespor_logo.png',
            ],
            [
                'name' => 'Eyüpspor',
                'colors' => [
                    'color1' => '#FFD700', // Sarı
                    'color2' => '#800080', // Mor
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/8/8e/Ey%C3%BCpspor_logo.png',
            ],
            [
                'name' => 'Fenerbahçe',
                'colors' => [
                    'color1' => '#000080', // Lacivert
                    'color2' => '#FFFF00', // Sarı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/9/9f/Fenerbah%C3%A7e_SK.png',
            ],
            [
                'name' => 'Galatasaray',
                'colors' => [
                    'color1' => '#A32638', // Koyu Kırmızı
                    'color2' => '#FDB927', // Altın Sarısı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/3/31/Galatasaray_Sports_Club_Logo.png',
            ],
            [
                'name' => 'Gaziantep FK',
                'colors' => [
                    'color1' => '#FF0000', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/7/7e/Gaziantep_FK_logo.png',
            ],
            [
                'name' => 'Göztepe',
                'colors' => [
                    'color1' => '#FF0000', // Kırmızı
                    'color2' => '#FFFF00', // Sarı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/9/9f/G%C3%B6ztepe_logo.png',
            ],
            [
                'name' => 'Hatayspor',
                'colors' => [
                    'color1' => '#800000', // Bordo
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/7/7b/Hatayspor_logo.png',
            ],
            [
                'name' => 'İstanbul Başakşehir',
                'colors' => [
                    'color1' => '#000080', // Lacivert
                    'color2' => '#FF4500', // Turuncu
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/1/12/%C4%B0stanbul_Ba%C5%9Fak%C5%9Fehir_FK_logo.png',
            ],
            [
                'name' => 'Kasımpaşa',
                'colors' => [
                    'color1' => '#0000FF', // Mavi
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/7/7a/Kas%C4%B1mpa%C5%9Fa_SK_logo.png',
            ],
            [
                'name' => 'Kayserispor',
                'colors' => [
                    'color1' => '#FF0000', // Kırmızı
                    'color2' => '#FFFF00', // Sarı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/b/b8/Kayserispor_logo.png',
            ],
            [
                'name' => 'Konyaspor',
                'colors' => [
                    'color1' => '#008000', // Yeşil
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/8/88/Konyaspor_logo.png',
            ],
            [
                'name' => 'Samsunspor',
                'colors' => [
                    'color1' => '#FF0000', // Kırmızı
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/8/8e/Samsunspor_logo.png',
            ],
            [
                'name' => 'Sivasspor',
                'colors' => [
                    'color1' => '#FF0000', // Kırmızı
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/80/Sivasspor.png/300px-Sivasspor.png',
            ],
            [
                'name' => 'Trabzonspor',
                'colors' => [
                    'color1' => '#800000', // Bordo
                    'color2' => '#2162a0', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/a/ab/TrabzonsporAmblemi.png',
            ],
            [
                'name' => 'Adanaspor',
                'colors' => [
                    'color1' => '#ff6600', // Turuncu
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/e/ed/Adanaspor_logo.png',
            ],
            [
                'name' => 'Amed SFK',
                'colors' => [
                    'color1' => '#1d6b00', // Yeşil
                    'color2' => '#ff0000', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/6/6e/Amed_SK.png',
            ],
            [
                'name' => 'Ankara Keçiörengücü SK',
                'colors' => [
                    'color1' => '#601670', // Eflatun
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/c/c1/Ankara_Ke%C3%A7i%C3%B6reng%C3%BCc%C3%BC_SK.png',
            ],
            [
                'name' => 'Bandırmaspor',
                'colors' => [
                    'color1' => '#632739', // Bordo
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/c/cb/Band%C4%B1rmaspor.png',
            ],
            [
                'name' => 'Boluspor',
                'colors' => [
                    'color1' => '#ed301f', // Kırmızı
                    'color2' => '#FFFFFF', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/2/22/Boluspork.png/330px-Boluspork.png',
            ],
            [
                'name' => 'Çorum FK',
                'colors' => [
                    'color1' => '#e30918', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/3/37/%C3%87orum_FK.png',
            ],
            [
                'name' => 'Erzurumspor FK',
                'colors' => [
                    'color1' => '#02306b', // Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/0/0a/Erzurumspor_FK.png',
            ],
            [
                'name' => 'Esenler Erokspor',
                'colors' => [
                    'color1' => '#009100', // Yeşil
                    'color2' => '#f5ed00', // Sarı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/4/46/Esenler_Erokspor_logo.png',
            ],
            [
                'name' => 'Fatih Karagümrük SK',
                'colors' => [
                    'color1' => '#ed2a1c', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/9/90/Fatihkaragumruk.png/330px-Fatihkaragumruk.png',
            ],
            [
                'name' => 'Gençlerbirliği',
                'colors' => [
                    'color1' => '#e00007', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/f/f7/Genclerbirligi.png/330px-Genclerbirligi.png',
            ],
            [
                'name' => 'Iğdır FK',
                'colors' => [
                    'color1' => '#016635', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/5/57/I%C4%9Fd%C4%B1r_FK_logo.png/330px-I%C4%9Fd%C4%B1r_FK_logo.png',
            ],
            [
                'name' => 'İstanbulspor',
                'colors' => [
                    'color1' => '#f7e11b', // Sarı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/e/ed/IstanbulsporAS.png/330px-IstanbulsporAS.png',
            ],
            [
                'name' => 'Kocaelispor',
                'colors' => [
                    'color1' => '#027845', // Yeşil
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/5/5a/Kocaelispor.png/330px-Kocaelispor.png',
            ],
            [
                'name' => 'Manisa FK',
                'colors' => [
                    'color1' => '#000000', // Siyah
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/d/dd/Manisa_FK.png',
            ],
            [
                'name' => 'MKE Ankaragücü',
                'colors' => [
                    'color1' => '#ffff00', // Sarı
                    'color2' => '#000080', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/9/97/MKE_Ankarag%C3%BCc%C3%BC_logo.png/330px-MKE_Ankarag%C3%BCc%C3%BC_logo.png',
            ],
            [
                'name' => 'Pendikspor',
                'colors' => [
                    'color1' => '#ff0000', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/2/2e/Pendikspor.png/330px-Pendikspor.png',
            ],
            [
                'name' => 'Sakaryaspor',
                'colors' => [
                    'color1' => '#009644', // Yeşil
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/3/34/Sakaryaspor_Logosu.png/330px-Sakaryaspor_Logosu.png',
            ],
            [
                'name' => 'Şanlıurfaspor',
                'colors' => [
                    'color1' => '#fff703', // Sarı
                    'color2' => '#38736c', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/1c/%C5%9Eanl%C4%B1urfaspor.png/330px-%C5%9Eanl%C4%B1urfaspor.png',
            ],
            [
                'name' => 'Ümraniyespor',
                'colors' => [
                    'color1' => '#cc0000', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/7/75/%C3%9Cmraniyespor_Logosu.png/330px-%C3%9Cmraniyespor_Logosu.png',
            ],
            [
                'name' => 'Yeni Malatyaspor',
                'colors' => [
                    'color1' => '#f0e402', // Sarı
                    'color2' => '#0f1012', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/f/f8/Yeni_Malatyaspor.png/330px-Yeni_Malatyaspor.png',
            ],
            [
                'name' => '1461 Trabzon FK',
                'colors' => [
                    'color1' => '#22346e', // Lacivert
                    'color2' => '#e6241e', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/9/91/1461_Trabzon_FK.png/330px-1461_Trabzon_FK.png',
            ],
            [
                'name' => '24 Erzincanspor',
                'colors' => [
                    'color1' => '#fa0202', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/6/65/24_Erzincanspor.png/330px-24_Erzincanspor.png',
            ],
            [
                'name' => 'Adana 01 FK',
                'colors' => [
                    'color1' => '#ffe100', // Sarı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/5/5f/Adana_01_FK.png/330px-Adana_01_FK.png',
            ],
            [
                'name' => 'Afyonspor',
                'colors' => [
                    'color1' => '#40165c', // Mor
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/14/Afyonspor_Logo.png/330px-Afyonspor_Logo.png',
            ],
            [
                'name' => 'Altay SK',
                'colors' => [
                    'color1' => '#000000', // Siyah
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/c/c3/AltaySKlogo.png',
            ],
            [
                'name' => 'Altınordu FK',
                'colors' => [
                    'color1' => '#161c38', // Lacivert
                    'color2' => '#c21717', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/b/b0/Altinordu_FK.png',
            ],
            [
                'name' => 'Ankaraspor',
                'colors' => [
                    'color1' => '#27456b', // Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/7/78/Sincan_Belediyesi_Ankaraspor.png/330px-Sincan_Belediyesi_Ankaraspor.png',
            ],
            [
                'name' => 'Batman Petrolspor',
                'colors' => [
                    'color1' => '#e60514', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/c/c9/Batman_Petrolspor_2023.png/330px-Batman_Petrolspor_2023.png',
            ],
            [
                'name' => 'Fethiyespor',
                'colors' => [
                    'color1' => '#292c91', // Lacivert
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/3/32/Fethiyespor.png/330px-Fethiyespor.png',
            ],
            [
                'name' => 'Isparta 32 SK',
                'colors' => [
                    'color1' => '#219c37', // Yeşil
                    'color2' => '#f250b4', // Pembe
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/f/fe/Isparta32spor.png',
            ],
            [
                'name' => 'İnegölspor',
                'colors' => [
                    'color1' => '#8a272c', // Bordo
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/9/96/%C4%B0negolspor1.png',
            ],
            [
                'name' => 'İskenderunspor',
                'colors' => [
                    'color1' => '#ed7002', // Turuncu
                    'color2' => '#02a5e0', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/a/a9/Iskenderunspor_2025.png/330px-Iskenderunspor_2025.png',
            ],
            [
                'name' => 'Karaköprü Belediyespor',
                'colors' => [
                    'color1' => '#cc1839', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/a/a9/Iskenderunspor_2025.png/330px-Iskenderunspor_2025.png',
            ],
            [
                'name' => 'Kastamonuspor',
                'colors' => [
                    'color1' => '#eb1722', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/0/0f/GMG_Kastamonuspor.png/330px-GMG_Kastamonuspor.png',
            ],
            [
                'name' => 'Kepezspor',
                'colors' => [
                    'color1' => '#3fc0cc', // Kırmızı
                    'color2' => '#c40a16', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/3/3b/Kepezsporlogo.png/330px-Kepezsporlogo.png',
            ],
            [
                'name' => 'Kırklarelispor',
                'colors' => [
                    'color1' => '#018768', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/9/97/K%C4%B1rklarelispor.png/330px-K%C4%B1rklarelispor.png',
            ],
            [
                'name' => 'Sarıyer SK',
                'colors' => [
                    'color1' => '#023bbf', // Lacivert
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/a/a8/Sar%C4%B1yerspor.png/330px-Sar%C4%B1yerspor.png',
            ],
            [
                'name' => 'Beykoz Anadoluspor',
                'colors' => [
                    'color1' => '#f5e940', // Sarı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/2/2d/Beykoz_Anadoluspor.png/330px-Beykoz_Anadoluspor.png',
            ],
            [
                'name' => '68 Aksarayspor',
                'colors' => [
                    'color1' => '#000000', // Siyah
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/b/bd/68AksarayBelediyespor.png/330px-68AksarayBelediyespor.png',
            ],
            [
                'name' => 'Ankara Demirspor',
                'colors' => [
                    'color1' => '#202642', // Lacivert
                    'color2' => '#65bdf0', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/f/ff/Ankarademirspor.png/330px-Ankarademirspor.png',
            ],
            [
                'name' => 'Arnavutköy Belediyespor',
                'colors' => [
                    'color1' => '#084680', // Mavi
                    'color2' => '#5bab44', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/0/0b/Arnavutk%C3%B6y_Belediyespor_logo.png',
            ],
            [
                'name' => 'Belediye Derincespor',
                'colors' => [
                    'color1' => '#edd81a', // Mavi
                    'color2' => '#294999', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/0/05/Belediye_derincespor2.png/330px-Belediye_derincespor2.png',
            ],
            [
                'name' => 'Beyoğlu Yeni Çarşı SF',
                'colors' => [
                    'color1' => '#1097cc', // Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/8f/Beyoglu_yeni_carsi_sf.png/330px-Beyoglu_yeni_carsi_sf.png',
            ],
            [
                'name' => 'Bucaspor',
                'colors' => [
                    'color1' => '#f5dc00', // Sarı
                    'color2' => '#322b9e', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/f/fb/Bucaspor.png',
            ],
            [
                'name' => 'Diyarbekirspor',
                'colors' => [
                    'color1' => '#01854e', // Yeşil
                    'color2' => '#eb2015', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/a/a2/Diyarbekirspor_A.%C5%9E.png',
            ],
            [
                'name' => 'Elazığspor',
                'colors' => [
                    'color1' => '#8f2733', // Bordo
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/3/3e/Elazigspor.png/330px-Elazigspor.png',
            ],
            [
                'name' => 'Erbaaspor',
                'colors' => [
                    'color1' => '#2967b3', // Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/d/d3/Erbaa_spor_kul%C3%BCb%C3%BC_logosu.png/330px-Erbaa_spor_kul%C3%BCb%C3%BC_logosu.png',
            ],
            [
                'name' => 'Giresunspor',
                'colors' => [
                    'color1' => '#076304', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/c/c1/Giresunspor.png',
            ],
            [
                'name' => 'Karacabey Belediyespor',
                'colors' => [
                    'color1' => '#096eba', // Mavi
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/4/42/Karacabey_Belediyespor.png/330px-Karacabey_Belediyespor.png',
            ],
            [
                'name' => 'Karaman FK',
                'colors' => [
                    'color1' => '#ab1411', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/9/93/Karaman_FK_Logo.png/330px-Karaman_FK_Logo.png',
            ],
            [
                'name' => 'Menemen FK',
                'colors' => [
                    'color1' => '#003763', // Lacivert
                    'color2' => '#d6c229', // Sarı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/85/Menemenfk.png/330px-Menemenfk.png',
            ],
            [
                'name' => 'Nazillispor',
                'colors' => [
                    'color1' => '#000000', // Siyah
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/9/95/Nazillispor_%282024%29.png',
            ],
            [
                'name' => 'Serik Belediyespor',
                'colors' => [
                    'color1' => '#029946', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/a/a8/Serik_Belediyespor.png/330px-Serik_Belediyespor.png',
            ],
            [
                'name' => 'Somaspor',
                'colors' => [
                    'color1' => '#000000', // Siyah
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/89/Somaspor.png/330px-Somaspor.png',
            ],
            [
                'name' => 'Vanspor FK',
                'colors' => [
                    'color1' => '#ff0303', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/5/55/Vanspor_FK.png/330px-Vanspor_FK.png',
            ],
            [
                'name' => 'Yeni Mersin İdmanyurdu',
                'colors' => [
                    'color1' => '#334c82', // Lacivert
                    'color2' => '#de212b', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/d/d2/Yeni_Mersin_%C4%B0dmanyurdu.png',
            ],
            [
                'name' => '23 Elazığ FK',
                'colors' => [
                    'color1' => '#820303', // Bordo
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/1c/23K1.png/330px-23K1.png',
            ],
            [
                'name' => 'Anadolu Üniversitesi Gençlik Spor Kulübü',
                'colors' => [
                    'color1' => '#0303ff', // Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/2/22/Anadolu_%C3%9Cniversitesi_SK.png/330px-Anadolu_%C3%9Cniversitesi_SK.png',
            ],
            [
                'name' => 'Artvin Hopaspor',
                'colors' => [
                    'color1' => '#74277d', // Mor
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/3/3c/Artvin_Hopaspor_logo.png/330px-Artvin_Hopaspor_logo.png',
            ],
            [
                'name' => 'Belediye Kütahyaspor',
                'colors' => [
                    'color1' => '#2bd1ff', // Mavi
                    'color2' => '#2c0061', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/7/7d/Belediye_K%C3%BCtahyaspor.png/330px-Belediye_K%C3%BCtahyaspor.png',
            ],
            [
                'name' => 'Bornova 1877 SY',
                'colors' => [
                    'color1' => '#146329', // Yeşil
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Bornova1877sportif1.png/330px-Bornova1877sportif1.png',
            ],
            [
                'name' => 'Bulvarspor',
                'colors' => [
                    'color1' => '#cf0202', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/1b/Kartal_Bulvarspor.png/330px-Kartal_Bulvarspor.png',
            ],
            [
                'name' => 'Bursaspor',
                'colors' => [
                    'color1' => '#017d31', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/5/5c/Bursaspor-amblem.png/330px-Bursaspor-amblem.png',
            ],
            [
                'name' => 'Düzcespor',
                'colors' => [
                    'color1' => '#ed1520', // Kırmızı
                    'color2' => '#0d0557', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/b/b0/Duzcespor-asil-duzcesporlogo-kirmizi-lacivert-resmi-logo-arma.png/330px-Duzcespor-asil-duzcesporlogo-kirmizi-lacivert-resmi-logo-arma.png',
            ],
            [
                'name' => 'Ergene Velimeşe SK',
                'colors' => [
                    'color1' => '#1a30d9', // Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/4/41/Ergene_Velime%C5%9Fe_SK.png/330px-Ergene_Velime%C5%9Fe_SK.png',
            ],
            [
                'name' => 'Kahramanmaraşspor',
                'colors' => [
                    'color1' => '#ed4545', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/82/Kahramanmarasspor_PIXEL.png/330px-Kahramanmarasspor_PIXEL.png',
            ],
            [
                'name' => 'Karşıyaka SK',
                'colors' => [
                    'color1' => '#02a64e', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/82/Kahramanmarasspor_PIXEL.png/330px-Kahramanmarasspor_PIXEL.png',
            ],
            [
                'name' => 'Kırşehir Futbol SK',
                'colors' => [
                    'color1' => '#018030', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/0/02/K%C4%B1r%C5%9Fehir_FK.png/330px-K%C4%B1r%C5%9Fehir_FK.png',
            ],
            [
                'name' => 'Kuşadasıspor',
                'colors' => [
                    'color1' => '#000000', // Siyah
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/4/44/Ku%C5%9Fadas%C4%B1spor1.png',
            ],
            [
                'name' => 'Muşspor',
                'colors' => [
                    'color1' => '#ffc800', // Sarı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/1b/Musspor.png/330px-Musspor.png',
            ],
            [
                'name' => 'Silifke Belediyespor',
                'colors' => [
                    'color1' => '#e32f0b', // Kırmızı
                    'color2' => '#332694', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/2/2a/Silifke_Belediyespor.png/330px-Silifke_Belediyespor.png',
            ],
            [
                'name' => 'Tokat Belediye Plevnespor',
                'colors' => [
                    'color1' => '#cc212f', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/c/cb/Tokat_Belediye_Plevnespor_arma.png',
            ],
            [
                'name' => 'Adıyaman FK',
                'colors' => [
                    'color1' => '#ffd500', // Sarı
                    'color2' => '#006647', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/c/c3/Ad%C4%B1yaman_FK_yeni.png/330px-Ad%C4%B1yaman_FK_yeni.png',
            ],
            [
                'name' => 'Amasyaspor FK',
                'colors' => [
                    'color1' => '#02a659', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/b/b7/Amasyaspor1968_logo.png/330px-Amasyaspor1968_logo.png',
            ],
            [
                'name' => 'Balıkesirspor',
                'colors' => [
                    'color1' => '#ed1520', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/5/5a/Balikesirspor.png/330px-Balikesirspor.png',
            ],
            [
                'name' => 'Beykoz İshaklı SF',
                'colors' => [
                    'color1' => '#6b0612', // Bordo
                    'color2' => '#0293cc', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/5/54/Beykozishaklispor.png/330px-Beykozishaklispor.png',
            ],
            [
                'name' => 'Çayelispor',
                'colors' => [
                    'color1' => '#20873e', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/c/ce/%C3%87ayelispor.png/330px-%C3%87ayelispor.png',
            ],
            [
                'name' => 'Etimesgutspor',
                'colors' => [
                    'color1' => '#f7ed23', // Sarı
                    'color2' => '#2b3a8f', // Lacivert
                ],
                'logo' => 'https://www.etimesgutbelediyespor.org/wp-content/uploads/2025/02/yeniLogo.png',
            ],
            [
                'name' => 'Fatsa Belediyespor',
                'colors' => [
                    'color1' => '#0253d6', // Lacivert
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/b/bc/Fatsa_Belediyespor_yeni.png/330px-Fatsa_Belediyespor_yeni.png',
            ],
            [
                'name' => 'İnegöl Kafkas Gençlikspor',
                'colors' => [
                    'color1' => '#0d662e', // Yeşil
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/c/c9/%C4%B0neg%C3%B6l_Kafkas_Gen%C3%A7likspor.png',
            ],
            [
                'name' => 'Kelkit Hürriyetspor',
                'colors' => [
                    'color1' => '#ff0303', // Kırmızı
                    'color2' => '#018f56', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/8c/Kelkit_H%C3%BCrriyetspor.png/330px-Kelkit_H%C3%BCrriyetspor.png',
            ],
            [
                'name' => 'Mazıdağı Fosfatspor',
                'colors' => [
                    'color1' => '#154785', // Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/8/81/Maz%C4%B1da%C4%9F%C4%B1_Fosfatspor1.png',
            ],
            [
                'name' => 'Muğlaspor',
                'colors' => [
                    'color1' => '#0f8a3e', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/c/c8/Mu%C4%9Flaspor.png/330px-Mu%C4%9Flaspor.png',
            ],
            [
                'name' => 'Nevşehir Belediyespor',
                'colors' => [
                    'color1' => '#d90202', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/0/0b/Nev%C5%9Fehir_Belediyespor.png/330px-Nev%C5%9Fehir_Belediyespor.png',
            ],
            [
                'name' => 'Silivrispor',
                'colors' => [
                    'color1' => '#fc0303', // Kırmızı
                    'color2' => '#0303ff', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/b/be/Silivrispor.png/330px-Silivrispor.png',
            ],
            [
                'name' => 'Tire 2021 FK',
                'colors' => [
                    'color1' => '#c91414', // Kırmızı
                    'color2' => '#ede321', // Sarı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/2/2a/Tire_2021_fk1.png',
            ],
            [
                'name' => 'Türk Metal 1963 SK',
                'colors' => [
                    'color1' => '#031c3b', // Lacivert
                    'color2' => '#e31920', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/4/46/T%C3%BCrk_Metal_1963_SK.png/330px-T%C3%BCrk_Metal_1963_SK.png',
            ],
            [
                'name' => 'Uşakspor',
                'colors' => [
                    'color1' => '#e6151c', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/7/79/U%C5%9Fakspor_A.%C5%9E..png/330px-U%C5%9Fakspor_A.%C5%9E..png',
            ],
            [
                'name' => 'Konyaspor',
                'colors' => [
                    'color1' => '#088a5f', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/9/91/1922_Konyaspor.png',
            ],
            [
                'name' => '1923 Mustafakemalpaşa SK',
                'colors' => [
                    'color1' => '#25459c', // Lacivert
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://im.mackolik.com/img/logo/buyuk/544.gif',
            ],
            [
                'name' => '52 Orduspor',
                'colors' => [
                    'color1' => '#642d91', // Mor
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/a/a5/52_Orduspor.png',
            ],
            [
                'name' => 'Alanya 1221 FSK',
                'colors' => [
                    'color1' => '#ed7300', // Turuncu
                    'color2' => '#1468de', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/a/a2/Alanya_1221_FK.png/330px-Alanya_1221_FK.png',
            ],
            [
                'name' => 'Aliağa FK',
                'colors' => [
                    'color1' => '#ffff00', // Sarı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/6/64/Alia%C4%9Fa_FK.png/330px-Alia%C4%9Fa_FK.png',
            ],
            [
                'name' => 'Ayvalıkgücü Belediyespor',
                'colors' => [
                    'color1' => '#ed1c27', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/4/4e/Ayval%C4%B1kg%C3%BCc%C3%BC.png',
            ],
            [
                'name' => 'Bayburt Özel İdarespor',
                'colors' => [
                    'color1' => '#ffee00', // Sarı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/9/90/Bayburt_%C3%96zel_%C4%B0darespor.png/330px-Bayburt_%C3%96zel_%C4%B0darespor.png',
            ],
            [
                'name' => 'Çankayaspor',
                'colors' => [
                    'color1' => '#d92127', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/f/f5/%C3%87ankayaFK16.png',
            ],
            [
                'name' => 'Çorluspor 1947',
                'colors' => [
                    'color1' => '#f01620', // Kırmızı
                    'color2' => '#fcd700', // Sarı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/2/2b/%C3%87orluspor_1947.png/330px-%C3%87orluspor_1947.png',
            ],
            [
                'name' => 'Efeler 09 SFK',
                'colors' => [
                    'color1' => '#116935', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/4/4c/Efeler09SFK.png',
            ],
            [
                'name' => 'Karabük İdman Yurdu',
                'colors' => [
                    'color1' => '#e31920', // Kırmızı
                    'color2' => '#004394', // Mavi
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/6/60/Karab%C3%BCkidmanyurdu.png/330px-Karab%C3%BCkidmanyurdu.png',
            ],
            [
                'name' => 'Küçükçekmece Sinopspor',
                'colors' => [
                    'color1' => '#ffbf29', // Turuncu
                    'color2' => '#172d87', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/11/K%C3%BC%C3%A7%C3%BCk%C3%A7ekmece_Sinopspor.png/330px-K%C3%BC%C3%A7%C3%BCk%C3%A7ekmece_Sinopspor.png',
            ],
            [
                'name' => 'Osmaniyespor FK',
                'colors' => [
                    'color1' => '#ffcc00', // Sarı
                    'color2' => '#028200', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/7/71/Osmaniyespor_FK.png/330px-Osmaniyespor_FK.png',
            ],
            [
                'name' => 'Pazarspor',
                'colors' => [
                    'color1' => '#77abd4', // Açık Mavi
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/d/dc/Pazarspor_Yeni.png/330px-Pazarspor_Yeni.png',
            ],
            [
                'name' => 'Viranşehir Belediyespor',
                'colors' => [
                    'color1' => '#58bfc7', // Turkuaz
                    'color2' => '#d9212a', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/16/Viransehir_Belediyespor.png/330px-Viransehir_Belediyespor.png',
            ],
            [
                'name' => 'Yozgat Belediyesi Bozokspor',
                'colors' => [
                    'color1' => '#8f0000', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/1/13/Yozgat_Belediyesi_Bozokspor.png',
            ],
            [
                'name' => 'Polatlı 1926 SK',
                'colors' => [
                    'color1' => '#ffee00', // Sarı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/1f/Polatli_1926_SK.png/330px-Polatli_1926_SK.png',
            ],
            [
                'name' => 'Ağrı 1970 SK',
                'colors' => [
                    'color1' => '#ffff00', // Sarı
                    'color2' => '#000082', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/9/94/A%C4%9Fr%C4%B11970spor.png/330px-A%C4%9Fr%C4%B11970spor.png',
            ],
            [
                'name' => 'İzmir Çoruhlu FK',
                'colors' => [
                    'color1' => '#ff6600', // Turuncu
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/0/09/%C4%B0zmir_coruhlu_fk.png',
            ],
            [
                'name' => 'Büyükçekmece Tepecikspor',
                'colors' => [
                    'color1' => '#13542b', // Yeşil
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/f/fc/B%C3%BCy%C3%BCk%C3%A7ekmece_Tepecikspor.png/330px-B%C3%BCy%C3%BCk%C3%A7ekmece_Tepecikspor.png',
            ],
            [
                'name' => 'Denizlispor',
                'colors' => [
                    'color1' => '#01a74f', // Yeşil
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/c/cd/Denizlispor.png/330px-Denizlispor.png',
            ],
            [
                'name' => 'Edirnespor',
                'colors' => [
                    'color1' => '#ffee00', // Sarı
                    'color2' => '#e31b22', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/e/ea/Edirnespor1.png',
            ],
            [
                'name' => 'Kahramanmaraş İstiklalspor',
                'colors' => [
                    'color1' => '#f00008', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/6/66/Kahramanmara%C5%9F_%C4%B0stiklalspor.png/330px-Kahramanmara%C5%9F_%C4%B0stiklalspor.png',
            ],
            [
                'name' => 'Kırıkkale FK',
                'colors' => [
                    'color1' => '#1e2d75', // Lacivert
                    'color2' => '#de020a', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/a/ad/K%C4%B1r%C4%B1kkale_FK.png/330px-K%C4%B1r%C4%B1kkale_FK.png',
            ],
            [
                'name' => 'Mardin 1969 SK',
                'colors' => [
                    'color1' => '#f01322', // Kırmızı
                    'color2' => '#121b47', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/4/47/Mardin_1969_SK.png/330px-Mardin_1969_SK.png',
            ],
            [
                'name' => 'Niğde Belediyesispor',
                'colors' => [
                    'color1' => '#e01b25', // Kırmızı
                    'color2' => '#16408a', // Lacivert
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/2/21/Nigdebelediyesispor.png/330px-Nigdebelediyesispor.png',
            ],
            [
                'name' => 'Bursa Nilüfer FK',
                'colors' => [
                    'color1' => '#00296b', // Mavi
                    'color2' => '#017001', // Yeşil
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/1/11/Bursaniluferfk.png/330px-Bursaniluferfk.png',
            ],
            [
                'name' => 'Orduspor 1967',
                'colors' => [
                    'color1' => '#422d7d', // Mor
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/0/0a/Orduspor19674.png/330px-Orduspor19674.png',
            ],
            [
                'name' => 'Sebat Gençlikspor',
                'colors' => [
                    'color1' => '#cc0000', // Kırmızı
                    'color2' => '#ffffff', // Beyaz
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/9/98/Sebat_Gen%C3%A7likspor.png',
            ],
            [
                'name' => 'Erciyes 38 FSK',
                'colors' => [
                    'color1' => '#372b8f', // Mavi
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/thumb/8/84/Erciyes_38_fsk.png/330px-Erciyes_38_fsk.png',
            ],
            [
                'name' => '7 Eylül Turgutlu 1984 SY',
                'colors' => [
                    'color1' => '#cc0000', // Kırmızı
                    'color2' => '#000000', // Siyah
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/7/77/Turgutluspor.png',
            ],
            [
                'name' => 'Zonguldakspor FK',
                'colors' => [
                    'color1' => '#00254f', // Lacivert
                    'color2' => '#d92130', // Kırmızı
                ],
                'logo' => 'https://upload.wikimedia.org/wikipedia/tr/8/82/Zonguldakspor_FK.png',
            ],
        ];

        if (Team::doesntExist()) {
            foreach ($dataSet as $data) {
                Team::create($data);
            }
        }


    }
}
