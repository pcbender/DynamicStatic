# Opus - DevOps Automation Actor

Opus is the DevOps automation actor in the Dynamic Static AI CMS architecture. It handles GitHub Actions workflows, deployment automation, and continuous integration/delivery processes.

## Architecture Overview

Opus operates as the fourth actor in the Dynamic Static system:
- **Cantor**: The human user creating content
- **Echo**: AI co-creator (OpenAI Custom GPT)
- **Weaver**: Authentication and job management backend
- **Opus**: DevOps automation and deployment (this component)

## GitHub Workflows

The following GitHub Actions workflows are automatically deployed from `.github/workflows/` and reference scripts in the root `scripts/` folder:

### 1. Dynamic Static Builder (`dynamic-static-builder.yml`)
**Purpose**: Automatically processes new articles dispatched from Weaver
**Trigger**: Repository dispatch event `dsb.new_article`
**Scripts Used**: `scripts/render.js`
**Output**: Adds new article files to `dist/articles/` or `dist/posts/`
**Flow**:
1. Receives job ID from Weaver via repository dispatch
2. Fetches article content from Weaver API
3. Renders article using `scripts/render.js`
4. Writes article files to `dist/` directory
5. Creates pull request with new content

### 2. Build Site (`build-site.yml`)
**Purpose**: Builds the static site when content changes
**Trigger**: Push/PR to content or dist directories
**Scripts Used**: `npm run build:site` (references root package.json)
**Output**: Generates/updates files in `dist/` directory
**Flow**:
1. Sets up Node.js environment
2. Installs dependencies
3. Runs site build process
4. Outputs built site to `dist/` folder

### 3. AI Light Review (`ai-review.yml`)
**Purpose**: Provides automated AI code review on pull requests
**Trigger**: Pull request events (opened, synchronize, reopened)
**Scripts Used**: `scripts/ai-review.js`, `scripts/OpusProcessor.js`
**Flow**:
1. Downloads previous tracking data
2. Runs lightweight AI analysis via `scripts/ai-review.js`
3. Posts review comments
4. Stores tracking information using `scripts/OpusProcessor.js`

### 4. AI Deep Release Review (`ai-deep-review.yml`)
**Purpose**: Comprehensive AI review for releases and tags
**Trigger**: Release creation, tag pushes, manual dispatch
**Scripts Used**: External validation tools + `scripts/ai-review.js` (deep mode)
**Analysis Target**: Built `dist/` directory and source files
**Flow**:
1. Builds the site (populates `dist/` folder)
2. Validates JSON schemas
3. Performs link checking on `dist/` HTML files
4. Runs HTML validation on `dist/` content
5. Executes Lighthouse performance audit on `dist/` served locally
6. Conducts deep AI analysis

### 5. Deploy (`main.yml`)
**Purpose**: Deploys built site to production via rsync
**Trigger**: Push to main branch or manual dispatch
**Scripts Used**: Built-in rsync commands
**Source**: `dist/` directory contents
**Flow**:
1. Installs SSH tools
2. Establishes secure connection
3. Synchronizes `dist/` folder contents to remote server
4. Mirrors entire `dist/` directory structure to production

## GitHub Secrets Setup

### Required Secrets

Configure the following secrets in your GitHub repository (`Settings > Secrets and variables > Actions`):

#### Weaver API Integration
```
WEAVER_BASE=https://webbness.net
WEAVER_TOKEN=your_hmac_key_or_jwt_private_key
```

#### OpenAI Integration
```
OPENAI_API_KEY=sk-your-openai-api-key
```

#### GitHub Access
```
AI_REVIEW_PAT=ghp_your_personal_access_token
```
*Note: If not provided, will fallback to `GITHUB_TOKEN`*

#### SSH Deployment
```
SSH_HOST=your.server.com
SSH_USER=your_username
SSH_PASSWORD=your_ssh_password
SSH_PORT=22
```
*Note: SSH_PORT is optional, defaults to 22*

### Required Variables

Configure the following repository variables (`Settings > Secrets and variables > Actions > Variables`):

```
REMOTE_PATH=/path/to/your/site/directory
```

## Build and Deployment Pipeline

### Content-to-Deployment Flow
1. **Content Creation**: Cantor creates content through Echo (Custom GPT)
2. **Job Creation**: Echo submits job to Weaver API
3. **Article Processing**: Opus receives dispatch → `scripts/render.js` → writes to `dist/`
4. **Site Building**: Content changes trigger build → updates `dist/` directory
5. **Deployment**: Main branch pushes → `dist/` contents deployed to production

### Local Development
```bash
# Install dependencies
npm install

# Build site locally
npm run build:site

# Serve dist/ folder locally for testing
npx http-server ./dist -p 8080
```

### Build Triggers
- **Manual Build**: `npm run build:site`
- **Content Changes**: Push/PR to `dist/`, `content/` directories
- **Article Processing**: Repository dispatch from Weaver
- **Release Build**: Tag creation or release publication

## New User Setup Flow

For a new Dynamic Static user via the Custom GPT, the setup process is streamlined:

### 1. Fork Repository
```bash
# User forks the GitHub repository from the Custom GPT interface
# or manually via GitHub web interface
```

### 2. Configure GitHub Secrets
Set up the required secrets as documented in the "GitHub Secrets Setup" section below.

### 3. Deploy Web Server
Copy the contents of the `dist/` folder to their web server:
```bash
# Simple deployment - just copy dist/ contents
cp -r dist/* /var/www/html/
# or upload via FTP, rsync, etc.
```

