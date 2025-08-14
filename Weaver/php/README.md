# Weaver PHP Implementation

This directory contains the PHP implementation of Weaver, the messenger and gatekeeper component in the Dynamic Static AI CMS architecture.

## Overview

This implementation now uses a simplified service authentication model:
- API Key authentication for service-to-service calls (header `X-API-Key`)
- Optional ephemeral job-scoped session JWT (`weaver_session`) returned on job creation
- Job management & content payload handling (including structured assets)
- GitHub App integration (installation token + persistent file cache)

## Requirements

- **PHP 8.0+** with the following extensions:
  - `curl` - For external API calls
  - `json` - For JSON processing
  - `openssl` - For JWT signing and verification
  - `pdo` - For database operations (if using database storage)
- **Composer** - For dependency management
- **Web Server** - Apache or Nginx with PHP support

## Installation

1. **Install PHP dependencies:**
   ```bash
   composer install
   ```

2. **Configure environment variables:**
  Environment file strategy:
  - `.env.local` (committed) is for local development defaults (no real secrets).
  - `.env` (ignored) is for production deployment secrets.
  - If host contains `localhost` or `127.0.0.1`, loader prefers `.env.local` then falls back to `.env`.
  - Otherwise (non-local), loader prefers `.env` then `.env.local`.
  - You can override with `WEAVER_ENV_FILE=somefile`.

  Create a production `.env` (not committed) or edit the provided `.env.local` for dev:
  ```env
  # Core Auth
  WEAVER_API_KEY=change_me_long_random
  WEAVER_SESSION_JWT_SECRET=optional_session_secret   # if omitted, session tokens disabled

  # GitHub App
  GITHUB_APP_ID=your_github_app_id
  GITHUB_APP_PRIVATE_KEY=../weaver-private.pem   # relative path to PEM
  GITHUB_WEBHOOK_SECRET=optional_webhook_secret

  # (Allowlist removed; GitHub App installation now governs repository authorization)

  # Optional
  LOG_LEVEL=info
  ```

3. **Set up web server configuration:**

  **Apache (.htaccess):**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^api/(.*)$ api/$1 [L]
   
   # Security headers
   Header always set X-Content-Type-Options nosniff
   Header always set X-Frame-Options DENY
   Header always set X-XSS-Protection "1; mode=block"
   ```

  **Nginx:**
   ```nginx
   location /api/ {
     try_files $uri $uri/ /api/index.php?$query_string;
   }
   ```

## Architecture

### Bootstrap System
All entry points include `bootstrap.php`:
```php
require_once __DIR__ . '/../bootstrap.php';
```

The bootstrap:
- Loads environment variables from `.env`
- Initializes the `WeaverConfig` singleton
- Sets up error handling and CORS headers

### Configuration Management
`Weaver\WeaverConfig` provides typed access to configuration:
```php
$config = WeaverConfig::getInstance();
$clientId = $config->googleClientId;
$jwtSecret = $config->weaverJwtSecret;
```

### Auth Components
- **API Key Guard** (`auth_api_key.php`) – validates `X-API-Key` header
- **Session Token** (`session.php`) – issues HS256 JWT scoped to a single job (optional)

## API Endpoints (Current)
```
POST /api/insertJob.php     - Create new publishing job (X-API-Key required)
GET  /api/getJobStatus.php  - Get job status (?id=) (public, optional session bearer)
POST /api/getAllJobs.php    - List jobs (X-API-Key; limit/offset pagination; optional session narrows to job)
POST /api/updateJob.php     - Update job status (X-API-Key; optional session must match job)
GET  /api/jobArtifact.php   - Retrieve job payload (X-API-Key; optional HMAC + session)
GET  /api/health.php        - Health & env diagnostics (no auth; no secrets)
```

## Usage Examples

### Quick Testing (API Key + Session)
Assume: `WEAVER_API_KEY=dev-key` and server running at `http://localhost:8080`.

