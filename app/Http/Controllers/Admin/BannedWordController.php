<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\BannedWord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class BannedWordController extends Controller
{
    public function index()
    {
        return view('admin.pages.settings.banned-words.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = BannedWord::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('word', 'ILIKE', "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['id', 'word'];

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
            return [
                $item->id,
                $item->word,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editBtn',
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

    public function store(Request $request)
    {
        $request->validate([
            'word' => 'required|string|max:255|unique:banned_words,word',
        ], [
            'word.required' => 'Kelime zorunlu.',
            'word.unique' => 'Bu kelime zaten mevcut.',
        ]);

        try {
            BannedWord::create([
                'word' => $request->word,
            ]);

            return response()->json([
                'message' => 'Kelime başarıyla eklendi.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => "Bir sorun oluştu. Error: {$e->getMessage()}"
            ], 500);
        }
    }

    public function show(string $id)
    {
        $bannedWord = BannedWord::find($id);
        if (!$bannedWord) {
            return response()->json([
                'message' => 'Kelime bulunamadı.'
            ], 404);
        }

        return response()->json([
            'data' => $bannedWord
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'word' => 'required|string|max:255|unique:banned_words,word',
        ], [
            'word.required' => 'Kelime zorunlu.',
            'word.unique' => 'Bu kelime zaten mevcut.',
        ]);

        try {
            $bannedWord = BannedWord::find($id);
            if (!$bannedWord) {
                return response()->json([
                    'message' => 'Kelime bulunamadı.'
                ], 404);
            }

            $bannedWord->update([
                'word' => $request->word,
            ]);

            return response()->json([
                'message' => 'Kelime başarıyla güncellendi.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => "Bir sorun oluştu. Error: {$e->getMessage()}"
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $bannedWord = BannedWord::find($id);
        if (!$bannedWord) {
            return response()->json([
                'message' => 'Kelime bulunamadı.'
            ], 404);
        }

        $bannedWord->delete();

        return response()->json([
            'message' => 'Kelime başarıyla silindi.'
        ]);
    }
}