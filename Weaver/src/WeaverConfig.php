<?php
namespace Weaver;

use Dotenv\Dotenv;
use InvalidArgumentException;

/**
 * Application configuration loaded from environment variables.
 *
 * @property-read string $weaverOauthClientId OAuth client ID used by Weaver actions.
 * @property-read string $weaverOauthClientSecret OAuth client secret for Weaver actions.
 * @property-read string $googleClientId Google OAuth client ID.
 * @property-read string $googleClientSecret Google OAuth client secret.
 * @property-read string $googleRedirectUri Callback URL registered with Google.
 * @property-read string $weaverJwtKid Key ID used in JWT headers.
 * @property-read string $weaverJwtPrivateKey PEM encoded private key for signing JWTs.
 * @property-read string $weaverIssuer Base URL that appears in JWT "iss" claims.
 * @property-read int    $weaverJwtTtl Default lifetime for access tokens.
 * @property-read int    $weaverRefreshTtl Default lifetime for refresh tokens.
 * @property-read ?string $weaverHmacSecret Optional secret for webhook HMAC verification.
 */
class WeaverConfig
{
    public readonly string $weaverOauthClientId;
    public readonly string $weaverOauthClientSecret;
    public readonly string $googleClientId;
    public readonly string $googleClientSecret;
    public readonly string $googleRedirectUri;
    public readonly string $weaverJwtKid;
    public readonly string $weaverJwtPrivateKey;
    public readonly string $weaverIssuer;
    public readonly int $weaverJwtTtl;
    public readonly int $weaverRefreshTtl;
    public readonly ?string $weaverHmacSecret;

    private function __construct(
        string $weaverOauthClientId,
        string $weaverOauthClientSecret,
        string $googleClientId,
        string $googleClientSecret,
        string $googleRedirectUri,
        string $weaverJwtKid,
        string $weaverJwtPrivateKey,
        string $weaverIssuer,
        int $weaverJwtTtl,
        int $weaverRefreshTtl,
        ?string $weaverHmacSecret
    ) {
        $this->weaverOauthClientId = $weaverOauthClientId;
        $this->weaverOauthClientSecret = $weaverOauthClientSecret;
        $this->googleClientId = $googleClientId;
        $this->googleClientSecret = $googleClientSecret;
        $this->googleRedirectUri = $googleRedirectUri;
        $this->weaverJwtKid = $weaverJwtKid;
        $this->weaverJwtPrivateKey = $weaverJwtPrivateKey;
        $this->weaverIssuer = $weaverIssuer;
        $this->weaverJwtTtl = $weaverJwtTtl;
        $this->weaverRefreshTtl = $weaverRefreshTtl;
        $this->weaverHmacSecret = $weaverHmacSecret;
    }

    /**
     * Locate the directory that may contain a .env file.
     */
    private static function rootDir(): string
    {
        $root = dirname(__DIR__, 2);
        if (!file_exists($root . '/.env')) {
            $root = dirname(__DIR__);
        }
        return $root;
    }

    /**
     * Build configuration from environment variables. If a .env file exists
     * and variables are missing, it will be loaded using vlucas/php-dotenv.
     *
     * @throws InvalidArgumentException if any required setting is absent.
     */
    public static function fromEnvironment(): self
    {
        $root = self::rootDir();
        if (file_exists($root . '/.env') && empty($_ENV['WEAVER_OAUTH_CLIENT_ID'])) {
            Dotenv::createImmutable($root)->safeLoad();
        }

        $env = $_ENV + $_SERVER;
        $required = [
            'WEAVER_OAUTH_CLIENT_ID',
            'WEAVER_OAUTH_CLIENT_SECRET',
            'GOOGLE_CLIENT_ID',
            'GOOGLE_CLIENT_SECRET',
            'GOOGLE_REDIRECT_URI',
            'WEAVER_JWT_KID',
            'WEAVER_JWT_PRIVATE_KEY'
        ];

        foreach ($required as $key) {
            if (!isset($env[$key]) || $env[$key] === '') {
                throw new InvalidArgumentException("Missing required config: {$key}");
            }
        }

        $issuer = $env['WEAVER_ISSUER'] ?? self::detectIssuer();

        return new self(
            $env['WEAVER_OAUTH_CLIENT_ID'],
            $env['WEAVER_OAUTH_CLIENT_SECRET'],
            $env['GOOGLE_CLIENT_ID'],
            $env['GOOGLE_CLIENT_SECRET'],
            $env['GOOGLE_REDIRECT_URI'],
            $env['WEAVER_JWT_KID'],
            $env['WEAVER_JWT_PRIVATE_KEY'],
            $issuer,
            (int)($env['WEAVER_JWT_TTL'] ?? 1800),
            (int)($env['WEAVER_REFRESH_TTL'] ?? 1209600),
            $env['WEAVER_HMAC_SECRET'] ?? null
        );
    }

    /**
     * Determine the issuer from the current request when not explicitly set.
     */
    private static function detectIssuer(): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . '://' . $host;
    }
}
