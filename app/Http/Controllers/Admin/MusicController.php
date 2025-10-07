<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\View\View;
use App\Models\Music\Music;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\BunnyCdnService;

class MusicController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.musics.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Music::query()->with(['artist', 'category']);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere("slug", "LIKE", "%{$search}%");
        }

        // Filters
        if ($request->filled('artist_id')) {
            $query->where('artist_id', $request->input('artist_id'));
        }

        if ($request->filled('music_category_id')) {
            $query->where('music_category_id', $request->input('music_category_id'));
        }

        // Order by
        $columns = ['id', 'name', 'artist_id', 'music_category_id'];

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

        $authUser = $request->user();
        $data = $list->map(function ($item) use ($authUser) {
            $icon = "<a href='{$item->music_url}' target='_blank' class='d-flex justify-content-center align-items-center w-40px h-40px text-hover-primary cursor-pointer rounded-circle border border-2 border-light'><i class='fa fa-music'></i></a>";
            $column = "<div class='d-flex align-items-center'>
                {$icon}
                <div class='ms-5 d-flex flex-column justify-content-center'>
                    <span class='text-dark'>{$item->title}</span>                 
                    <div class='text-muted fs-7 fw-bold'>{$item->slug}</div>
                </div>
            </div>";

            return [
                $item->id,
                $column,
                "<span class='badge badge-secondary'>{$item?->artist?->name}</span>",
                "<span class='badge badge-secondary'>{$item?->category?->name}</span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => $authUser->can('music update'),
                    'editBtnClass' => 'editBtn',
                    'showDelete' => $authUser->can('music delete'),
                    'deleteBtnClass' => 'deleteBtn',
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
        $request->validate([
            'file' => 'required|file|mimetypes:audio/mpeg,audio/mp3',
            'title' => 'required|string|max:255',
            'artist_id' => 'required|exists:artists,id',
            'music_category_id' => 'required|exists:music_categories,id',
        ], [
            'file.required' => __('validation.required', ['attribute' => 'Müzik Dosyası']),
            'file.mimetypes' => __('validation.mimetypes', ['attribute' => 'Müzik Dosyası', 'mimetypes' => 'audio/mpeg,audio/mp3']),
            'title.required' => __('validation.required', ['attribute' => 'Müzik Adı']),
            'title.max' => __('validation.max.string', ['attribute' => 'Müzik Adı', 'max' => 255]),
            'artist_id.required' => __('validation.required', ['attribute' => 'Sanatçı']),
            'artist_id.exists' => __('validation.exists', ['attribute' => 'Sanatçı']),
            'music_category_id.required' => __('validation.required', ['attribute' => 'Müzik Kategorisi']),
            'music_category_id.exists' => __('validation.exists', ['attribute' => 'Müzik Kategorisi']),
        ]);

        try {
            $music = Music::create([
                'title' => $request->input('title'),
                'artist_id' => $request->input('artist_id'),
                'music_category_id' => $request->input('music_category_id'),
            ]);


            // Upload file
            $file = $request->file("file");
            $fileName = Str::uuid() . '.' . $file->extension();
            $music->music_path = "musics/{$music->id}/{$fileName}";

            $bunnyCdnService->uploadToStorage($music->music_path, $file->get());

            $music->save();

            return response()->json([
                'message' => 'Müzik eklendi.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id, BunnyCdnService $bunnyCdnService): JsonResponse
    {
        $music = Music::find($id);
        if (!$music) {
            return response()->json([
                'error' => 'Müzik bulunamadı.',
            ], 404);
        }

        $request->validate([
            'file' => 'nullable|file|mimetypes:audio/mpeg,audio/mp3',
            'title' => 'required|string|max:255',
            'artist_id' => 'required|exists:artists,id',
            'music_category_id' => 'required|exists:music_categories,id',
        ], [
            'file.mimetypes' => __('validation.mimetypes', ['attribute' => 'Müzik Dosyası', 'mimetypes' => 'audio/mpeg,audio/mp3']),
            'title.required' => __('validation.required', ['attribute' => 'Müzik Adı']),
            'title.max' => __('validation.max.string', ['attribute' => 'Müzik Adı', 'max' => 255]),
            'artist_id.required' => __('validation.required', ['attribute' => 'Sanatçı']),
            'artist_id.exists' => __('validation.exists', ['attribute' => 'Sanatçı']),
            'music_category_id.required' => __('validation.required', ['attribute' => 'Müzik Kategorisi']),
            'music_category_id.exists' => __('validation.exists', ['attribute' => 'Müzik Kategorisi']),
        ]);

        try {
            $music->update([
                'title' => $request->input('title'),
                'artist_id' => $request->input('artist_id'),
                'music_category_id' => $request->input('music_category_id'),
            ]);

            // Upload file
            $file = $request->file("file");
            if ($file) {
                $fileName = Str::uuid() . '.' . $file->extension();
                $music->music_path = "musics/{$music->id}/{$fileName}";

                $bunnyCdnService->uploadToStorage($music->music_path, $file->get());

                $music->save();
            }

            return response()->json([
                'message' => 'Müzik güncellendi.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function show($id): JsonResponse
    {
        $music = Music::find($id);
        if (!$music) {
            return response()->json([
                'error' => 'Müzik bulunamadı.',
            ], 404);
        }


        return response()->json([
            'data' => $music,
        ]);
    }

    public function delete($id): JsonResponse
    {
        $music = Music::find($id);
        if (!$music) {
            return response()->json([
                'error' => 'Müzik bulunamadı.',
            ], 404);
        }

        $music->delete();

        return response()->json([
            'message' => 'Müzik silindi.',
        ]);
    }
}
