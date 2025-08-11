# Echo – Opus/Weaver API Quick Reference

## General Notes
- All endpoints live at: https://webbness.net/api/
- Job states: pending, publishing, merging, waiting, actionrequired, building, live, error, cancel, cancelled, stale
- Terminal states: live, error, cancelled
- Use `{ "status": "*" }` in getAllJobs to retrieve ALL jobs
- Always embed `[job:{jobId}]` in PR titles for publishArticle

---

## Endpoints

### 1. publishArticle
POST /publish.php
Publishes content to the configured GitHub repository.

**Required:**
- repository (string)
- github_token (string)
- filename (string)
- content (string)

**Common optional:**
- project, user, branch, base_path, base_url
- metadata: title, tags, description, relatedArticles[]
- overrides: branch, base_url, auto_merge, delete_branch, build_hook
- overrides.pull_request: enabled, base, title, body (title should contain `[job:{jobId}]`)

**Response:**
- status (string)
- url (string)
- commitHash (string)
- pullRequestUrl (string)
- build_triggered (boolean)

---

### 2. getJobStatus
GET /getJobStatus.php?id={jobId}
Returns status of a specific publishing job.

**Query params:**
- id (string, required)

**Response:**
- id
- status
- created_at (date-time)
- updated_at (date-time)
- payload (JSON string: article metadata)

---

### 3. getAllJobs
POST /getAllJobs.php
Retrieves all jobs filtered by status.

**Body:**
- status (string, required) – comma-separated list of statuses OR "*" for all jobs

**Response:**
Array of job objects:
- id
- status
- created_at
- updated_at
- payload (JSON string: article metadata)

---

### 4. insertJob
POST /insertJob.php
Creates a new publishing job.

**Body:**
- id (string, required)
- status (string, required)
- created_at (date-time, required)
- updated_at (date-time, required)
- payload.article:
  - title (string, required)
  - url (string, required)
  - tags[] (optional)
  - snippet (optional)

**Response:**
- status
- message
- job_id

---

### 5. updateJobStatus
POST /updateJob.php
Updates the status of an existing job.

**Body:**
- id (string, required)
- status (string, required)

**Response:**
- status

---

## Recommended Usage Flow

1. **Session start:**  
   Call getAllJobs `{ "status": "*" }` → cache results for quick lookups

2. **Publishing:**  
   - insertJob with `pending`
   - publishArticle with PR title `[job:{jobId}]`
   - updateJobStatus → `publishing`

3. **Tracking:**  
   Poll getJobStatus until `live`, `error`, or `cancelled`

4. **Cancel:**  
   updateJobStatus → `cancel` (backend will move to `cancelled`)

