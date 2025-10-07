<?php

namespace App\GraphQL\Resolvers;

use App\Models\SearchCard;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SearchResolver
{
    /**
     * Arama kartlarını getir
     *
     * @param mixed $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return array
     */
    public function getSearchCards($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $category = $args['category'] ?? null;

        // Cache key oluştur
        $cacheKey = "search_cards:" . ($category ?? 'all');

        // Cache'den getir veya hesapla
        return Cache::remember($cacheKey, 3600, function () use ($category) {
            $query = SearchCard::active();

            if ($category) {
                $query->where('category', $category);
            }

            $cards = $query->get();

            return [
                'cards' => $cards,
                'total' => $cards->count()
            ];
        });
    }

    /**
     * Arama kartı oluştur
     *
     * @param mixed $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return \App\Models\SearchCard
     */
    public function createSearchCard($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Sadece admin kullanıcılar için
        $user = $context->user();
        if (!$user || !$this->isUserAdmin($user)) {
            throw new \Exception('Bu işlem için yetkiniz yok.');
        }

        $input = $args['input'];

        // Varsayılan değerleri ayarla
        if (!isset($input['order'])) {
            $input['order'] = SearchCard::max('order') + 1;
        }

        if (!isset($input['is_active'])) {
            $input['is_active'] = true;
        }

        $input['click_count'] = 0;

        // Yeni kart oluştur
        $card = SearchCard::create($input);

        // Cache'i temizle
        $this->clearSearchCardsCache();

        return $card;
    }

    /**
     * Arama kartını güncelle
     *
     * @param mixed $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return \App\Models\SearchCard
     */
    public function updateSearchCard($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Sadece admin kullanıcılar için
        $user = $context->user();
        if (!$user || !$this->isUserAdmin($user)) {
            throw new \Exception('Bu işlem için yetkiniz yok.');
        }

        $id = $args['id'];
        $input = $args['input'];

        $card = SearchCard::findOrFail($id);
        $card->update($input);

        // Cache'i temizle
        $this->clearSearchCardsCache();

        return $card;
    }

    /**
     * Arama kartını sil (soft delete)
     *
     * @param mixed $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return bool
     */
    public function deleteSearchCard($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Sadece admin kullanıcılar için
        $user = $context->user();
        if (!$user || !$this->isUserAdmin($user)) {
            throw new \Exception('Bu işlem için yetkiniz yok.');
        }

        $id = $args['id'];

        $card = SearchCard::findOrFail($id);
        $card->delete();

        // Cache'i temizle
        $this->clearSearchCardsCache();

        return true;
    }

    /**
     * Arama kartının tıklanma sayısını artır
     *
     * @param mixed $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return bool
     */
    public function incrementSearchCardClick($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $id = $args['id'];

        // Queue'ya gönder
        dispatch(function () use ($id) {
            try {
                $card = SearchCard::findOrFail($id);
                $card->incrementClickCount();
            } catch (\Exception $e) {
                Log::error('Arama kartı tıklanma sayısı artırılamadı', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        })->onQueue('low');

        return true;
    }

    /**
     * Arama yap
     *
     * @param mixed $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return array
     */
    public function search($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];
        $query = $input['query'];
        $type = $input['type'];
        $page = $input['page'] ?? 1;
        $perPage = $input['per_page'] ?? 10;

        // Boş sorgu kontrolü
        if (empty(trim($query))) {
            return $this->emptySearchResponse($type, $page, $perPage);
        }

        // Cache key oluştur
        $cacheKey = "search:{$type}:{$query}:{$page}:{$perPage}";

        // Cache'den getir veya hesapla
        return Cache::remember($cacheKey, 300, function () use ($query, $type, $page, $perPage) {
            switch ($type) {
                case 'VIDEO':
                case 'VIDEOS':
                    // Hem VIDEO hem de VIDEOS tipini destekle
                    return $this->searchVideos($query, $page, $perPage);
                case 'USERS':
                    return $this->searchUsers($query, $page, $perPage);
                case 'TAGS':
                    return $this->searchTags($query, $page, $perPage);
                case 'LOCATIONS':
                    return $this->searchLocations($query, $page, $perPage);
                default:
                    throw new \Exception('Geçersiz arama tipi: ' . $type);
            }
        });
    }

    /**
     * Videolarda arama yap
     *
     * @param string $query
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function searchVideos($query, $page, $perPage)
    {
        // Debug log ekle
        \Log::info("Video arama başlatıldı: query={$query}, page={$page}, perPage={$perPage}");
        
        // MongoDB text search için regex oluştur
        // Regex'i daha basit ve güvenilir hale getir
        $regex = $query; // Basit string eşleşme için regex kullanmadan önce dene

        // Arama pipeline'ı
        $pipeline = [];

        // Sadece public videoları getir
        $pipeline[] = [
            '$match' => [
                'is_private' => false,
                'is_banned' => ['$ne' => true],
                '$or' => [
                    ['title' => ['$regex' => $query, '$options' => 'i']],
                    ['description' => ['$regex' => $query, '$options' => 'i']],
                    ['tags' => ['$regex' => $query, '$options' => 'i']],
                    ['team_tags' => ['$regex' => $query, '$options' => 'i']],
                    ['location' => ['$regex' => $query, '$options' => 'i']]
                ]
            ]
        ];
        
        // Debug log ekle
        \Log::info("Video arama pipeline: " . json_encode($pipeline));

        // Skora göre sırala
        $pipeline[] = [
            '$addFields' => [
                'searchScore' => [
                    '$add' => [
                        ['$cond' => [
                            'if' => ['$regexMatch' => ['input' => ['$ifNull' => ['$title', '']], 'regex' => $query, 'options' => 'i']],
                            'then' => 10,
                            'else' => 0
                        ]],
                        ['$cond' => [
                            'if' => ['$regexMatch' => ['input' => ['$ifNull' => ['$description', '']], 'regex' => $query, 'options' => 'i']],
                            'then' => 5,
                            'else' => 0
                        ]],
                        ['$cond' => [
                            'if' => [
                                '$gt' => [
                                    ['$size' => [
                                        '$filter' => [
                                            'input' => ['$cond' => [
                                                'if' => ['$isArray' => '$tags'],
                                                'then' => ['$ifNull' => ['$tags', []]],
                                                'else' => []
                                            ]],
                                            'as' => 'tag',
                                            'cond' => [
                                                '$regexMatch' => [
                                                    'input' => ['$ifNull' => ['$$tag', '']],
                                                    'regex' => $query, 
                                                    'options' => 'i'
                                                ]
                                            ]
                                        ]
                                    ]],
                                    0
                                ]
                            ],
                            'then' => 3,
                            'else' => 0
                        ]],
                        ['$cond' => [
                            'if' => [
                                '$gt' => [
                                    ['$size' => [
                                        '$filter' => [
                                            'input' => ['$cond' => [
                                                'if' => ['$isArray' => '$team_tags'],
                                                'then' => ['$ifNull' => ['$team_tags', []]],
                                                'else' => []
                                            ]],
                                            'as' => 'tag',
                                            'cond' => [
                                                '$regexMatch' => [
                                                    'input' => ['$ifNull' => ['$$tag', '']],
                                                    'regex' => $query, 
                                                    'options' => 'i'
                                                ]
                                            ]
                                        ]
                                    ]],
                                    0
                                ]
                            ],
                            'then' => 2,
                            'else' => 0
                        ]],
                        ['$cond' => [
                            'if' => ['$regexMatch' => ['input' => ['$ifNull' => ['$location', '']], 'regex' => $query, 'options' => 'i']],
                            'then' => 1,
                            'else' => 0
                        ]],
                        ['$ifNull' => ['$engagement_score', 0]]
                    ]
                ]
            ]
        ];
        
        // Debug log ekle
        \Log::info("Video arama skor hesaplama pipeline: " . json_encode($pipeline));

        // Skora göre sırala
        $pipeline[] = [
            '$sort' => [
                'searchScore' => -1,
                'created_at' => -1
            ]
        ];

        // Toplam sayıyı hesapla
        $totalPipeline = $pipeline;
        $totalPipeline[] = ['$count' => 'total'];
        $totalResult = Video::raw(function($collection) use ($totalPipeline) {
            return $collection->aggregate($totalPipeline)->toArray();
        });
        $total = $totalResult[0]->total ?? 0;

        // Sayfalama
        $pipeline[] = ['$skip' => ($page - 1) * $perPage];
        $pipeline[] = ['$limit' => $perPage];

        // Sonuçları getir
        $results = Video::raw(function($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });
        
        // Debug log ekle
        \Log::info("Video arama sonuçları ham hali: " . count($results) . " adet");
        
        // Video objelerine dönüştür
        $videos = [];
        foreach ($results as $result) {
            // _id değerini string'e çevir
            $videoId = (string) $result->_id;
            $video = Video::active()->find($videoId);
            if ($video) {
                $videos[] = $video;
            } else {
                // Video bulunamadıysa debug log ekle
                \Log::info("Video bulunamadı: id={$videoId}");
            }
        }
        
        // Debug log ekle
        \Log::info("Video arama sonuçları işlendikten sonra: " . count($videos) . " adet");

        return [
            'videos' => $videos,
            'users' => [],
            'tags' => [],
            'locations' => [],
            'type' => 'VIDEO',
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ];
    }

    /**
     * Kullanıcılarda arama yap
     *
     * @param string $query
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function searchUsers($query, $page, $perPage)
    {
        // Kullanıcılarda arama yap
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('surname', 'like', "%{$query}%")
            ->orWhere('nickname', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'videos' => [],
            'users' => $users->items(),
            'tags' => [],
            'locations' => [],
            'type' => 'USERS',
            'page' => $page,
            'per_page' => $perPage,
            'total' => $users->total()
        ];
    }

    /**
     * Etiketlerde arama yap
     *
     * @param string $query
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function searchTags($query, $page, $perPage)
    {
        // Debug log ekle
        \Log::info("Etiket arama başlatıldı: query={$query}, page={$page}, perPage={$perPage}");
        
        // Eşleşen tüm etiketleri bul
        $pipeline = [
            ['$match' => ['is_private' => false, 'is_banned' => ['$ne' => true]]],
            ['$project' => ['tags' => ['$ifNull' => ['$tags', []]]]],
            ['$unwind' => ['path' => '$tags', 'preserveNullAndEmptyArrays' => false]],
            ['$match' => ['tags' => ['$regex' => $query, '$options' => 'i']]],
            ['$group' => ['_id' => '$tags', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
            ['$limit' => 50]
        ];
        
        // Debug log ekle
        \Log::info("Etiket arama pipeline: " . json_encode($pipeline));

        $results = Video::raw(function($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });

        // Etiketleri çıkar
        $allTags = [];
        foreach ($results as $result) {
            $allTags[] = $result->_id;
        }

        // Sayfalama
        $total = count($allTags);
        $offset = ($page - 1) * $perPage;
        $tags = array_slice($allTags, $offset, $perPage);

        // Etiketlerle ilgili videoları getir
        $videos = [];
        if (!empty($tags)) {
            $videos = Video::active()->where('is_private', false)
                ->where('is_banned', '!=', true)
                ->whereIn('tags', $tags)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return [
            'videos' => $videos,
            'users' => [],
            'tags' => $tags,
            'locations' => [],
            'type' => 'TAGS',
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ];
    }

    /**
     * Lokasyonlarda arama yap
     *
     * @param string $query
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function searchLocations($query, $page, $perPage)
    {
        // Debug log ekle
        \Log::info("Konum arama başlatıldı: query={$query}, page={$page}, perPage={$perPage}");
        
        // Eşleşen tüm lokasyonları bul
        $pipeline = [
            ['$match' => [
                'is_private' => false, 
                'is_banned' => ['$ne' => true], 
                'location' => ['$regex' => $query, '$options' => 'i']
            ]],
            ['$project' => [
                'location' => ['$ifNull' => ['$location', '']]
            ]],
            ['$match' => [
                'location' => ['$ne' => '']
            ]],
            ['$group' => ['_id' => '$location', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
            ['$limit' => 50]
        ];
        
        // Debug log ekle
        \Log::info("Konum arama pipeline: " . json_encode($pipeline));

        $results = Video::raw(function($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });

        // Lokasyonları çıkar
        $allLocations = [];
        foreach ($results as $result) {
            if ($result->_id) {
                $allLocations[] = $result->_id;
            }
        }

        // Sayfalama
        $total = count($allLocations);
        $offset = ($page - 1) * $perPage;
        $locations = array_slice($allLocations, $offset, $perPage);

        // Lokasyonlarla ilgili videoları getir
        $videos = [];
        if (!empty($locations)) {
            $videos = Video::active()->where('is_private', false)
                ->where('is_banned', '!=', true)
                ->whereIn('location', $locations)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return [
            'videos' => $videos,
            'users' => [],
            'tags' => [],
            'locations' => $locations,
            'type' => 'LOCATIONS',
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ];
    }

    /**
     * Boş arama sonucu döndür
     *
     * @param string $type
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function emptySearchResponse($type, $page, $perPage)
    {
        return [
            'videos' => [],
            'users' => [],
            'tags' => [],
            'locations' => [],
            'type' => $type,
            'page' => $page,
            'per_page' => $perPage,
            'total' => 0
        ];
    }

    /**
     * Arama kartları cache'ini temizle
     *
     * @return void
     */
    protected function clearSearchCardsCache()
    {
        Cache::forget('search_cards:all');

        // Kategori bazlı cache'leri temizle
        $categories = SearchCard::distinct('category')->get()->pluck('category')->filter();
        foreach ($categories as $category) {
            Cache::forget("search_cards:{$category}");
        }
    }

    /**
     * Kullanıcının admin olup olmadığını kontrol et
     *
     * @param \App\Models\User $user
     * @return bool
     */
    protected function isUserAdmin(User $user)
    {
        // Kullanıcının rolünü veya yetkilerini kontrol et
        // Bu örnek için basit bir kontrol yapıyoruz, gerçek uygulamada daha karmaşık olabilir
        return $user->hasRole('admin') || $user->id === 1; // ID 1 genellikle ana admin olur
    }
}
