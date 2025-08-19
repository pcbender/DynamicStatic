<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = JWT::encode(
            ['sub' => $user->id, 'email' => $user->email],
            env('APP_KEY'),
            'HS256'
        );

        return response()->json(['token' => $token]);
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();
        return $this->handleOAuthCallback($googleUser);
    }

    public function handleMicrosoftCallback()
    {
        $microsoftUser = Socialite::driver('microsoft')->user();
        return $this->handleOAuthCallback($microsoftUser);
    }

    private function handleOAuthCallback($socialUser)
    {
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            ['name' => $socialUser->getName(), 'password' => Hash::make(uniqid())]
        );

        $token = JWT::encode(
            ['sub' => $user->id, 'email' => $user->email],
            env('APP_KEY'),
            'HS256'
        );

        return response()->json(['token' => $token]);
    }
}