### 4. Ready to Create
The system is immediately ready - no databases to configure, no complex setup. The welcome page in `dist/index.html` explains how everything works.

### Clean Starting Point
The `dist/` folder provides a clean "Welcome to Dynamic Static AI CMS" page that:
- Explains the four-actor architecture
- Demonstrates automatic metadata management  
- Shows how JSON files update automatically
- Provides clear next steps for content creation
- Emphasizes the "no database, no muss or fuss" philosophy

## Setup Instructions
```bash
git clone https://github.com/your-username/DynamicStatic.git
cd DynamicStatic
```

### 2. Install Dependencies
```bash
npm install
```

### 3. Configure GitHub Secrets
Navigate to `Settings > Secrets and variables > Actions` in your GitHub repository and add all required secrets listed above.

### 4. Configure Repository Variables
In the same settings area, go to the `Variables` tab and add `REMOTE_PATH`.

### 5. Enable GitHub Actions
Ensure GitHub Actions are enabled in your repository settings.

### 6. Test Workflows

#### Test AI Review
Create a pull request to trigger the AI review workflow:
```bash
git checkout -b test-feature
echo "# Test" > test.md
git add test.md
git commit -m "test: add test file"
git push origin test-feature
```

#### Test Dynamic Static Builder
Trigger via Weaver API (requires authentication):
```bash
curl -X POST https://api.github.com/repos/your-username/your-repo/dispatches \
  -H "Authorization: token $GITHUB_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  -d '{
    "event_type": "dsb.new_article",
    "client_payload": {
      "job_id": "test-job-123",
      "branch": "main",
      "base_path": ""
    }
  }'
```

#### Test Deployment
Push to main branch to trigger deployment:
```bash
git checkout main
git push origin main
```

## Workflow Security

### Authentication Scopes
- **AI_REVIEW_PAT**: Requires `repo`, `pull_requests:write`, `contents:write`
- **WEAVER_TOKEN**: HMAC key for authenticating with Weaver API
- **OPENAI_API_KEY**: OpenAI API access for AI reviews

### Network Security
- All API calls use HTTPS
- SSH connections use password authentication (consider upgrading to key-based)
- Secrets are encrypted at rest in GitHub

### Access Control
- Workflows run only on authorized events
- Repository dispatch requires GitHub API token
- Weaver API uses bearer token authentication

## Customization

### Adding New Workflows
1. Create new `.yml` file in `.github/workflows/` directory
2. Reference existing scripts in `scripts/` folder or create new ones
3. Define triggers, jobs, and steps
4. Add required secrets/variables in GitHub repository settings
5. Push to repository - workflow will be automatically deployed
6. Test with pull request or appropriate trigger

### Modifying Workflows
1. Edit workflow files in `.github/workflows/` directory
2. Workflows are automatically deployed when changes are pushed to GitHub
3. Update any referenced scripts in root `scripts/` folder as needed
4. Add required secrets/variables in GitHub repository settings
5. Test changes in feature branch before merging

## File Organization

### Build Output Directory
- `dist/` - **Built static site ready for deployment**
  - `dist/index.html` - Main site homepage
  - `dist/articles/` - Generated article pages
  - `dist/posts/` - Generated blog posts
  - `dist/css/` - Compiled stylesheets
  - `dist/img/` - Optimized images and assets
  - `dist/data/` - JSON data files for dynamic content
  - `dist/favicon.ico` - Site favicon

### Workflow Files (Auto-deployed)
- `.github/workflows/*.yml` - GitHub Actions workflows (automatically deployed when pushed)
- `.github/ai-review-history.json` - AI review tracking data

### Referenced Scripts
The workflows reference automation scripts in the root directory:
- `scripts/ai-review.js` - AI code review logic
- `scripts/OpusProcessor.js` - Tracking and processing utilities  
- `scripts/render.js` - Article rendering and formatting
- `scripts/Base64Encoding.js` - OAuth utility functions
- `scripts/build.js` - Build system logic
- `scripts/opusPublisherClient.js` - Publishing client

### Configuration Files
- `package.json` - Node.js dependencies and build scripts
- `project-config.json` - Project-specific configuration
- Root deployment files (`run-local.*` scripts)

## Troubleshooting

### Common Issues

**Workflow fails with authentication error**
- Verify all required secrets are configured
- Check token permissions and expiration
- Ensure API endpoints are accessible

**Deploy fails with SSH error**
- Verify SSH credentials and host accessibility
- Check remote path permissions
- Confirm SSH_PORT matches server configuration

**AI review not posting comments**
- Verify OPENAI_API_KEY is valid and has credits
- Check AI_REVIEW_PAT permissions
- Review workflow logs for specific errors

**Article rendering fails**
- Verify Node.js dependencies are installed
- Check script paths and permissions
- Validate input JSON format from Weaver

### Debug Steps
1. Check workflow run logs in GitHub Actions tab
2. Verify secret values (without exposing them)
3. Test individual components locally
4. Review error messages and stack traces
5. Check external service status (OpenAI, Weaver, SSH host)

## Integration with Dynamic Static Architecture

Opus integrates with other actors:

**From Weaver**: Receives job dispatch events with article data
**To GitHub**: Creates pull requests and manages repository state
**From Echo**: Processes AI-generated content through workflows
**To Cantor**: Provides deployment status and review feedback

This automation enables the complete content-to-deployment pipeline for the Dynamic Static AI CMS.
