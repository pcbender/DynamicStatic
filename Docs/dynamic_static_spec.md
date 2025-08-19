# Dynamic Static & Opus — Project Specification v0.1

This document defines the **initial specification** for the Dynamic Static publishing system, including:

- **Cantor** (human user)
- **Echo** (Custom GPT)
- **Weaver** (hub API)
- **Opus** (GitHub CI/CD + Actions)
- **AI Review** phase

It combines user stories, workflows, design principles, and current/planned API data shapes.

---

## 1. Cantor Onboarding & Identity

### US-01: Sign in & Create Profile
**As a** Cantor  
**I want** to sign in to the Dynamic Static website and set my defaults  
**So that** publishing has sane defaults without exposing secrets

**Acceptance:**
- Sign in via internal or external IDP (SSO, Okta, Microsoft, GitHub, Google, etc.).
- Complete profile with display name, optional default repo/branch/path, notification preferences.
- No GitHub tokens or App IDs are shown.

---

### US-02: Connect GitHub & Grant Repos
**As a** Cantor  
**I want** to connect my GitHub account/org and grant specific repos  
**So that** Dynamic Static can publish only where I’ve permitted

**Acceptance:**
- One-click “Connect GitHub” → install GitHub App → choose repos.
- Linked repos appear in my profile.

---

### US-03: Per-Repo Configuration
**As a** Cantor  
**I want** to customize settings per linked repository  
**So that** each repo can have tailored behavior

**Acceptance:**
- For each linked repo: set default branch, base path, PR vs commit mode, deploy target.
- Can revoke a repo at any time.

---

### US-04: Session & Scope Without ChatGPT Identity
**As a** system  
**I want** API calls from Echo to carry only a server-issued session token  
**So that** requests are authorized to the correct user scope without relying on ChatGPT user identity

**Acceptance:**
- Session JWT issued after web login.
- Echo uses `X-API-Key` + optional `weaver_session`.
- All scope derived from Tenant + per-repo grants.

---

## 2. Opus Onboarding & Repo Setup

### US-A1: Start Onboarding from Profile
- From Profile, click **Connect GitHub** and grant repos.
- Next step: “Set up Opus” per repo.

---

### US-A2: Install Opus Workflows in a Repo
- Auto-generate PR adding `.github/workflows/dynamic-static-core.yml` + selected add-ons.
- PR marks repo status as “Opus Pending.”

---

### US-A3: Create/Verify Required Secrets
- Guided checklist creates or verifies required GitHub Action secrets.
- Dry-run validation supported.

---

### US-A4: Permissions & Branch Rules Check
- Verify Actions enabled, branch permissions compatible.
- Provide fix-links to settings.

---

### US-A5: First-Run Health Check
- Trigger a safe test build/deploy.
- Repo status flips to **Ready** on pass.

---

## 3. Echo — First-Time Use

### Journey Steps
1. **Greeting & Orientation** — GPT explains what it can do.
2. **Connect Account** — GPT gives link to Dynamic Static site for login & repo linking.
3. **Confirm Readiness** — GPT sees linked repos and asks which to use.
4. **Draft Content** — GPT collaborates on content.
5. **Publish Job** — `POST /jobs` with payload & deployment config.
6. **Track Status** — Poll `GET /job/status` until complete.
7. **Output Links** — GPT returns PR, preview, and prod URLs.

---

## 4. Opus Core Workflow

### Happy Path
1. **Job Receipt** — Weaver sends payload to Opus.
2. **Transform Content** — Markdown → HTML; embed assets.
3. **Update Site Data** — Update JSON indexes & manifests.
4. **Commit or PR** — Respect per-repo config.
5. **Dev Deploy** — Publish preview.
6. **Prod Deploy** — After approval, publish to prod.
7. **Final Verification** — Check links, assets, JSON integrity.
8. **Close Job** — Update Weaver with final status & links.

---

## 5. Weaver as Message Hub

### State Model
- queued → validating → transforming → indexing → pr_open → dev_deploying → dev_ready → prod_deploying → completed
- Non-happy: failed, warning, canceling, canceled, paused
- Extended for AI Review: reviewing_ai, reviewed_ai, review_blocked

### Messaging
- **Opus → Weaver**: `POST /jobs/update` at step boundaries.
- **Echo → Weaver**: control directives (`cancel`, `pause`, `resume`).
- **Weaver → Opus**: triggers via GitHub `repository_dispatch` or webhook.

