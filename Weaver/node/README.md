# Weaver Node.js Implementation

🚧 **Status: Planned Implementation**

This directory will contain the Node.js implementation of Weaver, providing the same API functionality as the PHP version but optimized for serverless and cloud-native deployments.

## Planned Features

### Framework & Dependencies
- **Express.js** or **Fastify** for high-performance HTTP server
- **jsonwebtoken** for JWT handling
- (Optional) future external identity integration (deferred; current model uses API key + session JWT)
- **@octokit/rest** for GitHub API operations
- **cors** for cross-origin resource sharing
- **helmet** for security headers
- **winston** for structured logging

### Architecture
```
src/
├── config/
│   └── index.js          # Environment configuration
├── middleware/
│   ├── auth.js           # JWT authentication middleware
│   ├── cors.js           # CORS configuration
│   └── validation.js     # Request validation
├── routes/
│   ├── sessions.js       # Session issuance (job-scoped JWT)
│   ├── jobs.js           # Job management API
│   └── github.js         # GitHub webhook handling
├── services/
│   ├── SessionService.js
│   └── JobService.js
├── utils/
│   └── database.js       # Database abstraction
└── app.js                # Express application setup
```

### Deployment Options
- **Serverless**: Vercel, Netlify Functions, AWS Lambda
- **Container**: Docker, Railway, Render
- **Traditional**: PM2, forever, systemd
- **Cloud**: Google Cloud Run, Azure Container Instances

### Development Goals
- TypeScript support for type safety
- Comprehensive test coverage with Jest
- OpenAPI documentation generation
- Health check and metrics endpoints
- Hot reload for development
- Graceful shutdown handling

## Getting Started (When Available)

```bash
# Install dependencies
npm install

# Copy environment configuration
cp .env.example .env

# Start development server
npm run dev

# Run tests
npm test

# Build for production
npm run build

# Start production server
npm start
```

## Environment Variables
```env
# Server Configuration
PORT=3000
NODE_ENV=production

# Core Auth
WEAVER_API_KEY=change_me
WEAVER_SESSION_JWT_SECRET=optional_session_secret

# GitHub App
GITHUB_APP_ID=your_github_app_id
GITHUB_APP_PRIVATE_KEY_PATH=./weaver-private.pem
GITHUB_WEBHOOK_SECRET=your_webhook_secret

# Database (optional)
DATABASE_URL=postgresql://user:pass@localhost:5432/weaver

# CORS
ALLOWED_ORIGINS=https://chat.openai.com,http://localhost:3000
```

## API Compatibility

This implementation will provide compatibility with the current simplified PHP API model:

### Job Management
- `POST /api/insertJob` (API key)
- `GET /api/getJobStatus?id=`
- `POST /api/getAllJobs` (API key, pagination)
- `POST /api/updateJob` (API key)
- `GET /api/jobArtifact?id=` (API key)

### Additional Features
- `GET /health` - Health check endpoint
- `GET /metrics` - Prometheus metrics (optional)
- WebSocket support for real-time job updates (future)

## Performance Benefits

Compared to PHP implementation:
- **Asynchronous I/O** for better concurrent request handling
- **Lower memory footprint** for serverless deployments
- **Faster startup time** for containerized environments
- **Built-in clustering** support for multi-core utilization

## Development Roadmap

1. **Phase 1**: Basic Express.js setup + API key middleware + job endpoints
2. **Phase 2**: Session JWT issuance & validation
3. **Phase 3**: GitHub App integration & webhooks
4. **Phase 4**: Asset processing parity
5. **Phase 5**: Test & CI automation
6. **Phase 6**: Metrics & observability

## Contributing

When this implementation is ready for development:

1. Follow the established API specification
2. Maintain environment variable compatibility
3. Implement comprehensive error handling
4. Add unit and integration tests
5. Update documentation

---

**Want to help implement this?** Contact the maintainers or open an issue to discuss the implementation plan.
