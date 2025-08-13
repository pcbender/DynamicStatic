#!/bin/bash

# Weaver PHP Deployment Script
# Usage: ./deploy-weaver.sh [server] [user] [path]

set -e  # Exit on any error

# Configuration - Update these for your server
SERVER=${1:-"your-server.com"}
USER=${2:-"your-username"}
REMOTE_PATH=${3:-"/var/www/html/weaver"}

# Local paths
LOCAL_PHP_PATH="./Weaver/php"
LOCAL_ENV_PROD="./Weaver/.env.production"

echo "🚀 Deploying Weaver PHP to $USER@$SERVER:$REMOTE_PATH"
echo "========================================================"

# Check if local directory exists
if [ ! -d "$LOCAL_PHP_PATH" ]; then
    echo "❌ Error: $LOCAL_PHP_PATH directory not found!"
    exit 1
fi

# Install/update composer dependencies for production
echo "📦 Installing production dependencies..."
cd "$LOCAL_PHP_PATH"
composer install --no-dev --optimize-autoloader --no-interaction
cd - > /dev/null

echo "📤 Syncing files to server..."

# Rsync with optimized settings
rsync -avz \
    --progress \
    --delete \
    --exclude='.git/' \
    --exclude='.vscode/' \
    --exclude='node_modules/' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='*.log' \
    --exclude='temp/' \
    --exclude='cache/' \
    --exclude='composer.lock' \
    --exclude='phpunit.xml' \
    --exclude='tests/' \
    "$LOCAL_PHP_PATH/" \
    "$USER@$SERVER:$REMOTE_PATH/"

echo "🔧 Setting up environment file..."

# Copy production environment file if it exists
if [ -f "$LOCAL_ENV_PROD" ]; then
    echo "📋 Copying production environment configuration..."
    scp "$LOCAL_ENV_PROD" "$USER@$SERVER:$REMOTE_PATH/.env"
else
    echo "⚠️  Warning: .env.production not found. You'll need to configure .env manually on the server."
fi

echo "🔐 Setting proper permissions..."

# Set proper permissions on the server
ssh "$USER@$SERVER" "
    cd $REMOTE_PATH && \
    find . -type f -exec chmod 644 {} \; && \
    find . -type d -exec chmod 755 {} \; && \
    chmod 600 .env 2>/dev/null || true
"

echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Post-deployment checklist:"
echo "   1. Verify .env configuration on server"
echo "   2. Test OAuth endpoints: $SERVER/oauth/authorize"
echo "   3. Check API endpoints: $SERVER/jobs"
echo "   4. Review server logs for any errors"
echo ""
echo "🌐 Your Weaver API should now be live at: https://$SERVER"
