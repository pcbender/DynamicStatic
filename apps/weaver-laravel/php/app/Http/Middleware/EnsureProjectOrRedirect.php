<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectOrRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            $hasProject = (bool) $user->project;
            $path = $request->path();
            $isProjectSetupRoute = $request->routeIs('project.setup') || $request->routeIs('project.setup.store');
            if (!$hasProject) {
                if (!$isProjectSetupRoute && !$this->isAllowedDuringOnboarding($path)) {
                    return redirect()->route('project.setup');
                }
            } else {
                if ($isProjectSetupRoute) {
                    return redirect()->route('dashboard');
                }
            }
        }
        return $next($request);
    }

    protected function isAllowedDuringOnboarding(string $path): bool
    {
        return preg_match('#^(login|register|auth/|logout|assets/|js/|css/|images/|_debugbar)#', $path) === 1;
    }
}
