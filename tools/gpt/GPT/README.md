# Echo - Custom GPT Configuration

This directory contains all the configuration files and specifications needed to set up **Echo**, the custom GPT that serves as the AI co-creator in the Dynamic Static AI CMS four-actor architecture.

## 🎭 Echo's Role in the System

```
🎭 Cantor (Human) ↔ 🤖 Echo (AI GPT) ↔ 🕸️ Weaver (API Gateway) ↔ ⚙️ Opus (GitHub DevOps)
```

**Echo** is the AI co-creator that:
- Generates and refines content for static site publishing
- Manages the complete content workflow from creation to deployment
- Interfaces with the Weaver API for authentication and job management
- Tracks publishing status and communicates with humans in natural language

## 📁 Files Overview

### [`Configuration.md`](./Configuration.md)
**Purpose**: Complete custom GPT instructions and behavior definition

**Contains**:
- GPT name, description, and personality settings
- Relational Design principles (creativity levels, response shapes)
- Complete workflow procedures and status management
- Four-actor role definitions and interaction patterns
- Error handling, polling cadence, and notification templates
- Conversation starters for user interaction

**Usage**: Copy this content into the "Instructions" field when creating the custom GPT in OpenAI's platform.

### [`openapi.json`](./openapi.json)
**Purpose**: API specification for Echo's integration with Weaver backend

**Contains**:
- Complete REST API definition for Dynamic Static Builder
- Authentication and authorization schemas
- Job management endpoints (insertJob, getJobStatus, getAllJobs, updateJob)
- Content publishing specifications
- GitHub integration parameters

**Usage**: Upload this file as an "Action" in the custom GPT configuration to enable API calls.

### [`project-config.json`](./project-config.json)
**Purpose**: Template and example project configuration for content publishing

**Contains**:
- Repository information (owner, repo, branch)
- Publishing paths and base URLs  
- Build hook configurations
- Project-specific defaults

**Usage**: 
- **Current**: Cantor uploads this file at the start of each chat session
- **Interactive**: Echo requests these values if no file is uploaded
- **Future**: When OpenAI enables persistent storage, settings will be cached across sessions

**Session Workflow**:
1. Cantor uploads project-config.json → Echo parses and caches settings
2. OR Echo interactively requests: repository, branch, base_path, base_url, etc.
3. Echo confirms configuration before first publish
4. Settings persist for the current chat session only

## 🛠️ Setting Up Echo Custom GPT

