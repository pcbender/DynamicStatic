# Weaver .NET Implementation

🚧 **Status: Planned Implementation**

This directory will contain the .NET implementation of Weaver, providing enterprise-grade performance and integration with Microsoft Azure services.

## Planned Features

### Framework & Dependencies
- **ASP.NET Core 8.0** Web API
- **Microsoft.AspNetCore.Authentication.JwtBearer** for JWT authentication
- **Google.Apis.Auth** for Google OAuth integration
- **Octokit** for GitHub API operations
- **Microsoft.EntityFrameworkCore** for database operations
- **Serilog** for structured logging
- **AutoMapper** for object mapping
- **FluentValidation** for request validation

### Architecture
```
src/
├── Weaver.Api/
│   ├── Controllers/
│   │   ├── OAuthController.cs
│   │   ├── JobsController.cs
│   │   └── GitHubController.cs
│   ├── Middleware/
│   │   ├── AuthenticationMiddleware.cs
│   │   ├── ErrorHandlingMiddleware.cs
│   │   └── CorsMiddleware.cs
│   ├── Models/
│   │   ├── Requests/
│   │   ├── Responses/
│   │   └── DTOs/
│   └── Program.cs
├── Weaver.Core/
│   ├── Entities/
│   ├── Interfaces/
│   └── Services/
│       ├── IGoogleOAuthService.cs
│       ├── IJwtService.cs
│       └── IJobService.cs
├── Weaver.Infrastructure/
│   ├── Data/
│   ├── Services/
│   │   ├── GoogleOAuthService.cs
│   │   ├── JwtService.cs
│   │   └── JobService.cs
│   └── Repositories/
└── Weaver.Tests/
    ├── Unit/
    ├── Integration/
    └── EndToEnd/
```

### Deployment Options
- **Azure App Service** for managed hosting
- **Azure Container Instances** for containerized deployment
- **Azure Functions** for serverless scenarios
- **Docker** for on-premises or cloud deployment
- **IIS** for traditional Windows hosting
- **Kubernetes** for orchestrated deployments

### Development Goals
- Clean Architecture principles
- Dependency injection throughout
- Comprehensive logging and monitoring
- Health checks and readiness probes
- API versioning support
- Swagger/OpenAPI documentation
- High test coverage (>90%)

## Getting Started (When Available)

```bash
# Restore dependencies
dotnet restore

# Copy configuration
cp appsettings.example.json appsettings.Development.json

# Run in development mode
dotnet run --project src/Weaver.Api

# Run tests
dotnet test

# Build for production
dotnet publish -c Release -o out
```

## Configuration

### appsettings.json
```json
{
  "Logging": {
    "LogLevel": {
      "Default": "Information",
      "Microsoft.AspNetCore": "Warning"
    }
  },
  "GoogleOAuth": {
    "ClientId": "your_google_client_id",
    "ClientSecret": "your_google_client_secret"
  },
  "Weaver": {
    "OAuthClientId": "dsb-gpt",
    "JwtSecret": "your_jwt_secret",
    "JwtExpiryMinutes": 60
  },
  "GitHub": {
    "AppId": "your_github_app_id",
    "PrivateKeyPath": "./weaver-private.pem",
    "WebhookSecret": "your_webhook_secret"
  },
  "ConnectionStrings": {
    "DefaultConnection": "Server=localhost;Database=Weaver;Trusted_Connection=true;"
  },
  "Cors": {
    "AllowedOrigins": ["https://chat.openai.com", "http://localhost:3000"]
  }
}
```

### Environment Variables (Production)
```env
# Override sensitive settings
GoogleOAuth__ClientSecret=production_secret
Weaver__JwtSecret=production_jwt_secret
GitHub__WebhookSecret=production_webhook_secret
ConnectionStrings__DefaultConnection=production_db_connection
```

## API Compatibility

This implementation will provide 100% API compatibility with other Weaver implementations:

### Controllers

**OAuthController**
- `GET /oauth/authorize`
- `GET /oauth/google_callback`
- `POST /oauth/token`

