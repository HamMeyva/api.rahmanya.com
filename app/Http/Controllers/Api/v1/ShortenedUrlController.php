<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\UrlShortener;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortenedUrlController
{

    public function shorten(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $shortUrl = UrlShortener::shorten($request->url);

        return response()->json([
            'success' => true,
            'short_url' => url($shortUrl->short_code),
            'original_url' => $shortUrl->original_url,
        ]);
    }

    public function redirect($shortCode)
    {
        $shortUrl = UrlShortener::resolve($shortCode);

        return redirect($shortUrl->original_url);
    }

}
