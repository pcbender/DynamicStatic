<?php

namespace App\Providers;

use App\Models\Project;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Project::class => ProjectPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('use-repo', function ($user, string $repo) {
            return app(\App\Services\RepoAccess::class)->userCanUse($user, $repo);
        });
    }
}