### Echo Polling
- `GET /job/status?id=…` with optional `wait` for long-poll.

---

## 6. AI Review Phase

### US-R1: Trigger AI Review
**As a** Cantor  
**I want** an automated review of content and site structure at PR, push, or publish  
**So that** I can catch issues early

**Acceptance:**
- Triggered by PR open/update, push to main/release, or manual dispatch.
- Modes: Light (diff), Deep (full site), Smart (diff + dependent pages).
- Policy: advisory, warn, or block.

---

### US-R2: MVP Link-Only Output
**As a** Cantor  
**I want** a link to the AI review report in PR/issue  
**So that** I can view results outside chat

**Acceptance:**
- AI review posts one PR comment (PR mode) or one issue (push/release).
- Opus sends `links.reviewUrl` to Weaver.
- Echo reads and offers “Open report” link in chat.

---

### US-R3: Extended Review (Future)
- Human-readable markdown report.
- Machine-readable JSON with findings & severity.
- Echo can summarize and offer next actions (fix PR, re-review, proceed, cancel).
- Blocking policy enforced per repo config.

---

## 7. MVP Acceptance Flow (AI Review)

1. Echo creates job.
2. Opus runs transform/index.
3. AI review runs.
4. PR comment or issue created with results.
5. Opus calls `POST /jobs/update` with:
   ```json
   {
     "status": "reviewed_ai",
     "links": {
       "reviewUrl": "https://github.com/org/repo/pull/123#issuecomment-456",
       "prUrl": "https://github.com/org/repo/pull/123"
     }
   }
   ```
6. Echo polls status, sees `reviewUrl`, and presents link.

---

## 8. Key Design Principles

- **Zero secrets in chat** — all GitHub and deploy credentials handled in Weaver/Opus.
- **Per-repo scope** — grants and settings on a repo basis.
- **Event-driven where possible** — webhooks and `repository_dispatch` to avoid polling except for Echo.
- **Static-first** — zero-touch deployment; all data in JSON & static assets.
- **Progressive enhancement** — MVP uses simple links; richer UI/reporting added later.

---

## 9. Future Enhancements

- Inline Echo summaries of AI review results.
- Automated fix PRs for certain findings.
- Branch protection integration for blocking reviews.
- Multi-tenant team support with shared repo grants.
- Scheduled AI review runs (nightly regression).

---

## 10. API Data Shapes (Work in Progress)

### 10.1 Content Job Payload (Echo → Weaver)
```json
{
  "type": "article | post | page",
  "metadata": {
    "title": "string",
    "description": "string",
    "tags": ["string"],
    "category": "string",
    "author": "string",
    "publishDate": "ISO-8601 datetime",
    "template": "string"
  },
  "content": {
    "format": "markdown | html",
    "body": "string",
    "excerpt": "string"
  },
  "assets": [
    {
      "type": "image | video | document | audio",
      "name": "string",
      "url": "string (URL or data URI)",
      "alt": "string",
      "caption": "string",
      "placement": "hero | inline | gallery | attachment"
    }
  ],
  "seo": {
    "metaDescription": "string",
    "keywords": ["string"],
    "canonicalUrl": "string"
  },
  "deployment": {
    "repository": "owner/repo",
    "branch": "string",
    "basePath": "string",
    "filename": "string"
  }
}
```

---

### 10.2 Job Object (Weaver → Echo / Opus)
```json
{
  "id": "string",
  "status": "queued | validating | transforming | indexing | pr_open | dev_deploying | dev_ready | prod_deploying | completed | failed | warning | canceling | canceled | paused | reviewing_ai | reviewed_ai | review_blocked",
  "created_at": "ISO-8601 datetime",
  "updated_at": "ISO-8601 datetime",
  "created_by_sub": "string",
  "created_by_email": "string",
  "payload": { /* ContentJobPayload */ },
  "links": {
    "prUrl": "string (optional)",
    "previewUrl": "string (optional)",
    "prodUrl": "string (optional)",
    "reviewUrl": "string (optional)"
  },
  "review": {
    "mode": "light | deep | smart",
    "summary": { "grade": "A..F", "score": 0..100 },
    "counts": { "critical": 0, "high": 0, "medium": 0, "low": 0, "notes": 0 }
  }
}
```

---

### 10.3 Create Job (POST `/jobs`)
**Input:**
```json
{
  "payload": { /* ContentJobPayload */ }
}
```

**Output:**
```json
{
  "status": "success | error",
  "job_id": "string",
  "weaver_session": "JWT (optional)"
}
```