**JobsController**
- `POST /api/jobs` (insertJob equivalent)
- `GET /api/jobs/{id}` (getJobStatus equivalent)
- `GET /api/jobs` (getAllJobs equivalent)
- `PUT /api/jobs/{id}` (updateJob equivalent)

**GitHubController**
- `POST /api/github/webhook`

### Additional Features
- `GET /health` - Health check endpoint
- `GET /ready` - Readiness probe
- `GET /metrics` - Application metrics
- `GET /swagger` - API documentation

## Performance Benefits

Compared to other implementations:
- **High throughput** with async/await patterns
- **Low latency** with compiled code execution
- **Memory efficiency** with garbage collection optimization
- **Scalability** with built-in load balancing support
- **Security** with built-in protection against common vulnerabilities

## Azure Integration

### Azure-Specific Features
- **Azure Key Vault** for secrets management
- **Azure Application Insights** for monitoring
- **Azure SQL Database** for data persistence
- **Azure Service Bus** for message queuing
- **Azure Redis Cache** for session storage
- **Azure Active Directory** for enterprise authentication

### ARM Template Deployment
```json
{
  "$schema": "https://schema.management.azure.com/schemas/2019-04-01/deploymentTemplate.json#",
  "contentVersion": "1.0.0.0",
  "resources": [
    {
      "type": "Microsoft.Web/sites",
      "apiVersion": "2021-02-01",
      "name": "weaver-api",
      "properties": {
        "serverFarmId": "[resourceId('Microsoft.Web/serverfarms', 'weaver-plan')]"
      }
    }
  ]
}
```

## Development Roadmap

1. **Phase 1**: Project structure and basic ASP.NET Core setup
2. **Phase 2**: OAuth endpoints with Google integration
3. **Phase 3**: Job management API with Entity Framework
4. **Phase 4**: GitHub App integration and webhooks
5. **Phase 5**: Azure-specific integrations and deployment
6. **Phase 6**: Performance optimization and monitoring
7. **Phase 7**: Production hardening and security review

## Testing Strategy

### Unit Tests
```csharp
[Test]
public async Task CreateJob_ValidRequest_ReturnsCreated()
{
    // Arrange
    var request = new CreateJobRequest { /* ... */ };
    
    // Act
    var result = await _controller.CreateJob(request);
    
    // Assert
    Assert.IsInstanceOf<CreatedResult>(result);
}
```

### Integration Tests
```csharp
[TestFixture]
public class JobsControllerIntegrationTests : IClassFixture<WebApplicationFactory<Program>>
{
    [Test]
    public async Task POST_jobs_authenticated_creates_job()
    {
        // Test full HTTP pipeline
    }
}
```

## Security Considerations

### Built-in Security Features
- **Data Protection API** for sensitive data encryption
- **CORS** middleware for cross-origin security
- **HTTPS** enforcement in production
- **Rate limiting** with AspNetCoreRateLimit
- **Input validation** with FluentValidation
- **SQL injection protection** with parameterized queries

### Security Headers
```csharp
app.Use(async (context, next) =>
{
    context.Response.Headers.Add("X-Content-Type-Options", "nosniff");
    context.Response.Headers.Add("X-Frame-Options", "DENY");
    context.Response.Headers.Add("X-XSS-Protection", "1; mode=block");
    await next();
});
```

## Monitoring and Observability

### Application Insights Integration
```csharp
services.AddApplicationInsightsTelemetry();
services.AddLogging(builder =>
{
    builder.AddApplicationInsights();
});
```

### Health Checks
```csharp
services.AddHealthChecks()
    .AddSqlServer(connectionString)
    .AddCheck<GoogleOAuthHealthCheck>("google-oauth")
    .AddCheck<GitHubHealthCheck>("github-api");
```

## Contributing

When this implementation is ready for development:

1. Follow C# coding standards and conventions
2. Implement SOLID principles
3. Write comprehensive unit and integration tests
4. Use dependency injection for all services
5. Follow async/await patterns consistently
6. Implement proper error handling and logging

---

**Interested in .NET development?** This implementation would benefit from contributors familiar with ASP.NET Core, Azure services, and enterprise application patterns.
