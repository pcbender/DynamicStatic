<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TokenIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

/**
 * SelfAuthController handles login and registration for self-managed accounts.
 */
class SelfAuthController extends Controller
{
    protected TokenIssuer $tokenIssuer;

    public function __construct(TokenIssuer $tokenIssuer)
    {
        $this->tokenIssuer = $tokenIssuer;
    }

    // Handle user login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return $this->redirectAfterAuth();
    }

    // Handle user registration
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = User::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);
        Auth::login($user);
        return $this->redirectAfterAuth();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    protected function redirectAfterAuth()
    {
        $user = Auth::user();
        return redirect()->to($user->project ? route('dashboard') : route('project.setup'));
    }
}
