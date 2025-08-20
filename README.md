# Dynamic Static AI CMS

Dynamic Static AI CMS is an intelligent content management system that combines static site generation with AI-powered content creation and management. It features a custom GPT with OpenAPI integration, OAuth authentication via Google, and seamless GitHub workflow automation.

## Local Dev Quickstart

Run this from repo root to bootstrap certs, start containers, migrate DB, run smoke test, and open the app.

```bash
./tools/scripts/mkcert-local.sh && docker compose up -d && \
docker compose exec weaver-php php artisan migrate && \
docker compose exec weaver-php php artisan weaver:smoke --demo && \
open https://localhost
```

## System Actors

The DS CMS workflow is orchestrated by four distinct actors, each playing a crucial role in the Relational Design theory's principle of Human/AI co-creation:

### 🎭 **Cantor** - The Human Co-Creator
- **Role**: Human collaborator in the content creation process
- **Responsibilities**: Strategic content direction, creative input, quality oversight
- **Interaction**: Works directly with Echo through the custom GPT interface
- **Philosophy**: Embodies the human element in Human/AI co-creation

### 🤖 **Echo** - The AI Co-Creator
- **Role**: AI assistant integrated via Custom GPT
- **Responsibilities**: Content generation, editing assistance, workflow automation
- **Capabilities**: OpenAPI integration, OAuth authentication, GitHub operations
- **Philosophy**: The AI partner in collaborative content creation

### 🕸️ **Weaver** - The Messenger & Gatekeeper
- **Role**: Backend service managing communication between Echo and Opus
- **Responsibilities**: OAuth flows, API authentication, request routing, security
- **Technology**: PHP-based service with JWT tokens and Google OAuth
- **Function**: Ensures secure, authorized communication between systems

### ⚙️ **Opus** - The DevOps Provider
- **Role**: GitHub-based automation and deployment system
- **Responsibilities**: Repository management, CI/CD workflows, content deployment
- **Integration**: GitHub Actions, automated builds, static site generation
- **Output**: Live content deployment and infrastructure management

## Features

- **Custom GPT Integration**: OpenAPI-enabled GPT for content creation and management
- **OAuth 2.0 Authentication**: Google-based identity provider with secure token flow
- **Static Site Generation**: Fast, deployable HTML with Alpine.js interactivity
- **AI-Powered Reviews**: Automated code and content review with GitHub integration
- **GitHub Workflow Integration**: Seamless publishing and deployment automation
- **TF-IDF Content Analysis**: Intelligent related article suggestions
- **RESTful API**: Complete backend for content and job management
- **Offline & Data Caching**: Service worker and manifest generator for fast, resilient data access

## Architecture

The Dynamic Static AI CMS implements a four-actor architecture based on Relational Design principles:

### 🎭 **Cantor** ↔ 🤖 **Echo** (Human/AI Co-Creation Layer)
- **Interface**: Custom GPT with OpenAPI integration
- **Authentication**: OAuth 2.0 via Google identity provider
- **Collaboration**: Real-time content creation and editing
- **Technology**: OpenAI GPT platform with secure API access

### 🤖 **Echo** ↔ 🕸️ **Weaver** (API Gateway Layer)
- **Communication**: RESTful API with JWT authentication
- **Security**: Scope-based authorization, HMAC verification
- **Services**: Job management, content publishing, status tracking
- **Technology**: PHP backend with Composer dependencies

### 🕸️ **Weaver** ↔ ⚙️ **Opus** (DevOps Integration Layer)
- **Integration**: GitHub App authentication and webhook dispatch
- **Automation**: CI/CD workflows, automated deployments
- **Repository**: GitHub-based version control and collaboration
- **Technology**: GitHub Actions, static site generation