### Step 1: Create Custom GPT
1. Go to [OpenAI GPT Builder](https://chat.openai.com/gpts/editor)
2. Click "Create a GPT"
3. Enter the name: **Echo – Dynamic Static Builder**

### Step 2: Configure Instructions
1. Copy the entire content from [`Configuration.md`](./Configuration.md)
2. Paste into the "Instructions" field
3. Ensure all Relational Design principles and workflow procedures are included

### Step 3: Add API Actions
1. Click "Create new action"
2. Upload [`openapi.json`](./openapi.json) or paste its contents
3. Configure authentication:
   - **Type**: OAuth 2.0
   - **Authorization URL**: `https://webbness.net/oauth/authorize`
   - **Token URL**: `https://webbness.net/oauth/token`
   - **Client ID**: `dsb-gpt`
   - **Scope**: `jobs:read jobs:write`

### Step 4: Configure Project Settings
**Current Approach** (per session):
1. Cantor uploads [`project-config.json`](./project-config.json) at start of each chat
2. OR Echo will interactively request configuration values
3. Settings are cached in session memory only

**Alternative Setup**:
- Upload project-config.json as a knowledge file (limited persistence)
- Echo falls back to interactive prompts when needed

**Future Enhancement**:
- When OpenAI enables GPT persistent storage, configuration will be cached across sessions
- Users will only need to configure once until settings change

### Step 5: Set Conversation Starters
Use the conversation starters from `Configuration.md`:
- "Upload your project-config.json to get started."
- "Let's create a new article for the site."
- "I have an idea—let's shape it into publishable form."
- "Use Creativity: High and Shape: Balanced to write something evocative."
- "Let's generate metadata and related articles."

## 🔧 Configuration Workflow

### Current Session-Based Approach

Due to current OpenAI limitations, Echo uses a **session-based configuration system**:

#### **Option 1: File Upload** (Recommended)
```
1. Cantor starts new chat session
2. Cantor uploads project-config.json
3. Echo parses and caches settings
4. Configuration persists for entire session
5. Ready to create and publish content
```

#### **Option 2: Interactive Configuration**
```
1. Cantor starts new chat without config file
2. Echo prompts for required settings:
   - Repository (owner/repo)
   - Branch (default: main)  
   - Base path (default: articles/)
   - Base URL (e.g., https://relationaldesign.ai)
   - Build hook (optional)
3. Echo confirms configuration
4. Ready to create and publish content
```

### Configuration Prompts

When no config file is uploaded, Echo will ask:

> **"I need your project settings to publish content. Please provide:"**
> - **Repository** (owner/repo format): 
> - **Target branch** (default: main): 
> - **Content directory** (default: articles/): 
> - **Site base URL**: 
> - **Build hook URL** (optional): 

Before first publish, Echo confirms:
> **"Ready to publish to {repository}/{branch} in {base_path}/ with base URL {base_url}. Confirm?"**

### Future Enhancements

**When OpenAI enables persistent GPT storage:**
- Configuration will be cached across sessions
- One-time setup per project
- Automatic updates when settings change
- Multi-project support with project switching

## 🔧 Configuration Updates

### Environment-Specific Settings
When deploying to different environments, update:

**Development**:
```json
{
  "branch": "develop",
  "base_url": "https://dev.relationaldesign.ai",
  "build_hook": "https://dev-api.example.com/deploy-hook"
}
```

**Production**:
```json
{
  "branch": "main", 
  "base_url": "https://relationaldesign.ai",
  "build_hook": "https://api.example.com/deploy-hook"
}
```

### API Server Updates
If Weaver backend URL changes, update:
1. `openapi.json` → `servers[0].url`
2. OAuth URLs in GPT action configuration
3. Any hardcoded references in instructions

## 🔐 Security Considerations

### OAuth Flow
- Echo authenticates via OAuth 2.0 with Google identity provider
- JWT tokens are scoped (`jobs:read`, `jobs:write`, `jobs:admin`)
- No long-term secrets stored in GPT configuration

### API Access
- All API calls go through Weaver's authentication layer
- Job management is user-scoped (users only see their own jobs)
- GitHub operations use GitHub App authentication (no personal tokens)

### Data Privacy
- No sensitive data should be stored in GPT instructions
- Project configuration contains only public repository information
- All API communications are HTTPS-encrypted

## 🔄 Workflow Integration

### Complete Publishing Flow
1. **Cantor** provides content direction to **Echo**
2. **Echo** generates content and metadata
3. **Echo** calls Weaver API to create job and publish content
4. **Weaver** authenticates request and dispatches to **Opus**
5. **Opus** handles GitHub operations (PR creation, builds, deployment)
6. **Echo** monitors job status and reports back to **Cantor**

### Job State Management
- **Echo** can set: `pending`, `publishing`, `waiting`, `actionrequired`, `cancel`, `error`
- **Opus** can set: `merging`, `building`, `live`, `cancelled`
- Terminal states: `live`, `error`, `cancelled`

### Error Handling
- Exponential backoff for API failures
- Human-friendly error messages
- Graceful degradation when API unavailable
- Two-way cancel protocol for job termination

## 🧪 Testing Echo

### Session Configuration Testing
```bash
# Test 1: File Upload Method
1. Start new chat with Echo
2. Upload project-config.json
3. Verify Echo confirms settings
4. Test content creation and publishing

# Test 2: Interactive Method  
1. Start new chat without config file
2. Echo should prompt for settings
3. Provide configuration interactively
4. Verify Echo confirms before publishing
```

### OAuth Flow Testing
```bash
# Test state encoding
node ../scripts/Base64Encoding.js

# Verify OAuth endpoints
curl "https://webbness.net/oauth/authorize?response_type=code&client_id=dsb-gpt&..."
```

### API Integration Testing
```bash
# Test job creation
curl -X POST "https://webbness.net/api/insertJob.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d '{"id":"test-job","status":"pending"}'

# Test job status retrieval  
curl -H "Authorization: Bearer $JWT_TOKEN" \
     "https://webbness.net/api/getJobStatus.php?id=test-job"
```

### End-to-End Testing
1. Start conversation with Echo
2. Request article creation
3. Verify OAuth authentication prompt
4. Complete authorization flow
5. Monitor job creation and status updates
6. Verify GitHub PR creation and workflow execution

## 📚 Additional Resources

- [Main Project README](../README.md) - Overall system architecture
- [Weaver Documentation](../Weaver/README.md) - API backend details
- [API Quick Reference](../Docs/API%20Quick%20Reference.md) - Endpoint documentation
- [AI Code Review Guide](../Docs/AI%20Code%20Review.md) - Development workflows

## 💡 Session Management Best Practices

### For Cantor (Users)

**Starting a New Session:**
1. **Always upload project-config.json first** for consistent configuration
2. **Verify Echo confirms settings** before proceeding with content creation
3. **Keep config file handy** for multiple sessions with same project

**During a Session:**
- Configuration persists throughout the entire chat session
- No need to re-upload or re-configure
- Echo will remember repository, branch, paths, etc.

**Multiple Projects:**
- Use different config files for different projects
- Start new sessions when switching between projects
- Clearly label config files (e.g., `relationaldesign-config.json`, `blog-config.json`)

### For Echo (AI Behavior)

**Session Initialization:**
- Check for uploaded config file at session start
- Parse and validate all required fields
- Cache settings in session memory
- Confirm configuration with Cantor

**Configuration Validation:**
```json
Required fields:
- project: string
- user: string  
- repository: string (owner/repo format)
- branch: string
- base_path: string
- base_url: string (valid URL)

Optional fields:
- build_hook: string (webhook URL)
```

**Interactive Fallback:**
- Gracefully handle missing config file
- Request settings in logical order
- Provide sensible defaults where appropriate
- Confirm complete configuration before first API call

## 🤝 Contributing

When updating Echo's configuration:

1. **Test thoroughly** in development environment
2. **Document changes** in relevant files
3. **Update API specifications** if endpoints change
4. **Verify OAuth flow** after configuration updates
5. **Test job lifecycle** end-to-end

---

**Echo** embodies the AI side of Relational Design, enabling seamless human-AI collaboration in content creation and publishing workflows.
