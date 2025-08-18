<?php

namespace App\Services\Auth;

/**
 * TokenIssuer handles JWT creation and setting cookies.
 */
class TokenIssuer
{
    // Issue a signed JWT
    public function issueToken(int $userId, string $email, array $scopes = [], int $expiry = 43200): string
    {
        // ...implementation stub...
    }

    // Set the JWT as a cookie
    public function setCookie(string $token): void
    {
        // ...implementation stub...
    }
}
