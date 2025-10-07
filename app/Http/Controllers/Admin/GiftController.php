<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Gift;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\BunnyCdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannelGift;
use App\Http\Requests\Admin\Gift\StoreRequest;
use App\Http\Requests\Admin\Gift\UpdateRequest;
use App\Helpers\CommonHelper;

class GiftController extends Controller
{
    public function index()
    {
        return view('admin.pages.gifts.index');
    }
    public function dataTable(Request $request): JsonResponse
    {
        $query = Gift::query()->with('assets');

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        // Order by
        $columns = ['name', 'price', 'queue', 'is_active', 'has_variants', 'total_usage', 'total_sales'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'queue';
        $orderDir = $request->input('order.0.dir', 'asc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'queue';
            $orderDir = 'asc';
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
            $editUrl = route('admin.gifts.edit', ['gift' => $item->id]);

            $firstAsset = $item->assets()->orderByRaw('team_id ASC NULLS FIRST')->first();
            $image = "<div class='symbol symbol-50px position-relative d-flex align-items-center'>
                <span class='symbol-label' style='background-image:url({$firstAsset?->image_url});'></span>
            </div>";

            $column = "<a href='{$editUrl}' class='d-flex'>
                    {$image}
                    <div class='ms-5 text-gray-800 d-flex flex-column justify-content-center'>
                        {$item->name}
                        <div class='text-muted fs-7 fw-bold'>{$item->slug}</div>
                    </div>
                </a>";

            $price = $item->is_discount ? "<del class='fs-7 text-danger'>{$item->price} Coin</del><div class='fs-6 text-success fw-bold'>{$item->get_final_price} Coin</div>" : "{$item->price} Coin";
            $status = $item->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>';
            $variants = '<span class="badge badge-secondary">' . ($item->has_variants ? 'Evet' : 'Hayır') . '</span>';
            return [
                $column,
                $price,
                $item->queue ?? '-',
                $status,
                $variants,
                $item->total_usage,
                $item->total_sales . ' Coin',
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editUrl' => $editUrl,
                    'showDelete' => true
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
        return view('admin.pages.gifts.create-edit');
    }
    public function store(StoreRequest $request, BunnyCdnService $bunnyCdnService): JsonResponse
    {
        //DB::beginTransaction();
        try {
            $giftAssets = $request->input('asset_repeater_area', []);

            $validatedData = $request->validated();
            $validatedData['is_active'] = $request->boolean('is_active');
            $validatedData['has_variants'] = count($giftAssets) > 1;

            $validatedData['is_discount'] = filled($request->input('discounted_price'));
            if (!$request->input('queue')) {
                $maxQueue = Gift::max('queue');
                $validatedData['queue'] = $maxQueue ? ($maxQueue + 1) : 1;
            }

            $gift = Gift::query()->create($validatedData);

            foreach ($giftAssets as $index => $asset) {
                $data = [
                    'team_id' => $asset['team_id'],
                    'image_path' => null,
                    'video_path' => null,
                ];

                $imageFile = $request->file("asset_repeater_area.{$index}.image_path");
                if ($imageFile) {
                    $imageName = Str::uuid() . '.' . $imageFile->extension();
                    $data['image_path'] = "gifts/{$gift->id}/images/{$imageName}";

                    $bunnyCdnService->uploadToStorage($data['image_path'], $imageFile->get());
                }

                $videoFile = $request->file("asset_repeater_area.{$index}.video_path");
                if ($videoFile) {
                    $videoName = Str::uuid() . '.' . $videoFile->extension();
                    $data['video_path'] = "gifts/{$gift->id}/videos/{$videoName}";

                    $bunnyCdnService->uploadToStorage($data['video_path'], $videoFile->get());
                }

                $gift->assets()->create($data);
            }

            //DB::commit();
            return response()->json([
                'message' => 'Hediye başarıyla oluşturuldu.',
                'redirect' => route('admin.gifts.index')
            ]);
        } catch (Exception $e) {
            //DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
    public function edit(Gift $gift)
    {
        return view('admin.pages.gifts.create-edit', compact('gift'));
    }
    public function update(UpdateRequest $request, Gift $gift, BunnyCdnService $bunnyCdnService): JsonResponse
    {
        //DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $validatedData['is_active'] = $request->boolean('is_active');
            $validatedData['is_discount'] = filled($request->input('discounted_price'));
            if (!$request->input('queue')) {
                $maxQueue = Gift::max('queue');
                $validatedData['queue'] = $maxQueue ? ($maxQueue + 1) : 1;
            }

            $findAsset = $gift->assets()->where('team_id', 1)->first();

            $gift->update($validatedData);

            $giftAssets = $request->input('asset_repeater_area', []);


            /* start: Deleted assets*/
            $incomingTeamIds = collect($giftAssets)->pluck('team_id')->filter()->unique()->values();

            $existingTeamIds = $gift->assets()->pluck('team_id');
            
            $toDeleteTeamIds = $existingTeamIds->diff($incomingTeamIds);
            if ($toDeleteTeamIds->isNotEmpty()) {
                $gift->assets()->whereIn('team_id', $toDeleteTeamIds)->each(function ($asset) use ($bunnyCdnService) {
                    $asset->delete();
                });
            }
            /* end: Deleted assets */

            foreach ($giftAssets as $index => $asset) {
                $findAsset = $gift->assets()->where('team_id', $asset['team_id'])->first();
                $data = [
                    'team_id' => $asset['team_id'],
                    'image_path' => $findAsset->image_path ?? null,
                    'video_path' => $findAsset->video_path ?? null
                ];

                $imageFile = $request->file("asset_repeater_area.{$index}.image_path");
                if ($imageFile) {
                    $imageName = Str::uuid() . '.' . $imageFile->extension();
                    $data['image_path'] = "gifts/{$gift->id}/images/{$imageName}";

                    $bunnyCdnService->uploadToStorage($data['image_path'], $imageFile->get());
                }

                $videoFile = $request->file("asset_repeater_area.{$index}.video_path");
                if ($videoFile) {
                    $videoName = Str::uuid() . '.' . $videoFile->extension();
                    $data['video_path'] = "gifts/{$gift->id}/videos/{$videoName}";

                    $bunnyCdnService->uploadToStorage($data['video_path'], $videoFile->get());
                }


                if ($findAsset) {
                    //güncelle
                    $findAsset->update($data);
                } else {
                    //yeni ekle
                    $gift->assets()->create($data);
                }

                $gift->update([
                    'has_variants' => $gift->assets()->count() > 1
                ]);
            }

            //DB::commit();
            return response()->json([
                'message' => 'Hediye başarıyla güncellendi.',
                'redirect' => route('admin.gifts.edit', ['gift' => $gift->id])
            ]);
        } catch (Exception $e) {
            //DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }

        $validatedData = $request->validated();
        $validatedData['is_active'] = $request->boolean('is_active');
        $validatedData['is_discount'] = filled($request->input('discounted_price'));
        if (!$request->input('queue')) {
            $maxQueue = Gift::max('queue');
            $validatedData['queue'] = $maxQueue ? ($maxQueue + 1) : 1;
        }

        $gift->update($validatedData);

        if ($request->input('image_changed') == 1 && $request->hasFile('image')) {
            $imageName = Str::uuid() . '.' . $request->file("image")->extension();
            $imagePath = "gifts/{$gift->id}/images/{$imageName}";

            $gift->image_path = $imagePath;

            $bunnyCdnService->uploadToStorage($imagePath, $request->file("image")->get());
        }

        if ($request->hasFile('video')) {
            $videoName = Str::uuid() . '.' . $request->file("video")->extension();
            $videoPath = "gifts/{$gift->id}/videos/{$videoName}";

            $gift->video_path = $videoPath;

            $bunnyCdnService->uploadToStorage($videoPath, $request->file("video")->get());
        }


        $gift->save();

        return response()->json([
            'message' => 'Hediye başarıyla güncellendi.',
            'redirect' => route('admin.gifts.index')
        ]);
    }
    public function destroy(string $id)
    {
        $gift = Gift::find($id);
        if (!$gift) {
            return response()->json([
                'message' => "Hediye bulunamadı.",
            ], 404);
        }

        $gift->delete();

        return response()->json([
            'message' => "Hediye başarıyla silindi.",
        ]);
    }
}
