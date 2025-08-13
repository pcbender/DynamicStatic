# Dynamic Static AI CMS - AI Code Review Guide

This guide covers the AI review system within the Dynamic Static AI CMS's four-actor architecture and includes setup for both local usage and GitHub Actions automation.

## System Overview

The AI Review system operates within the **Dynamic Static AI CMS** actor framework:

- **🎭 Cantor**: Human developer/reviewer initiating reviews
- **🤖 Echo**: AI co-creator providing automated code analysis  
- **🕸️ Weaver**: Backend service managing authentication and job workflows
- **⚙️ Opus**: GitHub DevOps automation handling repository operations

## Table of Contents
- [Prerequisites & Setup](#prerequisites--setup)
- [OAuth Flow Testing](#oauth-flow-testing)
- [Local Usage](#local-usage)
- [GitHub Actions Integration](#github-actions-integration)
- [Review Modes](#review-modes)
- [API Integration](#api-integration)
- [Troubleshooting](#troubleshooting)

## Prerequisites & Setup

### System Requirements
- Node.js 20+ installed
- PHP 8.0+ with Composer (for Weaver backend)
- GitHub Personal Access Token or GitHub App
- OpenAI API Key
- Google OAuth 2.0 credentials (for Weaver authentication)

### Initial Setup

1. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

2. **Install PHP dependencies (for Weaver):**
   ```bash
   cd Weaver
   composer install
   cd ..
   ```

3. **Configure environment variables:**
   Create a `.env` file in the project root:
   ```env
   # OpenAI Configuration
   OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxx
   
   # GitHub Configuration  
   GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx
   GITHUB_REPOSITORY=owner/repo
   BASE_BRANCH=main
   
   # Weaver Backend Configuration
   WEAVER_BASE=https://webbness.net
   WEAVER_TOKEN=your_jwt_secret_or_hmac_key
   
   # Google OAuth (for Weaver authentication)
   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   WEAVER_OAUTH_CLIENT_ID=dsb-gpt
   WEAVER_JWT_SECRET=your_jwt_secret
   ```

4. **Add to `.gitignore`:**
   ```
   .env
   .env.local
   .github/ai-review-history-local.json
   Weaver/.env
   ```

## OAuth Flow Testing

The Dynamic Static AI CMS includes OAuth 2.0 authentication between **Echo** (Custom GPT) and **Weaver** (backend service). Here's how to test the OAuth flow:

### 1. **Base64URL State Encoding Test**

Use the provided utility to test OAuth state parameter encoding:

```bash
node scripts/Base64Encoding.js
```

This script:
- Creates a test OAuth state object
- Encodes it as Base64URL (RFC 4648)
- Outputs the encoded string for use in OAuth flows

**Example Output:**
```
eyJyZWRpcmVjdF91cmkiOiJodHRwczovL2NoYXQub3BlbmFpLmNvbS9haXAvZy0yODVkM2QzNjMxZTczZTRiNTZiZjY0N2VhYWZhYjE1YWM1YzI1NWMwL29hdXRoL2NhbGxiYWNrIiwiY2xpZW50X2lkIjoiZHNiLWdwdCIsInNjb3BlIjoiam9iczpyZWFkIiwib3JpZ19zdGF0ZSI6InRlc3QxMjNhYmMifQ
```

### 2. **OAuth Flow Testing**

**Step 1: Authorization Request**
```bash
curl "https://webbness.net/oauth/authorize?response_type=code&client_id=dsb-gpt&redirect_uri=https%3A//chat.openai.com/aip/g-285d3d3631e73e4b56bf647eaafab15ac5c255c0/oauth/callback&state=encoded_state_from_step1&scope=jobs:read"
```

**Step 2: Token Exchange** (after receiving authorization code)
```bash
curl -X POST "https://webbness.net/oauth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "authorization_code",
    "code": "authorization_code_from_google",
    "client_id": "dsb-gpt"
  }'
```

**Step 3: API Testing with JWT Token**
```bash
# Test authenticated API access
curl -H "Authorization: Bearer your_jwt_token" \
     "https://webbness.net/api/getAllJobs.php" \
     -X POST \
     -H "Content-Type: application/json" \
     -d '{"status": "*"}'
```

### 3. **Custom OAuth Test Script**

Create `scripts/test-oauth.js` for comprehensive OAuth testing:

```javascript
// scripts/test-oauth.js
import fetch from 'node-fetch';

const WEAVER_BASE = process.env.WEAVER_BASE || 'https://webbness.net';
const CLIENT_ID = 'dsb-gpt';

async function testOAuthFlow() {
  console.log('🔐 Testing OAuth Flow...\n');
  
  // Step 1: Test authorization endpoint
  const authUrl = `${WEAVER_BASE}/oauth/authorize?response_type=code&client_id=${CLIENT_ID}&redirect_uri=https%3A//example.com/callback&state=test123&scope=jobs:read`;
  
  console.log('1. Authorization URL:');
  console.log(authUrl);
  console.log('\n2. Manual step: Visit URL above and complete Google OAuth');
  console.log('3. Extract authorization code from callback URL');
  console.log('\n4. Use code with token exchange endpoint:');
  console.log(`POST ${WEAVER_BASE}/oauth/token`);
  
  // Example token exchange (requires manual auth code)
  const tokenExample = {
    grant_type: 'authorization_code',
    code: 'AUTHORIZATION_CODE_FROM_STEP_2',
    client_id: CLIENT_ID
  };
  
  console.log('\nToken exchange payload:');
  console.log(JSON.stringify(tokenExample, null, 2));
}

testOAuthFlow().catch(console.error);
```

Run with: `node scripts/test-oauth.js`

## Local Usage

The AI review system can be run locally by **Cantor** (human developers) to analyze code before committing or as part of development workflow.

### Windows (PowerShell)

First time only - allow script execution:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

**Review local files:**
```powershell
.\run-local.ps1                    # Light review of recent changes
.\run-local.ps1 -mode deep         # Deep review of entire codebase
.\run-local.ps1 -post              # Post results to GitHub Issues
```

**Review a specific PR:**
```powershell
.\run-local.ps1 -pr 42             # Review PR #42 (Echo analyzes changes)
.\run-local.ps1 -pr 42 -post       # Review and post comment via Weaver API
```

**Deep review with automated fixes:**
```powershell
.\run-local.ps1 -mode deep -post   # Creates PR with AI-generated fixes
```

**Override repository:**
```powershell
.\run-local.ps1 -repo owner/other-repo
```

### Windows (Command Prompt)

**Review local files:**
```cmd
run-local.cmd                      # Light review
run-local.cmd --mode=deep          # Deep review  
run-local.cmd --post               # Post to GitHub
```

**Review a PR:**
```cmd
run-local.cmd --pr=42              # Review specific PR
run-local.cmd --pr=42 --post       # Review and comment
```

**Deep review with fixes:**
```cmd
run-local.cmd --mode=deep --post   # Generate and commit fixes
```

### Linux/Mac (Bash)

Make script executable (first time only):
```bash
chmod +x run-local.sh
```

**Review local files:**
```bash
./run-local.sh                     # Light review
./run-local.sh --mode=deep         # Deep review
./run-local.sh --post              # Post results to GitHub
```

**Review a PR:**
```bash
./run-local.sh --pr=42             # Review PR #42
./run-local.sh --pr=42 --post      # Review and post comment
```

**Deep review with fixes:**
```bash
./run-local.sh --mode=deep --post  # Creates PR with fixes
```

### Direct Node.js Usage

For all platforms, you can call the AI review script directly:

```bash
# Light review of local changes
node scripts/ai-review.js --mode=light

# Review specific PR  
node scripts/ai-review.js --mode=light --pr=42

# Deep review with automated fixes
node scripts/ai-review.js --mode=deep --create-pr

# Post results to GitHub
node scripts/ai-review.js --mode=light --post

# Override repository
node scripts/ai-review.js --mode=light --repo=owner/repo
```

### OAuth-Authenticated API Reviews

When testing the full **Echo ↔ Weaver ↔ Opus** flow:

```bash
# Test with OAuth token (requires Weaver backend)
WEAVER_TOKEN="your_jwt_token" node scripts/ai-review.js --mode=light --api-mode

# Test job creation and tracking
node scripts/ai-review.js --mode=deep --create-job --track-status
```

## GitHub Actions Integration

The **Opus** (DevOps) actor manages automated GitHub workflows that integrate with **Weaver** for job management and **Echo** for AI analysis.

### AI Review Workflow (PR Analysis)

**.github/workflows/ai-review.yml**
```yaml
name: AI Light Review

on:
  pull_request:
    types: [opened, synchronize, reopened]

permissions:
  pull-requests: write
  contents: write
  issues: write

jobs:
  light-review:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          token: ${{ secrets.AI_REVIEW_PAT || secrets.GITHUB_TOKEN }}

      - name: Download tracking ID artifact
        uses: actions/download-artifact@v4
        with:
          name: opus-tracking-id
          path: .
        continue-on-error: true
          
      - uses: actions/setup-node@v4
        with: 
          node-version: '20'
        
      - run: npm ci
      
      - name: Run AI Light Review
        env:
          OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          WEAVER_BASE: ${{ secrets.WEAVER_BASE }}
          WEAVER_TOKEN: ${{ secrets.WEAVER_TOKEN }}
        run: node scripts/ai-review.js --mode=light
        
      - name: Update review history and tracking
        run: |
          git config --local user.email "41898282+github-actions[bot]@users.noreply.github.com"
          git config --local user.name "github-actions[bot]"
          git add .github/ai-review-history.json
          if git diff --staged --quiet; then
            echo "No changes to review history"
          else
            git commit -m "Update AI review history [skip ci]"
            git push
          fi

      - name: Upload tracking ID artifact  
        uses: actions/upload-artifact@v4
        with:
          name: opus-tracking-id
          path: opus-tracking-id.txt
        continue-on-error: true
```

### Dynamic Static Builder Workflow

**.github/workflows/dynamic-static-builder.yml**
```yaml
name: Dynamic Static Builder

on:
  repository_dispatch:
    types: [dsb.new_article]

jobs:
  build_publish:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Fetch job artifact from Weaver
        env:
          WEAVER_BASE: ${{ secrets.WEAVER_BASE }}
          WEAVER_TOKEN: ${{ secrets.WEAVER_TOKEN }}
        run: |
          JOB_ID="${{ github.event.client_payload.job_id }}"
          echo "Fetching job artifact for: $JOB_ID"
          curl -sS -H "Authorization: Bearer $WEAVER_TOKEN" \
               "$WEAVER_BASE/api/getJobStatus.php?id=$JOB_ID" -o /tmp/job-status.json
          
          # Extract article content from job payload
          cat /tmp/job-status.json | jq '.payload' > /tmp/article.json

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install dependencies
        run: npm ci

      - name: Render article content
        id: render
        run: |
          # Process the article content using OpusProcessor
          node scripts/OpusProcessor.js --process=/tmp/article.json
          
          # Extract metadata for PR creation
          TITLE=$(cat /tmp/article.json | jq -r '.article.title // "New Article"')
          echo "article_title=$TITLE" >> $GITHUB_OUTPUT

      - name: Update job status to publishing
        env:
          WEAVER_BASE: ${{ secrets.WEAVER_BASE }}
          WEAVER_TOKEN: ${{ secrets.WEAVER_TOKEN }}
        run: |
          JOB_ID="${{ github.event.client_payload.job_id }}"
          curl -X POST "$WEAVER_BASE/api/updateJob.php" \
            -H "Authorization: Bearer $WEAVER_TOKEN" \
            -H "Content-Type: application/json" \
            -d "{\"id\":\"$JOB_ID\",\"status\":\"publishing\"}"

      - name: Create branch, commit, and PR
        uses: peter-evans/create-pull-request@v6
        with:
          commit-message: "feat(article): add ${{ steps.render.outputs.article_title }} [job:${{ github.event.client_payload.job_id }}]"
          branch: "dsb/${{ github.event.client_payload.job_id }}"
          title: "Add article: ${{ steps.render.outputs.article_title }} [job:${{ github.event.client_payload.job_id }}]"
          body: |
            Automated article publication via Dynamic Static Builder
            
            **Job ID**: `${{ github.event.client_payload.job_id }}`
            **Branch**: `${{ github.event.client_payload.branch || 'main' }}`
            **Base Path**: `${{ github.event.client_payload.base_path || 'articles/' }}`
            
            This PR was created automatically by the **Opus** actor in response to a job initiated by **Echo** via **Weaver**.
          base: ${{ github.event.client_payload.branch || 'main' }}

      - name: Update job status to building
        if: success()
        env:
          WEAVER_BASE: ${{ secrets.WEAVER_BASE }}
          WEAVER_TOKEN: ${{ secrets.WEAVER_TOKEN }}
        run: |
          JOB_ID="${{ github.event.client_payload.job_id }}"
          curl -X POST "$WEAVER_BASE/api/updateJob.php" \
            -H "Authorization: Bearer $WEAVER_TOKEN" \
            -H "Content-Type: application/json" \
            -d "{\"id\":\"$JOB_ID\",\"status\":\"building\"}"

      - name: Update job status to error
        if: failure()
        env:
          WEAVER_BASE: ${{ secrets.WEAVER_BASE }}
          WEAVER_TOKEN: ${{ secrets.WEAVER_TOKEN }}
        run: |
          JOB_ID="${{ github.event.client_payload.job_id }}"
          curl -X POST "$WEAVER_BASE/api/updateJob.php" \
            -H "Authorization: Bearer $WEAVER_TOKEN" \
            -H "Content-Type: application/json" \
            -d "{\"id\":\"$JOB_ID\",\"status\":\"error\"}"
```

### Deep Review Workflow (Scheduled)

**.github/workflows/ai-deep-review.yml**
```yaml
name: AI Deep Review

on:
  workflow_dispatch:  # Manual trigger
  schedule:
    - cron: '0 2 * * 0'  # Weekly on Sunday at 2am

permissions:
  contents: write
  pull-requests: write
  issues: write

jobs:
  deep-review:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          token: ${{ secrets.AI_REVIEW_PAT || secrets.GITHUB_TOKEN }}
      
      - uses: actions/setup-node@v4
        with: 
          node-version: '20'
      
      - run: npm ci
      
      - name: Run AI Deep Review with Fixes
        env:
          OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          WEAVER_BASE: ${{ secrets.WEAVER_BASE }}
          WEAVER_TOKEN: ${{ secrets.WEAVER_TOKEN }}
          BASE_BRANCH: ${{ github.event.repository.default_branch }}
        run: node scripts/ai-review.js --mode=deep --create-pr
```

### Site Building Workflow

**.github/workflows/build-site.yml**
```yaml
name: Build Site

on:
  push:
    branches: [main]
  pull_request:
    types: [closed]
    branches: [main]

jobs:
  build:
    if: github.event_name == 'push' || github.event.pull_request.merged == true
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          
      - run: npm ci
      
      - name: Build related articles data
        run: npm run build:site
        
      - name: Update job status to live (if applicable)
        env:
          WEAVER_BASE: ${{ secrets.WEAVER_BASE }}
          WEAVER_TOKEN: ${{ secrets.WEAVER_TOKEN }}
        run: |
          # Extract job ID from PR title if present
          if [[ "${{ github.event.head_commit.message }}" =~ \[job:([^\]]+)\] ]]; then
            JOB_ID="${BASH_REMATCH[1]}"
            echo "Updating job $JOB_ID to live status"
            curl -X POST "$WEAVER_BASE/api/updateJob.php" \
              -H "Authorization: Bearer $WEAVER_TOKEN" \
              -H "Content-Type: application/json" \
              -d "{\"id\":\"$JOB_ID\",\"status\":\"live\"}"
          fi
```

## Review Modes

The AI review system supports different analysis modes depending on the scope and requirements:

### Light Review
- **Purpose**: Quick analysis of recent changes or PR diffs
- **Scope**: Changed files only
- **Speed**: Fast execution (2-5 minutes)
- **Trigger**: Every PR via GitHub Actions
- **Actor Flow**: Cantor → Echo → (analysis) → GitHub comment
- **Focus Areas**:
  - Syntax errors and obvious bugs
  - Basic accessibility issues
  - Simple performance problems
  - Code style consistency

### Deep Review  
- **Purpose**: Comprehensive analysis of entire codebase
- **Scope**: All project files (up to token limit)
- **Speed**: Thorough execution (10-30 minutes)
- **Trigger**: Scheduled weekly or manual
- **Actor Flow**: Cantor → Echo → Weaver → Opus (with potential fixes)
- **Focus Areas**:
  - Architectural issues and patterns
  - Security vulnerabilities
  - Performance optimization opportunities
  - SEO and accessibility compliance
  - Content quality and grammar
  - Release readiness checklist

### API Integration Review
- **Purpose**: Test full four-actor workflow including OAuth
- **Scope**: API endpoints, authentication, job management
- **Speed**: Variable (depends on API response times)
- **Trigger**: Manual testing or CI/CD integration
- **Actor Flow**: Cantor → Echo → Weaver → Opus → status tracking

## Review Focus Areas

### 1. **Correctness & Safety**
- Broken HTML markup or malformed JSON
- JavaScript runtime errors
- Security vulnerabilities (XSS, injection attacks)
- Authentication and authorization issues

### 2. **Accessibility (WCAG Compliance)**
- Semantic HTML structure
- ARIA attributes and labels
- Keyboard navigation support
- Color contrast and visual accessibility
- Screen reader compatibility

### 3. **Performance**
- Load times and Core Web Vitals
- Render-blocking resources
- Image optimization
- Bundle size analysis
- Caching strategies

### 4. **SEO (Search Engine Optimization)**
- Meta tags and structured data
- Heading hierarchy (H1-H6)
- URL structure and canonicalization
- Sitemap and robots.txt
- Internal linking structure

### 5. **Data Integrity**
- JSON schema validation
- API response format consistency
- Database query optimization
- Content synchronization

### 6. **Content Quality**
- Spelling and grammar checking
- Content structure and readability
- Brand voice consistency
- Link integrity and accuracy

### 7. **Release Checklist** (Deep Mode Only)
- Build process validation
- Environment configuration
- Deployment readiness
- Monitoring and alerting setup
- Documentation completeness

## API Integration

The AI review system integrates with the **Weaver** API for job management and status tracking:

### Job-Based Reviews

```bash
# Create a review job via Weaver API
curl -X POST "https://webbness.net/api/insertJob.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "review-job-' $(date +%s) '",
    "status": "pending", 
    "created_at": "' $(date -u +%Y-%m-%dT%H:%M:%SZ) '",
    "updated_at": "' $(date -u +%Y-%m-%dT%H:%M:%SZ) '",
    "payload": {
      "repository": "owner/repo",
      "branch": "feature/new-content",
      "review_type": "deep",
      "focus_areas": ["accessibility", "performance", "seo"]
    }
  }'
```

### Status Tracking

```bash
# Monitor review progress
curl -H "Authorization: Bearer $JWT_TOKEN" \
     "https://webbness.net/api/getJobStatus.php?id=review-job-123456"
```

### OAuth-Authenticated Reviews

When running reviews through the full actor workflow:

1. **Echo** authenticates with **Weaver** via OAuth 2.0
2. **Echo** creates review jobs through the API
3. **Weaver** manages job lifecycle and status
4. **Opus** executes the actual review process
5. Results are communicated back through **Weaver** to **Echo**

## Configuration

## Troubleshooting

### Common Issues

**"GITHUB_TOKEN not set"**
- Create a `.env` file with your tokens
- Or export them: `export GITHUB_TOKEN=your_token`
- For GitHub Actions, ensure `GITHUB_TOKEN` is available in secrets

**"OPENAI_API_KEY not found"**
- Add `OPENAI_API_KEY=sk-...` to `.env` file
- Verify the API key is valid and has sufficient credits
- Check API key permissions for GPT-4 access

**"Weaver API authentication failed"**
- Verify `WEAVER_BASE` and `WEAVER_TOKEN` in environment
- Test OAuth flow using `scripts/Base64Encoding.js`
- Check JWT token expiration and scopes

**"Repository must be specified"**
- Add `GITHUB_REPOSITORY=owner/repo` to `.env`
- Or use `--repo=owner/repo` command flag
- Ensure repository exists and is accessible

**Rate limiting or quota exceeded**
- OpenAI: Check API usage and billing limits
- GitHub: Use GitHub App instead of PAT for higher limits
- Add delays between requests if hitting rate limits

**Token budget exceeded**
- The script monitors token usage (default 32K limit for GPT-4)
- Automatically stops adding files when approaching limit
- Consider using `--mode=light` for smaller token usage

**OAuth flow testing failures**
- Verify Google OAuth credentials are configured
- Check redirect URIs match exactly
- Ensure `WEAVER_OAUTH_CLIENT_ID` matches API configuration
- Test Base64URL encoding with provided utility

**GitHub Actions workflow failures**
- Check all required secrets are configured:
  - `OPENAI_API_KEY`
  - `AI_REVIEW_PAT` (or use `GITHUB_TOKEN`)
  - `WEAVER_BASE`
  - `WEAVER_TOKEN`
- Verify Node.js version compatibility (requires 20+)
- Check workflow permissions for PR comments and content writes

### Debugging OAuth Integration

**Test OAuth state encoding:**
```bash
node scripts/Base64Encoding.js
```

**Verify Weaver API connectivity:**
```bash
curl -I "https://webbness.net/api/getAllJobs.php"
```

**Test JWT token validation:**
```bash
# Replace YOUR_JWT_TOKEN with actual token
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "https://webbness.net/api/getAllJobs.php" \
     -X POST \
     -H "Content-Type: application/json" \
     -d '{"status": "*"}'
```

**Debug repository dispatch:**
```bash
# Check if GitHub received the dispatch event
curl -H "Authorization: token $GITHUB_TOKEN" \
     -H "Accept: application/vnd.github.v3+json" \
     "https://api.github.com/repos/owner/repo/dispatches"
```

### Best Practices

#### For Development Teams
1. **Run light reviews locally** before pushing to PR
2. **Schedule deep reviews weekly** for comprehensive analysis
3. **Test OAuth flow** after environment changes
4. **Monitor job status** through Weaver API during development
5. **Keep tokens secure** and rotate regularly

#### For CI/CD Integration
1. **Use GitHub App authentication** instead of PATs when possible
2. **Set up proper secret management** for all four actor components
3. **Monitor workflow execution times** and optimize as needed
4. **Implement fallback strategies** for API failures
5. **Track job completion** through Weaver status endpoints

#### For Content Management
1. **Use job IDs** for tracking article publication workflow
2. **Embed job IDs in PR titles** for correlation: `[job:123456]`
3. **Monitor job state transitions** from pending → live
4. **Handle error states** gracefully with appropriate notifications
5. **Test full actor workflow** before production deployment

### Actor-Specific Debugging

#### 🎭 **Cantor (Human) Issues**
- Environment setup problems
- Authentication configuration
- Local script execution permissions

#### 🤖 **Echo (AI GPT) Issues**  
- OpenAI API connectivity
- Token budget management
- Response parsing and formatting

#### 🕸️ **Weaver (API Gateway) Issues**
- OAuth flow failures
- JWT token validation
- Job management database errors
- CORS and security headers

#### ⚙️ **Opus (GitHub DevOps) Issues**
- Workflow dispatch failures  
- Repository permissions
- Branch and PR creation errors
- Build and deployment issues

### Monitoring and Observability

**Review History Tracking:**
- `.github/ai-review-history.json` - GitHub Actions reviews
- `.github/ai-review-history-local.json` - Local reviews
- Helps prioritize unreviewed or outdated files

**Job Status Monitoring:**
```bash
# Get all active jobs
curl -H "Authorization: Bearer $JWT_TOKEN" \
     -X POST "https://webbness.net/api/getAllJobs.php" \
     -d '{"status": "pending,publishing,building"}'

# Monitor specific job
curl -H "Authorization: Bearer $JWT_TOKEN" \
     "https://webbness.net/api/getJobStatus.php?id=job-123456"
```

**GitHub Actions Logs:**
- Check workflow run logs for detailed error information
- Monitor artifact uploads for tracking ID persistence
- Review job summaries for performance metrics