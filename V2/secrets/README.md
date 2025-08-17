Secrets directory (NOT for production commits)
===========================================

This folder holds per-file Docker secrets consumed by `docker-compose.yml`.

Each secret is mounted into containers at `/run/secrets/<name>` and loaded by the PHP `entrypoint.sh`.

Required files (create each with the secret value, no quotes, single line):

1. app_key.txt                 -> Laravel APP_KEY (omit from .env or leave placeholder). Use `php artisan key:generate --show` locally to create one then paste.
2. google_client_secret.txt    -> Google OAuth client secret
3. microsoft_client_secret.txt -> Microsoft (Entra) OAuth client secret
4. db_password.txt             -> MySQL regular user password (used for MYSQL_PASSWORD_FILE and DB_PASSWORD for Laravel)
5. mysql_root_password.txt     -> MySQL root password
6. ngrok_authtoken.txt         -> Ngrok auth token (allows authenticated tunnels)

Workflow:
1. Copy sample or create blank text files above.
2. Insert secret values (single line, trailing newline OK). Avoid extra spaces.
3. Ensure these files are NOT committed (see .gitignore rule).
4. Bring the stack up: `docker compose up --build` (from `V2/`).

Rotation:
Replace the file content, then `docker compose up -d --force-recreate weaver-php weaver-db`.

Security Notes:
- These are plain-text on the host; secure the host filesystem appropriately.
- For production, use an orchestrator-native secret manager (AWS Secrets Manager, SSM, Vault, etc.) instead of compose file secrets.
