<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Common\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'response' => Page::get(),
        ], JsonResponse::HTTP_OK);
    }

    public function show($pageId = null): JsonResponse
    {
        if ($pageId === null) {
            return response()->json([
                'success' => false,
                'response' => 'Page ID is required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!Page::find($pageId)) {
            return response()->json([
                'success' => false,
                'response' => 'Page Not Found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'response' => Page::find($pageId)
        ],
        JsonResponse::HTTP_OK);
    }
}
