<?php
namespace Weaver\Service;

use Firebase\JWT\JWT;
use Weaver\WeaverConfig;

/**
 * Helper service for creating JWTs using application configuration.
 */
class JwtService
{
    public function __construct(private WeaverConfig $config)
    {
    }

    private function privateKey(): string
    {
        $pem = $this->config->weaverJwtPrivateKey;
        $key = openssl_pkey_get_private($pem);
        if (!$key) {
            throw new \RuntimeException('Invalid private key');
        }
        return $pem;
    }

    /**
     * Sign a set of claims into a JWT.
     */
    public function sign(array $claims, ?int $ttl = null): string
    {
        $now = time();
        $ttl = $ttl ?? $this->config->weaverJwtTtl;
        $payload = array_merge([
            'iss' => $this->config->weaverIssuer,
            'aud' => 'weaver-api',
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $now + $ttl,
        ], $claims);

        $header = [
            'kid' => $this->config->weaverJwtKid,
            'typ' => 'JWT',
            'alg' => 'RS256'
        ];

        return JWT::encode($payload, $this->privateKey(), 'RS256', null, $header);
    }

    /**
     * Return a JSON Web Key representation of the signing key.
     */
    public function jwkFromPrivate(): array
    {
        $pem = $this->privateKey();
        $res = openssl_pkey_get_private($pem);
        $det = openssl_pkey_get_details($res);
        $n = rtrim(strtr(base64_encode($det['rsa']['n']), '+/', '-_'), '=');
        $e = rtrim(strtr(base64_encode($det['rsa']['e']), '+/', '-_'), '=');
        return [
            'kty' => 'RSA',
            'kid' => $this->config->weaverJwtKid,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $n,
            'e' => $e,
        ];
    }
}
