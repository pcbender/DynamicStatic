# Dynamic Static AI CMS - API Quick Reference

## System Actors Overview

The DS CMS operates through four distinct actors in the Relational Design workflow:

- **🎭 Cantor**: Human co-creator providing strategic direction and creative input
- **🤖 Echo**: AI co-creator (Custom GPT) handling content generation and workflow automation  
- **🕸️ Weaver**: Messenger/gatekeeper managing secure communication between Echo and Opus
- **⚙️ Opus**: DevOps provider handling GitHub workflows and deployment automation

## Actor Interactions

```
Cantor (Human) ↔ Echo (AI GPT) ↔ Weaver (API Gateway) ↔ Opus (GitHub DevOps)
```

---

## General Notes
- **Base URL**: https://webbness.net/api/
- **Implementation**: Multiple language options available (PHP, Node.js, .NET)
- **Authentication**: Bearer JWT tokens via OAuth 2.0 flow
- **Authorization**: Scope-based permissions (`jobs:read`, `jobs:write`, `jobs:admin`)
- **Job States**: `pending`, `publishing`, `merging`, `waiting`, `actionrequired`, `building`, `live`, `error`, `cancel`, `cancelled`, `stale`
- **Terminal States**: `live`, `error`, `cancelled`
- **GitHub Integration**: Uses GitHub App authentication (no personal tokens required)
- **Job Tracking**: Always embed `[job:{jobId}]` in PR titles for correlation

---

## API Endpoints

### 1. **insertJob** 
`POST /insertJob.php`

Creates a new publishing job and triggers GitHub workflow dispatch.

**Authentication**: Required (Bearer token with `jobs:write` scope)

**Request Body**:
```json
{
  "id": "unique-job-id",
  "status": "pending",
  "created_at": "2025-08-13T10:00:00Z",
  "updated_at": "2025-08-13T10:00:00Z",
  "payload": {
    "repository": "owner/repo",
    "branch": "main",
    "base_path": "articles/",
    "article": {
      "title": "Article Title",
      "url": "/article-slug",
      "tags": ["tag1", "tag2"],
      "snippet": "Brief description"
    }
  }
}
```

**Response**:
```json
{
  "status": "success",
  "job_id": "unique-job-id",
  "dispatched": true
}
```

**Notes**: 
- Automatically dispatches `dsb.new_article` event to GitHub
- Job is created with authenticated user's `sub` and `email`
- GitHub App authentication is used (no `github_token` needed)

---

### 2. **getAllJobs**
`POST /getAllJobs.php`

Retrieves jobs filtered by status, scoped to authenticated user (unless admin).

**Authentication**: Required (Bearer token with `jobs:read` scope)

**Request Body**:
```json
{
  "status": "pending,publishing,live"
}
```
*Use `"*"` to retrieve all jobs*

**Response**:
```json
[
  {
    "id": "job-123",
    "status": "live",
    "created_at": "2025-08-13T10:00:00Z",
    "updated_at": "2025-08-13T10:30:00Z",
    "payload": "{\"article\":{\"title\":\"Sample\",\"url\":\"/sample\"}}",
    "created_by_sub": "google-oauth-sub-id",
    "created_by_email": "user@example.com"
  }
]
```

**Notes**:
- Non-admin users only see their own jobs (filtered by `created_by_sub`)
- Admin users with `jobs:admin` scope see all jobs

---

### 3. **getJobStatus**
`GET /getJobStatus.php?id={jobId}`

Returns detailed status of a specific job.

**Authentication**: Required (Bearer token with `jobs:read` scope)

**Query Parameters**:
- `id` (required): Job identifier

**Response**:
```json
{
  "id": "job-123",
  "status": "live",
  "created_at": "2025-08-13T10:00:00Z",
  "updated_at": "2025-08-13T10:30:00Z",
  "payload": "{\"article\":{\"title\":\"Sample\",\"url\":\"/sample\"}}",
  "created_by_sub": "google-oauth-sub-id",
  "created_by_email": "user@example.com"
}
```

---

### 4. **updateJob**
`POST /updateJob.php`

Updates the status of an existing job.

**Authentication**: Required (Bearer token with `jobs:write` scope)

**Request Body**:
```json
{
  "id": "job-123",
  "status": "live"
}
```

**Response**:
```json
{
  "status": "success"
}
```

**Notes**:
- Users can only update jobs they created (unless admin)
- Status `cancel` automatically transitions to `cancelled`

---

### 5. **publish** (Deprecated)
`POST /publish.php`

**Status**: ⚠️ **DEPRECATED** - Returns error directing to use GitHub App flow

The publish endpoint has been replaced by the GitHub App integration workflow triggered through `insertJob`.

---

## OAuth 2.0 Authentication Flow

### 1. **Authorization Request** (Cantor → Echo → Weaver)
```
GET /oauth/authorize?response_type=code&client_id=dsb-gpt&redirect_uri=...&state=...&scope=jobs:read
```

### 2. **Google Authentication** (Weaver → Google → Cantor)
User authenticates with Google OAuth

### 3. **Authorization Code Exchange** (Echo → Weaver)
```
POST /oauth/token
{
  "grant_type": "authorization_code",
  "code": "auth_code_from_google",
  "client_id": "dsb-gpt"
}
```

**Response**:
```json
{
  "access_token": "jwt_token",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "jobs:read jobs:write"
}
```

---

## Recommended Workflow (Actor-Based)

### **Phase 1: Cantor ↔ Echo (Content Creation)**
1. **Human Input**: Cantor provides content direction through GPT interface
2. **AI Processing**: Echo generates/refines content based on input
3. **Collaboration**: Iterative refinement between human and AI

### **Phase 2: Echo ↔ Weaver (Job Management)**
1. **Authentication**: Echo obtains JWT token via OAuth flow
2. **Job Creation**: 
   ```bash
   POST /insertJob.php
   # Creates job with status 'pending'
   # Automatically dispatches GitHub workflow
   ```
3. **Status Tracking**:
   ```bash
   GET /getJobStatus.php?id={jobId}
   # Monitor job progression
   ```

### **Phase 3: Weaver ↔ Opus (DevOps Automation)**
1. **GitHub Dispatch**: Weaver triggers `dsb.new_article` workflow event
2. **Repository Operations**: Opus handles branch creation, content publishing
3. **CI/CD Pipeline**: Automated build, validation, and deployment
4. **Status Updates**: Opus updates job status through API

### **Phase 4: Monitoring & Completion**
1. **Status Polling**: Echo monitors job via `getJobStatus`
2. **Completion**: Job reaches terminal state (`live`, `error`, `cancelled`)
3. **Feedback Loop**: Results communicated back to Cantor

---

## Job State Transitions

```
pending → publishing → building → live
    ↓         ↓           ↓        ↑
  cancel → cancelled   error ← stale
```

**Actor Responsibilities**:
- **Echo**: Creates jobs, monitors status
- **Weaver**: Manages job data, handles authentication
- **Opus**: Executes publishing workflow, updates job status
- **Cantor**: Provides oversight and intervention when needed

---

## Error Handling

### Authentication Errors
- **401 Unauthorized**: Invalid or expired JWT token
- **403 Forbidden**: Insufficient scope permissions

### Job Errors  
- **400 Bad Request**: Invalid job data or deprecated endpoint usage
- **404 Not Found**: Job ID not found or access denied

### GitHub Integration Errors
- Job status transitions to `error` state
- Error details logged in GitHub Actions
- Manual intervention may be required (status: `actionrequired`)

