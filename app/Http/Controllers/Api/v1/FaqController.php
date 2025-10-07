<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Common\Faq;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'response' => Faq::get(),
        ], JsonResponse::HTTP_OK);
    }

    public function show($faqId = null): JsonResponse
    {
        if ($faqId === null) {
            return response()->json([
                'success' => false,
                'response' => 'Faq ID is required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!Faq::find($faqId)) {
            return response()->json([
                'success' => false,
                'response' => 'Faq Not Found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'response' => Faq::find($faqId)
        ],
            JsonResponse::HTTP_OK);
    }
}
