<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

final class EnsureProfileComplete
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user && empty($user->name)) {
            return redirect('/onboarding');
        }
        return $next($request);
    }
}
