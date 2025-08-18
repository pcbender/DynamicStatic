<?php

namespace App\Services\Auth;

use App\Models\LinkedAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

final class ProviderTokenRefresher
{
    public function ensureValid(string $provider, LinkedAccount $account): LinkedAccount
    {
        if ($account->expires_at && Carbon::now()->diffInSeconds($account->expires_at, false) <= 60) {
            return $this->refresh($provider, $account);
        }
        return $account;
    }

    public function refresh(string $provider, LinkedAccount $account): LinkedAccount
    {
        $refreshEnc = $account->refresh_token;
        if (!$refreshEnc) {
            throw new \RuntimeException('No refresh token available for '.$provider);
        }
        $refreshToken = Crypt::decryptString($refreshEnc);

        $payload = match ($provider) {
            'google' => [
                'client_id'     => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
            'microsoft' => [
                'client_id'     => env('MICROSOFT_CLIENT_ID'),
                'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
            default => throw new \InvalidArgumentException('Unsupported provider'),
        };

        $tokenUrl = $this->tokenUrl($provider);

        $resp = Http::asForm()->acceptJson()->post($tokenUrl, $payload);
        if (!$resp->successful()) {
            throw new \RuntimeException('Token refresh failed for '.$provider);
        }

        $json = $resp->json();

        $newAccess  = Arr::get($json, 'access_token');
        $newRefresh = Arr::get($json, 'refresh_token');
        $expiresIn  = (int) Arr::get($json, 'expires_in', 3600);

        if (!$newAccess) {
            throw new \RuntimeException('Provider did not return access_token');
        }

        $refreshToStore = $newRefresh
            ? Crypt::encryptString($newRefresh)
            : $account->refresh_token;

        $account->access_token = Crypt::encryptString($newAccess);
        $account->refresh_token = $refreshToStore;
        $account->expires_at = Carbon::now()->addSeconds($expiresIn);
        $account->save();

        return $account->refresh();
    }

    private function tokenUrl(string $provider): string
    {
        if ($provider === 'google') {
            return config('oauth.google.token_url');
        }
        if ($provider === 'microsoft') {
            $tenant = config('oauth.microsoft.tenant', 'common');
            return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
        }
        throw new \InvalidArgumentException('Unsupported provider');
    }
}
