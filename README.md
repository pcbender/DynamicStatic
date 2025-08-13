# Dynamic Static AI CMS

Dynamic Static AI CMS is an intelligent content management system that combines static site generation with AI-powered content creation and management. It features a custom GPT with OpenAPI integration, OAuth authentication via Google, and seamless GitHub workflow automation.

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
dist/                   Built static site (HTML, CSS, images, JSON data)
templates/              HTML templates for new pages
scripts/                Node.js utilities and tools
  build.js             Updates related article metadata using TF-IDF
  ai-review.js         AI review system for GitHub integration
  Base64Encoding.js    OAuth flow testing utility
  OpusProcessor.js     Content processing and publishing
GPT/                    Custom GPT (Echo) configuration
  Configuration.md     GPT instructions and behavior definition
  openapi.json         API specification for Weaver integration
  project-config.json  Default project settings for publishing
  README.md            Echo setup and configuration guide
Weaver/                 Multi-language backend services
  README.md            Multi-language implementation guide
  php/                 PHP implementation (production ready)
    api/               REST API endpoints
      auth.php         Authentication and authorization
      getAllJobs.php   Job listing endpoint
      insertJob.php    Job creation endpoint
      publish.php      Content publishing endpoint (deprecated)
    oauth/             OAuth 2.0 implementation
      authorize.php    OAuth authorization endpoint
      google_callback.php Google OAuth callback handler
      token.php        Token exchange endpoint
    src/               Core PHP classes
      Service/         Service layer (OAuth, JWT)
  node/                Node.js implementation (planned)
  dotnet/              .NET implementation (planned)
run-local.*            Helper scripts for different platforms
```

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

### PHP Backend
Deploy the `Weaver/` directory to any PHP hosting service with:
- PHP 8.0+ support
- Composer for dependency management
- HTTPS enabled (required for OAuth)

## Development Notes

- `.env` file stores sensitive configuration (not committed to git)
- `scripts/Base64Encoding.js` helps test OAuth state parameter encoding
- AI review system integrates with GitHub Actions for automated workflows
- JWT tokens include scope-based permissions for fine-grained access control
- Google OAuth integration supports multiple redirect URIs for different environments

## License

Apache-2.0

