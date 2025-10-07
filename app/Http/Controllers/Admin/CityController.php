<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Common\City;

class CityController extends Controller
{
    
    public function search(Request $request): JsonResponse
    {
        if (!$request->country_id) {
            return response()->json([
                "message" => "Ülke seçiniz."
            ], 404);
        }
        $term = $request->input('term')['term'] ?? null;
        $result = City::query()
            ->where("country_id", $request->input('country_id'))
            ->where("name", "ILIKE", "%" . $term . "%")
            ->get()
            ->map(function ($item) {
                return [
                    "id" => $item->id,
                    "name" => $item->name
                ];
            });

        return response()->json([
            "items" => $result
        ]);
    }
}
