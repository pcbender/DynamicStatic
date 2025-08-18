<?php

namespace App\Services\Auth;

/**
 * OAuthService handles provider configuration, redirects, callbacks, and user profile normalization.
 */
class OAuthService
{
    // Build provider configuration from environment variables
    public function buildProviderConfig(string $provider): array
    {
        // ...implementation stub...
    }

    // Handle redirect to provider
    public function redirect(string $provider): string
    {
        // ...implementation stub...
    }

    // Handle callback from provider
    public function callback(string $provider, array $data): array
    {
        // ...implementation stub...
    }

    // Normalize user profile data
    public function normalizeProfile(array $profile): array
    {
        // ...implementation stub...
    }

    // Upsert linked account
    public function upsertLinkedAccount(array $profile): void
    {
        // ...implementation stub...
    }

    // Mint JWT for authenticated user
    public function mintJWT(int $userId, string $email, array $scopes = []): string
    {
        // ...implementation stub...
    }
}
