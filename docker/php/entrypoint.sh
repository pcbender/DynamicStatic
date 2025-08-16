#!/bin/sh
set -e

echo "[entrypoint] Starting weaver-php container"
cd /var/www/html || { echo "Project directory /var/www/html not found"; exit 1; }

# Optional flags (default true)
AUTO_COMPOSER_INSTALL=${AUTO_COMPOSER_INSTALL:-true}
AUTO_MIGRATE=${AUTO_MIGRATE:-true}
DB_WAIT_RETRIES=${DB_WAIT_RETRIES:-20}
DB_WAIT_DELAY=${DB_WAIT_DELAY:-2}

if [ "$AUTO_COMPOSER_INSTALL" = "true" ] && [ -f composer.json ]; then
  if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Running composer install..."
    composer install --no-interaction --prefer-dist --no-progress || echo "[entrypoint] Composer install encountered issues"
  else
    echo "[entrypoint] vendor directory present, skipping composer install"
  fi
fi

# Generate APP_KEY if missing (happens after composer because artisan needed)
if [ -f artisan ]; then
  if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force || echo "[entrypoint] APP_KEY generation failed"
  fi
fi

if [ "$AUTO_MIGRATE" = "true" ] && [ -f artisan ]; then
  if [ "$DB_CONNECTION" = "mysql" ]; then
    DB_HOST=${DB_HOST:-weaver-db}
    DB_PORT=${DB_PORT:-3306}
    echo "[entrypoint] Waiting for MySQL at $DB_HOST:$DB_PORT ..."
    i=1
    while [ $i -le $DB_WAIT_RETRIES ]; do
      if php -r 'try{$dsn="mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"); new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"));}catch(Exception $e){exit(1);}'; then
        echo "[entrypoint] MySQL is reachable."
        break
      fi
      echo "[entrypoint] MySQL not ready yet (attempt $i/$DB_WAIT_RETRIES)"
      i=$((i+1))
      sleep $DB_WAIT_DELAY
    done
  fi
  echo "[entrypoint] Running database migrations (idempotent)..."
  php artisan migrate --force --no-interaction || echo "[entrypoint] Migrations failed (may already be up to date)"
  echo "[entrypoint] Optimizing caches..."
  php artisan config:cache || true
  php artisan route:cache || true
fi

echo "[entrypoint] Launching php-fpm"
exec php-fpm -F
