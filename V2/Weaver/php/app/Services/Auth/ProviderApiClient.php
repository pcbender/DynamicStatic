<?php

namespace App\Services\Auth;

use App\Exceptions\ReauthRequired;
use App\Models\LinkedAccount;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class ProviderApiClient
{
    /**
     * Inject ProviderTokenRefresher and auto-refresh tokens
     */
    public function __construct(private ProviderTokenRefresher $refresher)
    {
    }

    /**
     * Fetch Microsoft email via Graph API with auto-refresh token.
     *
     * @param LinkedAccount $account
     * @return string|null
     */
    public function microsoftEmailWithAutoRefresh(LinkedAccount $account): ?string
    {
        $account = $this->refresher->ensureValid('microsoft', $account);

        $token = Crypt::decryptString($account->access_token);
        $resp = Http::withToken($token)->acceptJson()->get(
            'https://graph.microsoft.com/v1.0/me',
            ['$select' => 'mail,userPrincipalName']
        );

        if ($resp->status() === 401) {
            try {
                $account = $this->refresher->refresh('microsoft', $account);
                $token = Crypt::decryptString($account->access_token);
                $resp = Http::withToken($token)->acceptJson()->get(
                    'https://graph.microsoft.com/v1.0/me',
                    ['$select' => 'mail,userPrincipalName']
                );
            } catch (\Throwable) {
                throw new ReauthRequired('microsoft', 'token refresh failed');
            }
        }

        if (!$resp->successful()) {
            return null;
        }

        return $resp->json('mail') ?: $resp->json('userPrincipalName') ?: null;
    }
}
