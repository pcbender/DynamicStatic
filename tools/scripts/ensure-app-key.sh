#!/usr/bin/env bash
set -euo pipefail
ENV="apps/weaver-laravel/.env"
EXAMPLE="apps/weaver-laravel/.env.example"

# Ensure .env exists
[[ -f "$ENV" ]] || { [[ -f "$EXAMPLE" ]] && cp "$EXAMPLE" "$ENV" || touch "$ENV"; }

# If APP_KEY already looks valid, exit
current="$(grep -E '^APP_KEY=' "$ENV" || true)"
if [[ "$current" =~ ^APP_KEY=base64:.+ ]]; then
  echo "APP_KEY ok in $ENV"
  exit 0
fi

# Generate and set a fresh key
key="base64:$(openssl rand -base64 32)"
if grep -qE '^APP_KEY=' "$ENV"; then
  sed -i.bak -E "s|^APP_KEY=.*|APP_KEY=${key}|" "$ENV"
else
  printf "\nAPP_KEY=%s\n" "$key" >> "$ENV"
fi
echo "Set APP_KEY in $ENV"
