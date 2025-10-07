<?php

namespace App\Models\Fake;

use App\Models\Morph\ReportProblem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sushi\Sushi;

/**
 * @mixin IdeHelperReportProblemCategory
 */
class ReportProblemCategory extends Model
{
    use Sushi;

    public const
        MUSTEHCEN_ICERIK = 1,
        UYGUNSUZ_ICERIK = 2,
        HAKARET_SOYLEMI = 3,
        IRKCILIK = 4,
        CINSEL_ISTISMAR = 5,
        SIYASI_ICERIK = 6,
        DIGER = 7;

    public static array $names = [
        self::MUSTEHCEN_ICERIK => 'Müstehcen İçerik',
        self::UYGUNSUZ_ICERIK => 'Uygunsuz İçerik',
        self::HAKARET_SOYLEMI => 'Hakaret Söylemi',
        self::IRKCILIK => 'Irkçılık',
        self::CINSEL_ISTISMAR => 'Cinsel İstismar',
        self::SIYASI_ICERIK => 'Siyasi Propaganda',
        self::DIGER => 'Diğer',
    ];
    
    public static array $slugs = [
        self::MUSTEHCEN_ICERIK => 'mustehcen_icerik',
        self::UYGUNSUZ_ICERIK => 'uygunsuz_icerik',
        self::HAKARET_SOYLEMI => 'hakaret_soylemi',
        self::IRKCILIK => 'irkcilik',
        self::CINSEL_ISTISMAR => 'cinsel_istismar',
        self::SIYASI_ICERIK => 'siyasi_icerik',
        self::DIGER => 'diger',
    ];

    public function getRows()
    {
        $result = [];
        foreach (self::$names as $id => $name) {
            $result[] = [
                'id' => $id, 
                'name' => $name,
                'slug' => self::$slugs[$id] ?? strtolower(str_replace(' ', '_', $name))
            ];
        }

        return $result;
    }

    protected function sushiShouldCache()
    {
        return true;
    }

    public function reported_problems(): HasMany
    {
        return $this->hasMany(ReportProblem::class, 'category_id');
    }
    
    /**
     * Slug'a göre kategori bulma
     * 
     * @param string $slug
     * @return self|null
     */
    public static function findBySlug(string $slug): ?self
    {
        $id = array_search($slug, self::$slugs);
        if ($id === false) {
            return null;
        }
        
        return self::find($id);
    }
    
    /**
     * Slug'ın geçerli olup olmadığını kontrol etme
     * 
     * @param string $slug
     * @return bool
     */
    public static function isValidSlug(string $slug): bool
    {
        return in_array($slug, self::$slugs);
    }
}
