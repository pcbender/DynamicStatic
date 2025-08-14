# Weaver - Multi-Language Backend Services

**Weaver** is the messenger and gatekeeper component in the Dynamic Static AI CMS architecture, managing secure communication between **Echo** (AI co-creator) and **Opus** (GitHub DevOps provider).

## 🕸️ Weaver's Role in the Four-Actor System

```
🎭 Cantor (Human) ↔ 🤖 Echo (AI GPT) ↔ 🕸️ Weaver (API Gateway) ↔ ⚙️ Opus (GitHub DevOps)
```

Weaver provides:
- **API Key Authentication** (`X-API-Key`) for service-to-service calls from Echo
- **Optional Session JWT** (job-scoped, ephemeral) to bind follow-up operations to a single job
- **Job Management System** for content publishing workflows (content + assets)
- **GitHub App Integration** (installation tokens, file cache for installation IDs)
- **HMAC Signature Verification** (optional artifact / webhook validation)

## Language Implementations

Weaver is designed to be implementation-agnostic, with multiple language versions providing identical APIs and functionality:

### 📁 [php/](./php/) - PHP Implementation
- **Status**: ✅ Production Ready
- **Framework**: Vanilla PHP 8.0+ with Composer
- **Dependencies**: Firebase JWT, Google API Client, PHPUnit
- **Deployment**: Apache/Nginx with mod_php or PHP-FPM
- **Use Case**: Traditional web hosting, cPanel environments

### 📁 [node/](./node/) - Node.js Implementation  
- **Status**: 🚧 Planned
- **Framework**: Express.js or Fastify
- **Dependencies**: jsonwebtoken, googleapis, jest
- **Deployment**: PM2, Docker, Vercel, Railway
- **Use Case**: Serverless functions, microservices, cloud-native

### 📁 [dotnet/](./dotnet/) - .NET Implementation
- **Status**: 🚧 Planned  
- **Framework**: ASP.NET Core Web API
- **Dependencies**: Microsoft.AspNetCore.Authentication.JwtBearer, Google.Apis.Auth
- **Deployment**: IIS, Azure App Service, Docker
- **Use Case**: Enterprise environments, Azure integration

### 📁 [go/](./go/) - Go Implementation
- **Status**: 🚧 Future Consideration
- **Framework**: Gin or Echo
- **Dependencies**: golang-jwt, google.golang.org/api
- **Deployment**: Binary deployment, Kubernetes
- **Use Case**: High-performance, containerized environments

## Common API Specification

All Weaver implementations target a shared REST contract (see `GPT/openapi.json`). Legacy OAuth flow has been removed pre-production.

### Core API (current PHP reference)
- `POST /api/insertJob.php` – Create publishing job (returns `job_id` + optional `weaver_session`)
- `GET  /api/getJobStatus.php?id={jobId}` – Retrieve job status
- `POST /api/getAllJobs.php` – List jobs with pagination `{status, limit, offset}`
- `POST /api/updateJob.php` – Update job status / payload patches
- `GET  /api/jobArtifact.php?id={jobId}` – Retrieve stored job payload (API key + optional HMAC/session)

### GitHub Integration
- GitHub App installation token retrieval (App ID + private key)
- Persistent file cache to reduce installation lookup calls
- Placeholder webhook handler (`api/github_app.php`) for future events

## Environment Configuration

Environment variables (current model):

```env
WEAVER_API_KEY=change_me_long_random
WEAVER_SESSION_JWT_SECRET=optional_session_secret

GITHUB_APP_ID=your_github_app_id
GITHUB_APP_PRIVATE_KEY=../weaver-private.pem
GITHUB_WEBHOOK_SECRET=optional_webhook_secret

# Repository Allow List (JSON array)
; (Allowlist removed – repository authorization delegated to GitHub App installation)

# Optional / Advanced
ALLOWED_ORIGINS=https://chat.openai.com,http://localhost:3000
LOG_LEVEL=info
```

## Choosing an Implementation

| Implementation | Best For | Pros | Cons |
|---------------|----------|------|------|
| **PHP** | Traditional hosting, WordPress integration | Mature ecosystem, wide hosting support | Single-threaded, memory management |
| **Node.js** | Serverless, microservices, rapid development | Fast development, NPM ecosystem, async | Callback complexity, memory leaks |
| **.NET** | Enterprise, Azure environments | Strong typing, excellent tooling, performance | Windows-centric, larger runtime |
| **Go** | High-performance, containerized deployments | Excellent performance, simple deployment | Smaller ecosystem, steeper learning curve |
