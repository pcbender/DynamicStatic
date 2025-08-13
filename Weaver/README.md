# Weaver - Multi-Language Backend Services

**Weaver** is the messenger and gatekeeper component in the Dynamic Static AI CMS architecture, managing secure communication between **Echo** (AI co-creator) and **Opus** (GitHub DevOps provider).

## 🕸️ Weaver's Role in the Four-Actor System

```
🎭 Cantor (Human) ↔ 🤖 Echo (AI GPT) ↔ 🕸️ Weaver (API Gateway) ↔ ⚙️ Opus (GitHub DevOps)
```

Weaver provides:
- **OAuth 2.0 Authentication** with Google identity provider
- **JWT-based API Authorization** with scope management
- **Job Management System** for content publishing workflows
- **GitHub App Integration** for secure repository operations
- **HMAC Signature Verification** for webhook security

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

All Weaver implementations expose identical REST APIs defined in the [OpenAPI specification](../openapi.json):

### OAuth Endpoints
- `GET /oauth/authorize` - Initiate OAuth flow
- `GET /oauth/google_callback` - Handle Google OAuth callback  
- `POST /oauth/token` - Exchange authorization code for JWT

### Job Management API
- `POST /api/insertJob.php` - Create new publishing job
- `GET /api/getJobStatus.php?id={jobId}` - Get job status
- `POST /api/getAllJobs.php` - List jobs (filtered by status)
- `POST /api/updateJob.php` - Update job status

### GitHub Integration
- `POST /api/github_app.php` - GitHub App webhook handler
- Repository dispatch for workflow triggering

## Environment Configuration

All implementations use consistent environment variables:

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
GITHUB_APP_PRIVATE_KEY=path_to_private_key.pem
GITHUB_WEBHOOK_SECRET=your_webhook_secret

# Database Configuration (if using database storage)
DATABASE_URL=your_database_connection_string

# CORS Configuration
ALLOWED_ORIGINS=https://chat.openai.com,http://localhost:3000
```

## Choosing an Implementation

| Implementation | Best For | Pros | Cons |
|---------------|----------|------|------|
| **PHP** | Traditional hosting, WordPress integration | Mature ecosystem, wide hosting support | Single-threaded, memory management |
| **Node.js** | Serverless, microservices, rapid development | Fast development, NPM ecosystem, async | Callback complexity, memory leaks |
| **.NET** | Enterprise, Azure environments | Strong typing, excellent tooling, performance | Windows-centric, larger runtime |
| **Go** | High-performance, containerized deployments | Excellent performance, simple deployment | Smaller ecosystem, steeper learning curve |
