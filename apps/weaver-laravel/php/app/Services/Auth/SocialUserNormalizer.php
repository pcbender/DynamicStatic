<?php

namespace App\Services\Auth;

use Illuminate\Support\Arr;

class SocialUserNormalizer
{
    /**
     * Normalize a Socialite user object into a consistent array.
     *
     * @param string $provider
     * @param mixed $u
     * @return array
     */
    public static function fromSocialite(string $provider, $u): array
    {
        return [
            'sub' => $u->getId() ?? $u->id ?? data_get($u->user, 'sub') ?? data_get($u->getRaw(), 'sub'),
            'email' => $u->getEmail() ?? data_get($u->getRaw(), 'email'),
            'name' => $u->getName() ?? data_get($u->getRaw(), 'name'),
            'avatar' => $u->getAvatar() ?? data_get($u->getRaw(), 'picture'),
            'token' => $u->token ?? data_get($u, 'accessTokenResponseBody.access_token') ?? data_get($u->getRaw(), 'access_token'),
            'refresh_token' => $u->refreshToken ?? data_get($u, 'accessTokenResponseBody.refresh_token') ?? data_get($u->getRaw(), 'refresh_token'),
            'expires_in' => $u->expiresIn ?? data_get($u, 'accessTokenResponseBody.expires_in') ?? data_get($u->getRaw(), 'expires_in'),
            'raw' => $u->getRaw(),
        ];
    }
}
