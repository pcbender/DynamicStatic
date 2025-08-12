# Weaver PHP Backend

This directory contains the minimal PHP services that power OAuth and the job API.

## Configuration

Configuration values are loaded from environment variables. You can provide them
via a `.env` file in the repository root using the keys in [`../.env.example`](../.env.example).
`Weaver\WeaverConfig` exposes these settings as typed read-only properties.

## Bootstrap

Public entry points should include `bootstrap.php`:

```php
require_once __DIR__ . '/../bootstrap.php';
```

The bootstrap loads the `.env` file, constructs the configuration object and
initializes the configuration singleton. Access it anywhere via
`Weaver\WeaverConfig::getInstance()`.

## Services

- `Weaver\Service\GoogleOAuthService` handles Google OAuth code exchanges and
  ID token validation.
- `Weaver\Service\JwtService` signs and exposes JWTs for the API.

These classes should be constructed with the shared configuration instance.
