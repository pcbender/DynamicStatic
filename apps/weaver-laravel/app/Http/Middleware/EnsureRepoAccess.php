<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class EnsureRepoAccess
{
    public function handle(Request $request, Closure $next)
    {
        $repo = $request->input('repository') ?? $request->route('repository');
        if ($repo && !$request->user()->can('use-repo', $repo)) {
            abort(403, 'Repo not linked to your account.');
        }
        return $next($request);
    }
}