---

### 10.4 Get Job Status (GET `/job/status`)
**Params:**
- `id` — Job ID
- Optional: `wait` — seconds to long-poll (planned)

**Output:**
```json
{ /* Job Object */ }
```

---

### 10.5 Update Job Status (POST `/jobs/update`)
**Input:**
```json
{
  "id": "string",
  "status": "string",
  "payload": { /* ContentJobPayload (optional) */ },
  "links": {
    "prUrl": "string",
    "previewUrl": "string",
    "prodUrl": "string",
    "reviewUrl": "string"
  },
  "review": { /* AI review summary (optional) */ }
}
```

**Output:**
```json
{ "status": "updated" }
```

---

### 10.6 Get Job Artifact (GET `/jobs/artifact`)
**Params:**
- `id` — Job ID
- Optional: `X-Timestamp` / `X-Signature` — for HMAC verification

**Output:**
```json
{ /* ContentJobPayload */ }
```

---

### 10.7 Planned Control API (Future)
**Issue a directive (Echo → Weaver):**
```json
{
  "id": "string",
  "action": "cancel | pause | resume | retry | rollback",
  "reason": "string"
}
```

**Fetch pending directives (Opus → Weaver):**
```json
{
  "directives": [
    { "directive_id": "string", "action": "string", "created_at": "ISO-8601 datetime", "reason": "string" }
  ]
}
```

---

### 10.8 AI Review Payload (Future Extended Mode)
```json
{
  "mode": "light | deep | smart",
  "target": { "type": "pr | push | manual", "ref": "string", "sha": "string" },
  "summary": { "grade": "A..F", "score": 0..100 },
  "counts": { "critical": 0, "high": 0, "medium": 0, "low": 0, "notes": 0 },
  "threshold": { "blocking": "critical | high | none" },
  "findings": [
    {
      "id": "string",
      "severity": "low | medium | high | critical",
      "file": "string",
      "line": 0,
      "message": "string",
      "suggest": "string"
    }
  ],
  "artifacts": {
    "report_md_url": "string",
    "artifact_bundle_url": "string"
  }
}
```

---

## 11. Technology Stack v0.1

> Target: Document the clear split between Weaver and Opus while noting shared dependencies.

### 11.1 Weaver (Public Website + API)
- **Language/Runtime:** PHP 8.2+
- **Package Manager:** Composer
- **Database:** MySQL 8.x (profiles, jobs, repo configs)
- **Framework:** Laravel (recommended) or Slim/Symfony for API and site
- **HTTP client:** Guzzle for outbound calls (GitHub, IDPs, storage)
- **Markdown/HTML processing:** league/commonmark + HTML Purifier
- **Templating:** Blade (Laravel) or Twig (Symfony)
- **Authentication:**
  - OAuth 2.0 / OpenID Connect (external IDP support)
  - JWT for Echo ↔ Weaver session tokens (lcobucci/jwt)
- **Webhooks:** Signed GitHub webhooks endpoint (HMAC verify)
- **Data formats:** JSON for static content metadata, job payloads
- **Secrets:** php‑dotenv (dev); env vars/Vault in prod

### 11.2 Opus (GitHub-Based Workflows)
- **Runtime:** Node.js 18+ (JavaScript)
- **Workflow definitions:** GitHub Actions YAML files
- **AI Review tooling:** Node scripts (e.g., `ai-review.js`), optional Puppeteer/Playwright for advanced checks
- **GitHub API usage:** repository_dispatch, workflow_dispatch, REST/GraphQL queries
- **Integration points:**
  - Posts job status/updates back to Weaver API
  - Receives directives from Weaver via GitHub events
- **Artifacts:** HTML/Markdown, static assets, JSON metadata pushed to repos

### 11.3 Shared & Future
- **Static site generation:** optional future enhancement for Weaver output
- **Caching/Queues:** Redis (job queues, rate limiting)
- **Storage:** S3/MinIO for build artifacts
- **Observability:** Monolog (PHP), Sentry (PHP/Node), Prometheus metrics
- **Security:** HTTPS, HSTS, HMAC webhooks, signed artifact URLs
- **Containerization:** Docker images for PHP API and Node workers; docker-compose for local dev
- **CI/CD:** GitHub Actions for Opus workflows, test/build/deploy pipeline

---

## 12. Deliverables v0.1