### Frontend (User Experience Layer)
- Static HTML in `dist/` ready for deployment
- [Alpine.js](https://alpinejs.dev/) for reactive UI components
- JSON-driven content and navigation system
- TF-IDF powered related article recommendations

## Repository Layout

```
apps/
  weaver-laravel/       Laravel application (Weaver service)
dist/                  Built static site artifacts
infra/
  docker/              Dockerfile & nginx config
tools/
  scripts/             Node.js workflow & automation scripts (Opus helpers)
  gpt/                 Custom GPT (Echo) configuration & OpenAPI spec
  postman/             Postman collections
templates/             HTML templates
V2/                    (Legacy location, ignored)
```

_VS Code configs updated; old V2/ path is ignored and not referenced._

## Getting Started

### Prerequisites

- Node.js 20 or later
- PHP 8.0+ with Composer
- Google OAuth 2.0 credentials
- OpenAI API key
- GitHub Personal Access Token

### Installation

1. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

2. **Install PHP dependencies:**
   ```bash
   cd Weaver
   composer install
   ```

3. **Configure environment variables:**
   Create a `.env` file in the project root:
   ```env
   # Google OAuth Configuration
   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   
   # Weaver OAuth Settings
   WEAVER_OAUTH_CLIENT_ID=dsb-gpt
   WEAVER_JWT_SECRET=your_jwt_secret
   
   # API Configuration
   OPENAI_API_KEY=your_openai_api_key
   GITHUB_TOKEN=your_github_token
   GITHUB_REPOSITORY=owner/repo
   
   # Optional
   BASE_BRANCH=main
   ```

### Development Setup

1. **Start the local development server:**
   ```bash
   cd dist
   python3 -m http.server 8000
   ```

2. **Set up PHP backend (if testing OAuth):**
   Configure your web server to serve the `Weaver/` directory, or use PHP's built-in server:
   ```bash
   cd Weaver
   php -S localhost:8080
   ```

3. **Test OAuth flow:**
   ```bash
   node scripts/Base64Encoding.js
   ```

### Usage

#### Content Management via GPT
The custom GPT can be accessed through OpenAI's interface with the configured OAuth flow. It provides:
- Content creation and editing
- GitHub repository management
- Automated publishing workflows

#### AI Code Review
Run automated code reviews:
```bash
npm run ai-review -- --mode=deep
npm run ai-review -- --mode=light --pr=42
```

#### Site Building
Regenerate related article metadata:
```bash
npm run build:site
```

## API Integration

### OpenAPI Specification
The `openapi.json` file defines the complete API specification for GPT integration, including:
- Content publishing endpoints
- Job management system
- Authentication flows
- Repository operations

### OAuth 2.0 Flow
1. **Authorization**: GPT initiates OAuth flow via `/oauth/authorize`
2. **Google Authentication**: User authenticates with Google
3. **Callback**: Google redirects to `/oauth/google_callback`
4. **Token Exchange**: GPT exchanges code for access token via `/oauth/token`
5. **API Access**: Authenticated requests to protected endpoints

### Key Endpoints
- `POST /publish` - Publish content to GitHub repository
- `GET /getAllJobs` - List all jobs and their status
- `POST /insertJob` - Create new publishing job
- `GET /getJobStatus/{id}` - Check specific job status

## Security

- **JWT-based authentication** with configurable expiration
- **Google OAuth 2.0** for identity verification
- **Scope-based authorization** for API access control
- **HMAC signature verification** for webhook security
- **Environment-based configuration** for sensitive data

## Deployment

### Static Site
The `dist/` directory can be deployed to any static hosting service:
- GitHub Pages
- Netlify
- Vercel
- AWS S3 + CloudFront

#### Workflow Safety & Deploy Checks

The GitHub Actions deploy jobs now include:
- Explicit `Checkout` step before artifact download (required for scripts and config files)
- Debug steps to print working directory and scripts for troubleshooting
- Guard steps to assert `WEBROOT_DEV` and `WEBROOT_PROD` secrets are set and absolute
- Hardened deploy wrapper (`scripts/safe-rsync.sh`) that checks for `.htaccess` and marker file before syncing

If a deploy fails, check the workflow logs for missing files, misconfigured secrets, or permission issues. The debug steps will show the exact state of the workspace before deploy.

#### HTTP Cache & Security Headers (.htaccess)

During `npm run build:site` the file `public/.htaccess` is copied to `dist/.htaccess`. This file applies:

- Cache busting for `sw.js` and `data/manifest.json` (no-store, must-revalidate)
- Long-lived immutable caching for other `data/*.json` payloads (1 year)
- Explicit JSON / web manifest MIME types
- Security headers: `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`

If you host on Apache, ensure `.htaccess` processing is enabled. For Nginx or other platforms, port these rules into your server config (example Nginx mapping: add `add_header` directives inside a `location /data/` block and a separate `location = /sw.js`).

The deploy safety script (`scripts/safe-rsync.sh`) requires that `.htaccess` exists in `dist/` (when present in `public/`) to avoid pushing a build missing critical cache headers.

#### Service Worker & Manifest

The build process now generates a service worker (`sw.js`) and a manifest (`data/manifest.json`) in `dist/` for offline and cache-aware data access. These files are registered automatically in top-level templates and use cache-control headers for optimal performance and reliability.

### PHP Backend
Deploy the `Weaver/` directory to any PHP hosting service with:
- PHP 8.0+ support
- Composer for dependency management
- HTTPS enabled (required for OAuth)

#### Laravel Cache Table

The backend now includes a migration for cache and cache_locks tables (`apps/weaver-laravel/database/migrations/2025_08_17_211405_create_cache_table.php`). Run `php artisan migrate` after pulling updates to ensure the database schema is current.

## Development Notes

- `.env` file stores sensitive configuration (not committed to git)
- `scripts/Base64Encoding.js` helps test OAuth state parameter encoding
- AI review system integrates with GitHub Actions for automated workflows
- JWT tokens include scope-based permissions for fine-grained access control
- Google OAuth integration supports multiple redirect URIs for different environments

## License

Apache-2.0

