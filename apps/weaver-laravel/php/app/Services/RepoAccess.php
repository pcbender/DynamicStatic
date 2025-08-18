<?php
namespace App\Services;

final class RepoAccess
{
    public function userCanUse($user, string $repo): bool
    {
        // Implement your per-repo lookup logic here
        return true; // Stub implementation
    }
}
