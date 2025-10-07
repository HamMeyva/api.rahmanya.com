<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Gift;

class GiftController extends Controller
{

    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'response' => Gift::get(),
        ]);
    }
}
