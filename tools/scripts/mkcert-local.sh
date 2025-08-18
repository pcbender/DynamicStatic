#!/usr/bin/env bash
set -euo pipefail
CERT_DIR="infra/certs"
mkdir -p "$CERT_DIR"

CRT="$CERT_DIR/localhost.crt"
KEY="$CERT_DIR/localhost.key"

if [[ -f "$CRT" && -f "$KEY" ]]; then
  echo "Certs already exist at $CERT_DIR"
  exit 0
fi

openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout "$KEY" \
  -out "$CRT" \
  -subj "/C=US/ST=Local/L=Local/O=DynamicStatic/OU=Dev/CN=localhost"

echo "Created $CRT and $KEY"

# Ensure Laravel APP_KEY exists for dev
if [ -x "./tools/scripts/ensure-app-key.sh" ]; then
  ./tools/scripts/ensure-app-key.sh
else
  echo "Warning: tools/scripts/ensure-app-key.sh not found; skipping APP_KEY guard."
fi
