<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Models\Ad\Advertiser;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\BunnyCdnService;
use Illuminate\Support\Str;

class AdvertiserController extends Controller
{
    public function index()
    {
        return view('admin.pages.advertisers.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Advertiser::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('email', 'ILIKE', "%{$search}%")
                ->orWhere('phone', 'ILIKE', "%{$search}%")
                ->orWhere("address", "LIKE", "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['name', 'type_id', 'email', 'phone', 'status_id', 'address', 'created_at'];

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
            $logo = $item->logo ? $item->logo : assetAdmin('media/svg/avatars/blank.svg');
            $advertiser = "<div class='d-flex align-items-center'>
                <div class='symbol symbol-50px'>
                    <span class='symbol-label' style='background-image:url({$logo});'></span>
                </div>
                <div class='ms-3'>
                    <div class='fs-7 text-gray-800 fw-bold'>{$item->name}</div>
                </div>
            </div>";


            return [
                $advertiser,
                "<span class='badge badge-secondary'>{$item->get_type}</span>",
                $item->email,
                $item->phone,
                "<span class='badge badge-secondary'>{$item->get_status}</span>",
                (new CommonHelper())->limitText($item->address),
                $item->get_created_At,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editAdvertiserBtn'
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

    public function store(Request $request, BunnyCdnService $bunnyCdnService): JsonResponse
    {
        $validatedData = $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'type_id' => 'required|in:' . implode(',', array_keys(Advertiser::$types)),
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string|max:400'
        ], [
            'logo.image' => __('validation.image', ['attribute' => 'Logo']),
            'logo.mimes' => __('validation.mimes', ['attribute' => 'Logo', 'values' => 'jpeg,png,jpg']),
            'logo.max' => __('validation.max.file', ['attribute' => 'Logo', 'max' => '10240']),
            'type_id.required' => __('validation.required', ['attribute' => 'Türü']),
            'name' => __('validation.required', ['attribute' => 'Ad Soyad']),
            'email' => __('validation.required', ['attribute' => 'E-Posta']),
            'phone' => __('validation.required', ['attribute' => 'Telefon']),
            'address' => __('validation.required', ['attribute' => 'Adres']),
        ]);

        unset($validatedData['logo']);
        $validatedData['status_id'] = Advertiser::STATUS_ACTIVE;
        $advertiser = Advertiser::create($validatedData);
 
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');

            $logoName = Str::uuid() . '.' . $logoFile->extension();
            $logoPath = "advertisers/{$advertiser->id}/logo/{$logoName}";

            $bunnyCdnService->uploadToStorage($logoPath, $logoFile->get());

            $advertiser->update([
                'logo_path' => $logoPath
            ]);
        }

        return response()->json([
            'message' => 'Reklam Veren başarıyla eklendi.'
        ]);
    }

    public function show($id)
    {
        return view('admin.pages.advertisers.show', compact('id'));
    }

    public function update(Request $request, $id, BunnyCdnService $bunnyCdnService)
    {
        $validatedData = $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'type_id' => 'required|in:' . implode(',', array_keys(Advertiser::$types)),
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string|max:400',
            'status_id' => 'required|in:' . implode(',', array_keys(Advertiser::$statuses))
        ], [
            'logo.image' => __('validation.image', ['attribute' => 'Logo']),
            'logo.mimes' => __('validation.mimes', ['attribute' => 'Logo', 'values' => 'jpeg,png,jpg']),
            'logo.max' => __('validation.max.file', ['attribute' => 'Logo', 'max' => '10240']),
            'type_id.required' => __('validation.required', ['attribute' => 'Türü']),
            'name' => __('validation.required', ['attribute' => 'Ad Soyad']),
            'email' => __('validation.required', ['attribute' => 'E-Posta']),
            'phone' => __('validation.required', ['attribute' => 'Telefon']),
            'address' => __('validation.required', ['attribute' => 'Adres']),
        ]);
        $advertiser = Advertiser::withTrashed()->find($id);
        if (!$advertiser) {
            return response()->json([
                'message' => 'Reklam Veren bulunamadı.',
            ], 404);
        }


        unset($validatedData['logo']);

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoName = Str::uuid() . '.' . $logoFile->extension();
            $logoPath = "advertisers/{$advertiser->id}/logo/{$logoName}";

            $bunnyCdnService->uploadToStorage($logoPath, $logoFile->get());

            $validatedData['logo_path'] = $logoPath;
        }

        $advertiser->update($validatedData);

        return response()->json([
            'message' => 'Reklam Veren başarıyla güncellendi.'
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->term["term"] ?? '';

        $data = Advertiser::query()
            ->where(function ($query) use ($term) {
                $query->where('email', 'ILIKE', "%{$term}%")
                    ->orWhere('name', 'ILIKE', "%{$term}%");
            })
            ->limit(50)
            ->orderByDesc("id")
            ->get();

        $result = [];
        foreach ($data as $item) {
            $result[] = [
                "id" => $item->id,
                "name" => "{$item->name} <span class='text-muted fs-7'> | {$item->email}</span>",
                "extraParams" => $item
            ];
        }

        return response()->json([
            "items" => $result
        ]);
    }


    public function getCreateAdvertiserForm(): JsonResponse
    {
        return response()->json([
            'view' => view('components.admin.forms.create-edit-advertiser')->render()
        ]);
    }
    public function getEditAdvertiserForm($id): JsonResponse
    {
        $advertiser = Advertiser::withTrashed()->find($id);

        return response()->json([
            'view' => view('components.admin.forms.create-edit-advertiser', ['advertiser' => $advertiser])->render()
        ]);
    }
}
