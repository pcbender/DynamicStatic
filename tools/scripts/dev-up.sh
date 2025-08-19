#!/usr/bin/env bash
set -euo pipefail

# 1) Ensure TLS certs and APP_KEY
./tools/scripts/mkcert-local.sh

# 2) Start/rebuild containers
docker compose up -d --build

# 3) Clear stale config and run migrations
docker compose exec weaver-php php artisan config:clear
docker compose exec weaver-php php artisan migrate --force

# 4) Quick smoke
docker compose exec weaver-php php artisan weaver:smoke --demo
echo "Open https://localhost (login: demo@local.test / password)"
