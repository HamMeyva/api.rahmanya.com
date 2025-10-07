<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login()
    {
        return view('admin.pages.auth.login');
    }

    public function loginPost(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // Find the admin by email
        $admin = Admin::where('email', $credentials['email'])->first();

        // Check if admin exists and password is correct
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            // Manually log in the admin
            Auth::guard('admin')->login($admin);

            // Regenerate the session
            $request->session()->regenerate();

            // Update the session with polymorphic relationship
            if ($request->hasSession()) {
                $sessionId = $request->session()->getId();
                $session = \DB::table('sessions')->where('id', $sessionId)->first();

                if ($session) {
                    \DB::table('sessions')
                        ->where('id', $sessionId)
                        ->update([
                            'sessionable_id' => $admin->id,
                            'sessionable_type' => Admin::class
                        ]);
                }
            }

            return response()->json([
                'redirectUrl' => redirect()->route('admin.dashboard.index')->getTargetUrl()
            ]);
        }

        return response()->json([
            "message" => 'Geçersiz kullanıcı bilgileri'
        ], 401);
    }

    public function logoutPost(): RedirectResponse
    {
        auth('admin')->logout();

        return redirect()->route('admin.auth.login');
    }
}
