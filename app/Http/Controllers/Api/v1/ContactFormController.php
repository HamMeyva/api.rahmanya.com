<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Common\ContactForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactFormController extends Controller
{
    public function __invoke(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'full_name' => 'required',
            'phone' => 'nullable',
            'email' => 'required|email',
            'message' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        ContactForm::create([
            'user_id' => $request->user()?->id ?? null,
            'full_name' => $request->input('full_name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'message' => $request->input('message'),
        ]);
    }
}