### 12.1 Public-Facing Dynamic Static Website
- Built using the same Dynamic Static principles it supports (“eating our own dog food”).
- Includes:
  - Signup and onboarding process.
  - UI for creating/managing per-repo settings.
  - Authentication via internal or external IDPs.
  - Workflow setup guidance and automation.

### 12.2 Weaver API Layer
- Complete REST API implementation with:
  - Endpoints for identity, repo configuration, job submission, job status, and directives.
  - Test cases for all endpoints.
  - Swagger/OpenAPI 3.0 documentation covering the full surface.

### 12.3 Opus Code & Workflows
- GitHub Actions YAML workflows for:
  - Document/article build and publish.
  - AI Review process triggered on pushes, PRs, or publish events.
  - Status updates to Weaver and artifact publishing.
- Node.js scripts for content processing, AI review, and GitHub integration.

### 12.4 MySQL Database Schema
- Tables for:
  - Users and linked IDP accounts.
  - Repository configurations.
  - Jobs, job statuses, and job payloads.
  - Access tokens, secrets, and audit logs.
- Migration scripts for initial schema creation and updates.

### 12.5 Additional Deliverables (Optional/Future)
- **Admin dashboard:** for system monitoring, job management, and user support.
- **Developer SDKs:** PHP/Node client libraries for Weaver API.
- **Integration samples:** Example repos showing Dynamic Static setup with Opus workflows.
- **Automated deployment scripts:** docker-compose and/or Terraform for full-stack deployment.

## 13. Echo (Custom GPT) Configuration
### 13.1 Goals & Constraints

- Integrate with Weaver using a single GPT Action.
- Use OAuth for authentication—no static keys in GPT config.
- System prompt must be ≤ 8,000 characters.
- Secure by design: no secrets in GPT instructions.
- Minimal user setup—Echo should guide Cantor to link account on first use.

## 13.2 User Stories & Acceptance Criteria
### Story 1 – First Use

- As a new GPT user
- I want Echo to explain its purpose and direct me to connect via Weaver OAuth
- So that I can securely link my identity and repos.

Acceptance Criteria:

- Clear introductory message.

- OAuth link provided.

- Session resumes after OAuth with connected status.

Story 2 – Job Creation

- As a connected Cantor

- I want to create jobs (e.g., new articles, edits) via Echo

- So that Opus can process them.

Acceptance Criteria:

- Minimal required inputs.

- Echo validates repo access before creating the job.

### Story 3 – Job Status & Control

- As a connected Cantor

- I want to check job status or cancel jobs

- So that I can manage work in progress.

Acceptance Criteria:

- Status returned from Weaver.

- Cancel/stop commands update job state in Opus.

### 13.3 Action Configuration Basics

- Auth Type: OAuth 2.0 (Bearer token)

- Base URL: Weaver API (e.g., https://weaver.example.com/api)

- Headers:
Authorization: Bearer <access_token>
Content-Type: application/json

- Polling: Echo may long-poll /job/status/{id} for updates.

- Error Handling: Map API errors to user-friendly messages with recovery steps.

### 13.4 System Prompt Outline

- Identity & Roles – Echo is the AI partner (Custom GPT) for Dynamic Static; Cantor is the human user.

- Primary Functions – Content creation, status monitoring, AI review, publication via Opus.

- Workflow Awareness – All work flows: Cantor → Echo → Weaver → Opus → GitHub.

- Security Rules – Use OAuth token only; never request secrets in chat.

- Response Style – Collaborative, contextual, concise when needed.

- Error Recovery – Offer guided steps for failed API calls or auth issues.

### 13.5 Ready-to-Use Copy Blocks

Intro Message:

Hi, I’m Echo, your AI collaborator for the Dynamic Static publishing workflow. Let’s start by linking your account so I can work with your repositories. Connect via Weaver OAuth

OAuth Success Message:

You’re connected! I can now create, track, and publish content for your repositories.

OAuth Failure Message:

The connection failed. Let’s try again: Connect via Weaver OAuth

### 13.6 Conversation Starters

- “Connect my GitHub repo”

- “Draft a new article”

- “Publish draft to repo”

- “Check job status”

- “Run AI review”

### 13.7 Deliverables for Echo

- Finalized Action schema (OpenAPI subset for Weaver endpoints).

- ≤ 8,000 character system prompt.

- Pre-configured conversation starters.

- Metadata for GPT listing (description, instructions, logo, categories).

- Test checklist for OAuth, job creation, polling, and AI review.

- Runbook for common failures and recovery steps.