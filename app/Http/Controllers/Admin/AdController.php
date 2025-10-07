<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Ad\Ad;
use App\Helpers\Variable;
use App\Models\Ad\AdClick;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Models\Ad\Advertiser;
use App\Models\Morph\Payment;
use MongoDB\BSON\UTCDateTime;
use App\Models\Ad\AdImpression;
use App\Models\Common\Currency;
use App\Services\BunnyCdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Admin\Ad\StoreRequest;
use App\Http\Requests\Admin\Ad\UpdateRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdController extends Controller
{
    public function index()
    {
        return view('admin.pages.ads.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Ad::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('title', 'ILIKE', "%{$search}%");
            //                ->orWhere("address", "LIKE", "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['title', 'advertiser_id', 'status_id', 'payment_status_id', 'total_budget', 'total_hours', 'bid_amount', 'created_at']; //doldururuz

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        //
        $recordsFiltered = (clone $query)->count();

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $drawAdvertiser = "<span class='fs-7 text-gray-700 fw-bold text-hover-primary'>{$item->advertiser?->name}</span><br><span class='fs-8 text-muted text-hover-primary'>{$item->advertiser?->email}</span>";
            $title = (new CommonHelper())->limitText($item->title);
            $description = (new CommonHelper())->limitText($item->description);
            $showUrl = route('admin.ads.show', ['id' => $item->id]);

            $ad = "<div class='d-flex align-items-center'>
                    <a href='{$showUrl}' class='symbol symbol-50px'>
                        <span class='symbol-label' style='background-image:url({$item->thumbnail_url});'></span>
                    </a>
                    <a href='{$showUrl}' class='ms-3'>
                        <div class='fs-7 text-gray-800 fw-bold text-hover-primary'>{$title}</div>
                        <div class='fs-8 text-muted text-hover-primary'>{$description}</div>
                    </a>
                </div>";

            return [
                $ad,
                $drawAdvertiser,
                "<span class='badge badge-{$item->get_status_color}'>{$item->get_status}</span>",
                "<span class='badge badge-{$item->get_payment_status_color}'>{$item->get_payment_status}</span>",
                $item->draw_total_budget,
                $item->draw_total_hours,
                $item->draw_bid_amount,
                "<span class='badge badge-secondary'>{$item->get_created_at}</span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showView' => true,
                    'viewUrl' => $showUrl,
                    'showEdit' => true,
                    'editUrl' => route('admin.ads.edit', ['id' => $item->id])
                ])->render()
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        return view('admin.pages.ads.create-edit');
    }

    public function store(StoreRequest $request, BunnyCdnService $bunnyCdnService): JsonResponse
    {
        //DB::beginTransaction();
        try {
            $advertiser = Advertiser::find($request->input('advertiser_id'));
            if (!$advertiser) {
                return response()->json([
                    'message' => 'Reklamveren bulunamadı.',
                ], 404);
            }

            $validatedData = $request->validated();

            $totalBudget = (new CommonHelper)->formatDecimalInput($validatedData['total_budget']);
            $totalHours = (int) $validatedData['total_hours'];
            $bidAmount = (float) ($totalBudget / $totalHours);

            // 1. Temel reklam bilgilerini kaydet.
            $ad = Ad::create([
                'advertiser_id' => $validatedData['advertiser_id'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'redirect_url' => $validatedData['redirect_url'],
                'status_id' => Ad::STATUS_INACTIVE,
                'payment_status_id' => Ad::PAYMENT_STATUS_PENDING,
                'paid_at' => null,
                'start_date' => Carbon::parse($validatedData['start_date']),
                'show_start_time' => $validatedData['show_start_time'],
                'show_end_time' => $validatedData['show_end_time'],
                'total_budget' => $totalBudget,
                'total_hours' => $totalHours,
                'bid_amount' => $bidAmount,
                'target_country_id' => $validatedData['target_country_id'],
                'target_language_id' => $validatedData['target_language_id'],
            ]);

            // 2. Pivot kayıtlarını ekle.
            $ad->target_cities()->sync($request->input('target_city_ids', []));
            $ad->placements()->sync($request->input('placement_ids', []));
            $ad->target_age_ranges()->sync($request->input('target_age_range_ids', []));
            $ad->target_genders()->sync($request->input('target_gender_ids', []));
            $ad->target_teams()->sync($request->input('target_team_ids', []));
            $ad->target_oses()->sync($request->input('target_os_ids', []));

            // 3. Reklam mediasını kaydet.
            if ($request->hasFile('media_path')) {
                $media = $request->file('media_path');

                $mimeType = $media->getMimeType();
                if (strpos($mimeType, 'image/') === 0) {
                    $ad->media_type_id = Ad::MEDIA_TYPE_IMAGE;

                    $mediaName = Str::uuid() . '.' . $media->extension();
                    $mediaPath = "ads/{$ad->id}/medias/{$mediaName}";

                    $bunnyCdnService->uploadToStorage($mediaPath, $media->get());
                    $ad->media_path = $mediaPath;
                } elseif (strpos($mimeType, 'video/') === 0) {
                    $ad->media_type_id = Ad::MEDIA_TYPE_VIDEO;

                    //1. create video
                    $response = $bunnyCdnService->createVideo((new CommonHelper)->limitText($ad->title, 100), $advertiser->collection_uuid);
                    $guid = $response['guid'] ?? null;

                    $ad->video_guid = $guid;

                    //2. upload video
                    $bunnyCdnService->uploadVideo($guid, $request->file('media_path'));
                }

                $ad->save();
            }

            // 4. Ads pool cache'ini temizle.
            Cache::forget('ads_pool');

            //DB::commit();
            return response()->json([
                'message' => 'Reklam başarıyla eklendi.',
                'redirect_url' => route('admin.ads.index'),
            ]);
        } catch (Exception $e) {
            //DB::rollBack();
            Log::error('Ad creation failed', [
                'exception' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Bir hata oluştu, işlem yapılmadı.',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $ad = Ad::withTrashed()->find($id);
        if (!$ad) {
            throw new NotFoundHttpException();
        }

        $ad->load([
            'advertiser',
            'placements',
            'target_country',
            'target_cities',
            'target_age_ranges',
            'target_genders',
            'target_language',
            'target_teams',
            'target_oses',
        ]);

        return view('admin.pages.ads.show', compact('ad'));
    }

    public function edit($id)
    {
        $ad = Ad::withTrashed()->find($id);
        if (!$ad) {
            throw new NotFoundHttpException();
        }

        $ad->load([
            'advertiser',
            'placements',
            'target_country',
            'target_cities',
            'target_age_ranges',
            'target_genders',
            'target_language',
            'target_teams',
            'target_oses',
        ]);

        return view('admin.pages.ads.create-edit', compact('ad'));
    }

    public function update(UpdateRequest $request, BunnyCdnService $bunnyCdnService,  $id): JsonResponse
    {
        $ad = Ad::withTrashed()->find($id);
        if (!$ad) {
            throw new NotFoundHttpException();
        }

        $advertiser = Advertiser::find($ad->advertiser_id);
        if (!$advertiser) {
            return response()->json([
                'message' => 'Reklamveren bulunamadı.',
            ], 404);
        }

        //DB::beginTransaction();
        try {
            $validatedData = $request->validated();

            $totalBudget = (new CommonHelper)->formatDecimalInput($validatedData['total_budget']);
            $totalHours = (int) $validatedData['total_hours'];
            $bidAmount = (float) ($totalBudget / $totalHours);

            // 1. Temel reklam bilgilerini kaydet.
            $ad->update([
                'advertiser_id' => $validatedData['advertiser_id'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'redirect_url' => $validatedData['redirect_url'],
                'start_date' => Carbon::parse($validatedData['start_date']),
                'show_start_time' => $validatedData['show_start_time'],
                'show_end_time' => $validatedData['show_end_time'],
                'total_budget' => $totalBudget,
                'total_hours' => $totalHours,
                'bid_amount' => $bidAmount,
                'target_country_id' => $validatedData['target_country_id'],
                'target_language_id' => $validatedData['target_language_id'],
            ]);

            // 2. Pivot kayıtlarını ekle.
            $ad->target_cities()->sync($request->input('target_city_ids', []));
            $ad->placements()->sync($request->input('placement_ids', []));
            $ad->target_age_ranges()->sync($request->input('target_age_range_ids', []));
            $ad->target_genders()->sync($request->input('target_gender_ids', []));
            $ad->target_teams()->sync($request->input('target_team_ids', []));
            $ad->target_oses()->sync($request->input('target_os_ids', []));

            // 3. Reklam mediasını kaydet.
            if ($request->hasFile('media_path')) {
                $deleteVideo = false;
                $media = $request->file('media_path');

                $mimeType = $media->getMimeType();
                if (strpos($mimeType, 'image/') === 0) {
                    $deleteVideo = $ad->video_guid ? true : false;

                    $ad->media_type_id = Ad::MEDIA_TYPE_IMAGE;

                    $mediaName = Str::uuid() . '.' . $media->extension();
                    $mediaPath = "ads/{$ad->id}/medias/{$mediaName}";

                    $bunnyCdnService->uploadToStorage($mediaPath, $media->get());

                    $ad->media_path = $mediaPath;
                } elseif (strpos($mimeType, 'video/') === 0) {
                    $deleteVideo = true;

                    $ad->media_type_id = Ad::MEDIA_TYPE_VIDEO;

                    //1. create video
                    $response = $bunnyCdnService->createVideo((new CommonHelper)->limitText($ad->title, 100), $advertiser->collection_uuid);
                    $guid = $response['guid'] ?? null;

                    $ad->video_guid = $guid;

                    //2. upload video
                    $bunnyCdnService->uploadVideo($guid, $request->file('media_path'));
                }

                $ad->save();

                if ($deleteVideo && $ad->video_guid) {
                    $bunnyCdnService->deleteVideo($ad->video_guid);
                }
            }

            // 4. Ads pool cache'ini temizle.
            Cache::forget('ads_pool');

            //DB::commit();
            return response()->json([
                'message' => 'Reklam başarıyla güncellendi.',
                'redirect_url' => route('admin.ads.show', ['id' => $ad->id]),
            ]);
        } catch (Exception $e) {
            //DB::rollBack();
            Log::error('Ad update failed', [
                'exception' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Bir hata oluştu, işlem yapılmadı.',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function statusUpdate(Request $request,  $id): JsonResponse
    {
        $request->validate([
            'status_id.*' => 'required|in:' . implode(',', array_keys(Ad::$statuses)),
        ]);

        $ad = Ad::withTrashed()->find($id);
        if (!$ad) {
            throw new NotFoundHttpException();
        }

        $ad->status_id = $request->input('status_id');
        $ad->save();

        return response()->json([
            'message' => 'Reklam durumu başarıyla güncellendi.'
        ]);
    }

    public function paymentStatusUpdate(Request $request,  $id): JsonResponse
    {
        $request->validate([
            'payment_status_id.*' => 'required|in:' . implode(',', array_keys(Ad::$paymentStatuses)),
        ]);

        $ad = Ad::withTrashed()->find($id);
        if (!$ad) {
            throw new NotFoundHttpException();
        }


        if ($ad->payment_status_id != $request->input('payment_status_id')) {
            $adPayment = $ad->payment;
            if ($request->input('payment_status_id') == Ad::PAYMENT_STATUS_COMPLETED) {
                //ödeme alındı olarak güncellendi ise
                if ($adPayment) {
                    //ve ödeme zaten var ise gidip payment datasını ödendi olarak işaretler
                    $adPayment->update([
                        'total_amount' => $ad->total_budget,
                        'paid_at' => now(),
                        'status_id' => Payment::STATUS_COMPLETED,
                        'channel_id' => Payment::CHANNEL_EFT,
                        'payable_data' => [
                            'id' => $ad->id,
                            'title' => $ad->title,
                            'description' => $ad->description,
                            'start_date' => $ad->start_date,
                            'show_start_time' => $ad->show_start_time,
                            'show_end_time' => $ad->show_end_time,
                            'total_budget' => $ad->total_budget,
                            'total_hours' => $ad->total_hours,
                            'bid_amount' => $ad->bid_amount,
                        ]
                    ]);
                } else {
                    //ödeme kaydı yok ise ödeme kaydı aç
                    $ad->payment()->create([
                        'sub_total' => $ad->total_budget,
                        'discount_amount' => 0,
                        'total_amount' => $ad->total_budget,
                        'paid_at' => now(),
                        'status_id' => Payment::STATUS_COMPLETED,
                        'channel_id' => Payment::CHANNEL_EFT,
                        'advertiser_id' => $ad->advertiser_id,
                        'currency_id' => 1, //TRY
                        'payable_data' => [
                            'id' => $ad->id,
                            'title' => $ad->title,
                            'description' => $ad->description,
                            'start_date' => $ad->start_date,
                            'show_start_time' => $ad->show_start_time,
                            'show_end_time' => $ad->show_end_time,
                            'total_budget' => $ad->total_budget,
                            'total_hours' => $ad->total_hours,
                            'bid_amount' => $ad->bid_amount,
                        ]
                    ]);
                }
            } else {
                //ödeme durumu bekliyor olarak güncellendi ise ve mevcut bir ödeme kaydı var ise ödenmedi olarak güncelle
                if ($adPayment) {
                    $adPayment->update([
                        'paid_at' => null,
                        'status_id' => Payment::STATUS_PENDING
                    ]);
                }
            }

            $ad->payment_status_id = $request->input('payment_status_id');
            $ad->save();
        }

        return response()->json([
            'message' => 'Reklam ödeme durumu başarıyla güncellendi.'
        ]);
    }

    public function getStatsData(Request $request): JsonResponse
    {
        $request->validate([
            'ad_id' => 'required',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $ad = Ad::withTrashed()->find($request->input('ad_id'));
        if (!$ad) {
            throw new NotFoundHttpException();
        }

        $startDate = Carbon::parse($request->input('start_date', Variable::DEFAULT_START_DATE))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();


        $impressionStats = $this->getImpressionStats($ad->id, $startDate, $endDate);
        $clickStats = $this->getClickStats($ad->id, $startDate, $endDate);


        $totalImpressions = $impressionStats['total_impressions'] ?? 0;
        $totalClicks = $clickStats['total_clicks'] ?? 0;
        $clickRate = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;

        $data = [
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'click_rate' => $clickRate,
            'total_completed_views' => $impressionStats['total_completed_views'] ?? 0,
            'total_fair_views' => $impressionStats['total_fair_views'] ?? 0,
        ];

        return response()->json($data);
    }
    protected function getImpressionStats($id, $startDate, $endDate)
    {
        $pipeline = [
            [
                '$match' => [
                    'ad_id' => (int) $id,
                    'impression_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$user_id',
                    'is_completed' => ['$max' => '$is_completed'],
                    'max_duration' => ['$max' => '$duration'],
                ],
            ],
            [
                '$group' => [
                    '_id' => null,
                    'total_impressions' => ['$sum' => 1],
                    'total_completed_views' => [
                        '$sum' => [
                            '$cond' => [['$eq' => ['$is_completed', true]], 1, 0]
                        ],
                    ],
                    'total_fair_views' => [
                        '$sum' => [
                            '$cond' => [
                                [
                                    '$and' => [
                                        ['$gte' => ['$max_duration', 10]],
                                        ['$eq' => ['$is_completed', false]]
                                    ]
                                ],
                                1,
                                0
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $results = AdImpression::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        return [
            'total_impressions' => $results[0]['total_impressions'] ?? 0,
            'total_completed_views' => $results[0]['total_completed_views'] ?? 0,
            'total_fair_views' => $results[0]['total_fair_views'] ?? 0,
        ];
    }
    protected function getClickStats($id, $startDate, $endDate)
    {
        $pipeline = [
            [
                '$match' => [
                    'ad_id' => (int) $id,
                    'click_at' => [
                        '$gte' => new UTCDateTime($startDate),
                        '$lte' => new UTCDateTime($endDate),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$user_id',
                ],
            ],
            [
                '$group' => [
                    '_id' => null,
                    'total_clicks' => ['$sum' => 1],
                ],
            ],
        ];

        $results = AdClick::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        return [
            'total_clicks' => $results[0]['total_clicks'] ?? 0,
        ];
    }
}