```bash
# Create job (ContentJobPayload)
curl -s -X POST http://localhost:8080/api/insertJob.php \
  -H "X-API-Key: dev-key" \
  -H "Content-Type: application/json" \
  -d '{
    "payload": {
      "type":"article",
      "metadata":{"title":"Hello World"},
      "content":{"format":"markdown","body":"# Title\n\n![hero](asset:hero.png)"},
      "assets":[{"type":"image","name":"hero.png","url":"data:image/png;base64,iVBORw0...","placement":"hero"}],
      "deployment":{"repository":"o/r","filename":"hello-world.html"}
    }
  }'

# Sample response:
# {"status":"success","job_id":"ab12cd34ef56...","weaver_session":"<JWT>"}

JOB_ID=ab12cd34ef56            # substitute from response
SESSION=<JWT_FROM_RESPONSE>

# Get job status (public; optionally add Authorization: Bearer $SESSION)
curl -s "http://localhost:8080/api/getJobStatus.php?id=$JOB_ID"

# Update job status (using API key + session)
curl -s -X POST http://localhost:8080/api/updateJob.php \
  -H "X-API-Key: dev-key" \
  -H "Authorization: Bearer $SESSION" \
  -H "Content-Type: application/json" \
  -d '{"id":"'$JOB_ID'","status":"completed"}'

# List jobs (pagination)
curl -s -X POST http://localhost:8080/api/getAllJobs.php \
  -H "X-API-Key: dev-key" \
  -H "Content-Type: application/json" \
  -d '{"status":"*","limit":10,"offset":0}'

# Fetch artifact (payload)
curl -s "http://localhost:8080/api/jobArtifact.php?id=$JOB_ID" -H "X-API-Key: dev-key"
```

> Legacy OAuth flow and scope-based bearer tokens have been removed. Any references in older docs should be disregarded.

## Development

### Local Development Setup
```bash
# Start PHP development server
php -S localhost:8080 -t .

# Or use with specific bootstrap
php -S localhost:8080 -t . -d auto_prepend_file=bootstrap.php
```

### Running Lightweight Tests
Current repo includes a minimal script test (`tests/InsertJobAuthTest.php`) that simulates API calls directly.
```bash
php tests/InsertJobAuthTest.php
```
Future: reinstate full PHPUnit suite with additional coverage (assets, pagination, artifact retrieval).

### Debugging
Enable error reporting in development:
```php
// In bootstrap.php for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Security Considerations

### Session Token Security
- HS256 JWT bound to a single job (claims: job_id, owner, repo)
- Expires (default: 30 min) – controlled by issuance TTL
- Optional; absence of `WEAVER_SESSION_JWT_SECRET` disables session issuance

### Input Validation
- All inputs are sanitized and validated
- SQL injection protection via prepared statements
- CSRF protection for state parameters

### CORS Configuration
Configured in `bootstrap.php`; update allowed origin and headers as required for your deployment.

## Deployment

### Apache/cPanel Hosting
1. Upload files to web root
2. Configure `.env` file with production values
3. Ensure mod_rewrite is enabled
4. Set appropriate file permissions (644 for files, 755 for directories)

### Docker Deployment
```dockerfile
FROM php:8.0-apache

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql curl json

# Copy application
COPY . /var/www/html/

# Install Composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Configure Apache
RUN a2enmod rewrite
```

### Environment-Specific Configuration
```env
# Production
WEAVER_JWT_SECRET=your_strong_production_secret
GOOGLE_CLIENT_SECRET=production_google_secret

# Development  
WEAVER_JWT_SECRET=dev_secret_key
GOOGLE_CLIENT_SECRET=dev_google_secret
```

## Monitoring and Logging

### Health Check Endpoint
```php
// api/health.php
echo json_encode(['status' => 'healthy', 'timestamp' => time()]);
```

### Error Logging
```php
// Logs to PHP error log
error_log("Weaver Error: " . $errorMessage);

// Custom logging
file_put_contents('logs/weaver.log', date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
```

## Troubleshooting

### Common Issues

**"Configuration not found"**
- Ensure `.env` file exists in project root (two levels up)
- Verify environment variable names match expected keys

**"Invalid JWT signature"**
- Check `WEAVER_JWT_SECRET` configuration
- Ensure consistent secret across all instances

**"Google OAuth failed"**
- Verify `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`
- Check redirect URI configuration in Google Console
- Ensure HTTPS in production

**"Database connection failed"**
- Check database credentials in `.env`
- Verify database server is accessible
- Run database migrations if applicable

### Debug Mode
```env
# Enable debug logging
PHP_DEBUG=true
LOG_LEVEL=debug
```

## Performance Optimization

### Caching
- Implement Redis/Memcached for session storage
- Cache Google OAuth tokens
- Use APCu for configuration caching

### Database Optimization
- Index frequently queried columns (`job_id`, `status`, `created_by_sub`)
- Use connection pooling
- Implement read replicas for scaling

### Web Server Tuning
- Enable gzip compression
- Configure proper cache headers
- Use CDN for static assets
