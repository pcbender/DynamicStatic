<?php

namespace App\Services\GitHub;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use RuntimeException;

class GitHubAppClient
{
    private GitHubAppKeyLoader $keyLoader;

    public function __construct(GitHubAppKeyLoader $keyLoader)
    {
        $this->keyLoader = $keyLoader;
    }

    public function buildAppJwt(): string
    {
        $privateKeyPem = $this->keyLoader->loadPrivateKey();
        $signer = new Sha256();
        $now = new DateTimeImmutable('-60 seconds');
        $exp = $now->add(new DateInterval('PT9M'));
        $iss = env('GITHUB_APP_CLIENT_ID') ?: env('GITHUB_APP_ID');
        if (!$iss) {
            throw new RuntimeException('Missing GITHUB_APP_CLIENT_ID / GITHUB_APP_ID');
        }
        $config = Configuration::forAsymmetricSigner($signer, InMemory::plainText($privateKeyPem), InMemory::plainText($privateKeyPem));
        $token = $config->builder()
            ->issuedBy($iss)
            ->issuedAt($now)
            ->expiresAt($exp)
            ->getToken($config->signer(), $config->signingKey());
        return $token->toString();
    }

    public function resolveInstallationAndMint(string $owner, string $repo): array
    {
        $appJwt = $this->buildAppJwt();
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'DynamicStatic-GitHubApp',
            'Authorization' => 'Bearer ' . $appJwt,
        ];
        $base = rtrim(env('GITHUB_API_BASE', 'https://api.github.com'), '/');
        $installationResponse = Http::withHeaders($headers)
            ->timeout(15)->connectTimeout(5)
            ->get($base . "/repos/{$owner}/{$repo}/installation");
        if ($installationResponse->status() === 404) {
            throw new RuntimeException('Installation not found for repository (ensure App is installed).');
        }
        if (!$installationResponse->successful()) {
            throw new RuntimeException('Failed to resolve installation (status ' . $installationResponse->status() . ').');
        }
        $installationId = $installationResponse->json('id');
        if (!$installationId) {
            throw new RuntimeException('Installation id missing in response.');
        }
        $tokenResp = Http::withHeaders($headers)
            ->timeout(15)->connectTimeout(5)
            ->post($base . "/app/installations/{$installationId}/access_tokens", []);
        if (!$tokenResp->successful()) {
            throw new RuntimeException('Failed to create installation token (status ' . $tokenResp->status() . ').');
        }
        $expires = $tokenResp->json('expires_at');
        if (!$expires) {
            throw new RuntimeException('Token expiry missing in response.');
        }
        return [
            'installation_id' => $installationId,
            'token_expires_at' => $expires,
        ];
    }
}
