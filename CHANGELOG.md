# Changelog

## Unreleased (Refactor to API Key + Session Model)

### Added
- ApiKeyAuth security scheme (header `X-API-Key`) for service (Echo -> Weaver) requests.
- SessionBearer scheme (ephemeral job-scoped JWT) returned as `weaver_session` from `POST /jobs` when enabled.
- Asset handling pipeline: base64/data URI materialization, markdown placeholder replacement, structured placement rendering.
- Pagination for `POST /jobs/list` (`limit`, `offset`) plus response wrapper with `items` and `pagination` metadata.
- Persistent file cache for GitHub App installation IDs.

### Changed
- `POST /jobs` now accepts `{ "payload": ContentJobPayload }` only (legacy body still supported server-side but deprecated) and returns `{ status, job_id, weaver_session? }`.
- `GET /job/status` replaces former `/jobs/{id}` path; uses query `?id=` and optional session bearer (no API key required).
- `POST /jobs/list` now requires API key instead of bearer OAuth token; optional session narrows to its specific job.
- `POST /jobs/update` now requires API key; optional session must match job when provided.
- `GET /jobs/artifact` replaces `/jobs/{id}/artifact`; requires API key; optional HMAC headers and session token.
- OpenAPI specification updated accordingly; removed legacy OAuth flows.

### Removed
- OAuth endpoints `/oauth/authorize` and `/oauth/token` from OpenAPI spec.
- Legacy bearer scope requirements (jobs:read, jobs:write, jobs:admin) from active endpoints.

### Deprecated
- Legacy insert job payload shape with top-level `owner`, `repo`, `article` (still accepted but will be removed in a future release). Use `payload.deployment.repository`, etc.

### Security
- Unified API key gate for all mutating/listing endpoints.
- Optional session JWT binds follow-up operations to a single job for additional safety.
- HMAC on artifact retrieval now optional (still supported for external validation); API key primary auth.

### Internal
- Simplified test harness for insert job auth/asset handling.
- Added disk-backed cache under `api/../cache` for GitHub installation ID to reduce API calls.

### Next (Planned)
- Extend session-bound operations for artifact retrieval enforcement.
- Add token revocation / rotation support for API keys.
- Introduce webhook endpoint for GitHub App events (installation, repository changes).
- Formal PHPUnit tests restoration and CI workflow.

