Gate::define('use-repo', function ($user, string $repo) {
    return app(\App\Services\RepoAccess::class)->userCanUse($user, $repo);
});