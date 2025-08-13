<?php
namespace Weaver\Service;

use GuzzleHttp\Client;
use Weaver\User;
use Weaver\WeaverConfig;

/**
 * Minimal Google OAuth helper.
 */
class GoogleOAuthService
{
    private Client $client;

    public function __construct(private WeaverConfig $config, ?Client $client = null)
    {
        $this->client = $client ?? new Client(['timeout' => 10]);
    }

    /**
     * Build the Google OAuth authorization URL.
     */
    public function buildAuthUrl(string $state, string $scope = 'openid email profile'): string
    {
        $params = http_build_query([
            'client_id' => $this->config->googleClientId,
            'redirect_uri' => $this->config->googleRedirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /**
     * Exchange a Google authorization code for tokens.
     *
     * @return array<string,mixed>
     */
    public function exchangeCode(string $code): array
    {
        $resp = $this->client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'code' => $code,
                'client_id' => $this->config->googleClientId,
                'client_secret' => $this->config->googleClientSecret,
                'redirect_uri' => $this->config->googleRedirectUri,
                'grant_type' => 'authorization_code',
            ],
        ]);
        return json_decode((string) $resp->getBody(), true);
    }

    /**
     * Validate an ID token returned by Google and return the user info.
     *
     * @throws \RuntimeException when validation fails
     */
    public function validateIdToken(string $idToken): User
    {
        [, $claims] = $this->parseJwt($idToken);
        if (($claims['aud'] ?? null) !== $this->config->googleClientId) {
            throw new \RuntimeException('aud mismatch');
        }
        if (!in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)) {
            throw new \RuntimeException('iss mismatch');
        }
        $sub = $claims['sub'] ?? null;
        if (!$sub) {
            throw new \RuntimeException('no sub');
        }
        return new User($sub, $claims['email'] ?? null, $claims);
    }

    private function parseJwt(string $jwt): array
    {
        [$h, $p, $s] = explode('.', $jwt);
        return [
            json_decode(base64_decode(strtr($h, '-_', '+/')), true),
            json_decode(base64_decode(strtr($p, '-_', '+/')), true),
        ];
    }
}
