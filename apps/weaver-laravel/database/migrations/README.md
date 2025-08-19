Migration Notes
===============

We differentiate between Laravel's internal queue tables (`jobs`, `failed_jobs`, etc.) and domain-specific job tracking. Domain jobs now live in `weaver_jobs` to avoid clashing with the framework `jobs` table.

Tables renamed in this refactor:
- job_payloads -> weaver_job_payloads
- job_statuses -> weaver_job_statuses

Foreign keys updated to reference `weaver_jobs`.

Removed duplicate migrations that attempted to recreate `users` and `jobs` tables.

When adding new domain job related structures, prefix tables with `weaver_`.
