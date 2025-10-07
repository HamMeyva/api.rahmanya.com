<?php

namespace App\Http\Controllers\Admin;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AppSettingController extends Controller
{
    public function index()
    {
        $appSettings = AppSetting::orderBy('id')->get();
        return view('admin.pages.settings.app-settings.index', compact('appSettings'));
    }

    public function update(Request $request): JsonResponse
    {
        foreach ($request->all() as $key => $value) {
            AppSetting::set($key, $value);
        }

        return response()->json([
            'message' => 'Ayarlar başarıyla güncellendi'
        ]);
    }
}
