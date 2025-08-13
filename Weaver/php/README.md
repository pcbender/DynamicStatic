# Weaver PHP Implementation

This directory contains the PHP implementation of Weaver, the messenger and gatekeeper component in the Dynamic Static AI CMS architecture.

## 🕸️ Overview

The PHP implementation provides a production-ready backend service that handles:
- OAuth 2.0 authentication with Google identity provider
- JWT-based API authorization with scope management
- Job management system for content publishing workflows
- GitHub App integration for secure repository operations

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
   Create a `.env` file in the project root (two levels up):
   ```env
   # Google OAuth Configuration
   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   
   # Weaver OAuth Settings
   WEAVER_OAUTH_CLIENT_ID=dsb-gpt
   WEAVER_JWT_SECRET=your_jwt_secret
   WEAVER_JWT_EXPIRY=3600
   
   # GitHub App Configuration
   GITHUB_APP_ID=your_github_app_id
   GITHUB_APP_PRIVATE_KEY=../weaver-private.pem
   GITHUB_WEBHOOK_SECRET=your_webhook_secret
   ```

3. **Set up web server configuration:**

   **Apache (.htaccess):**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^api/(.*)$ api/$1 [L]
   RewriteRule ^oauth/(.*)$ oauth/$1 [L]
   
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
   
   location /oauth/ {
       try_files $uri $uri/ /oauth/index.php?$query_string;
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

### Service Layer
- **`GoogleOAuthService`** - Handles Google OAuth flows and token validation
- **`JwtService`** - Creates and validates JWT tokens with scope-based permissions

## API Endpoints

### OAuth Flow
```
GET  /oauth/authorize        - Initiate OAuth authorization
GET  /oauth/google_callback  - Handle Google OAuth callback
POST /oauth/token           - Exchange authorization code for JWT
```

### Job Management
```
POST /api/insertJob.php     - Create new publishing job
GET  /api/getJobStatus.php  - Get job status by ID
POST /api/getAllJobs.php    - List jobs (filtered by status)
POST /api/updateJob.php     - Update job status
```

### Authentication & Authorization
```
POST /api/auth.php          - Authentication utilities
POST /api/github_app.php    - GitHub App webhook handler
```

## Usage Examples

### OAuth Flow Testing
```bash
# 1. Test authorization endpoint
curl "http://localhost/oauth/authorize?response_type=code&client_id=dsb-gpt&redirect_uri=https%3A//example.com/callback&state=test123&scope=jobs:read"

# 2. Exchange code for token (after Google auth)
curl -X POST "http://localhost/oauth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "authorization_code",
    "code": "authorization_code_from_google", 
    "client_id": "dsb-gpt"
  }'
```

### Job Management
```bash
# Create a new job
curl -X POST "http://localhost/api/insertJob.php" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "job-123",
    "status": "pending",
    "created_at": "2025-08-13T10:00:00Z",
    "updated_at": "2025-08-13T10:00:00Z",
    "payload": {
      "repository": "owner/repo",
      "article": {
        "title": "Sample Article",
        "url": "/sample-article"
      }
    }
  }'

# Get job status
curl -H "Authorization: Bearer your_jwt_token" \
     "http://localhost/api/getJobStatus.php?id=job-123"

# List all jobs
curl -X POST "http://localhost/api/getAllJobs.php" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{"status": "*"}'
```

## Development

### Local Development Setup
```bash
# Start PHP development server
php -S localhost:8080 -t .

# Or use with specific bootstrap
php -S localhost:8080 -t . -d auto_prepend_file=bootstrap.php
```

### Running Tests
```bash
# Run PHPUnit tests
composer test

# Or directly
vendor/bin/phpunit
```

### Debugging
Enable error reporting in development:
```php
// In bootstrap.php for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Security Considerations

### JWT Token Security
- Uses RS256 algorithm with private/public key pairs
- Implements scope-based authorization (`jobs:read`, `jobs:write`, `jobs:admin`)
- Configurable token expiry (default: 1 hour)

### Input Validation
- All inputs are sanitized and validated
- SQL injection protection via prepared statements
- CSRF protection for state parameters

### CORS Configuration
```php
// Configured in bootstrap.php
header('Access-Control-Allow-Origin: https://chat.openai.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
```

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
