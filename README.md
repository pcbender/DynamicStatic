# Dynamic Static Starter

A minimal, production-safe starter to build and deploy a **Dynamic Static** site on your own server.

## What you get
- Build pipeline → `dist/`
- Service Worker caching for `/data/*.json`
- Safe deploy wrapper (`scripts/safe-rsync.sh`) with marker-file guard
- GitHub Actions: AI review (stub) → build → deploy (dev) → tag-guarded prod

## Quick start
1. **Use this template** to create your repo.
2. Set repo secrets for deploy (optional): `SSH_*`, `WEBROOT_*`.
3. Push to `develop` → dev deploy (dry-run first). Tag `v1.0.0` on `main` → prod (dry-run first).

Docs: see [docs/echo-orchestrator-guide.md](docs/echo-orchestrator-guide.md)
