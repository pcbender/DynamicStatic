# Weaver PHP Deployment Script (PowerShell)
# Usage: .\deploy-weaver.ps1 -Server "your-server.com" -User "username" -RemotePath "/var/www/html/weaver"

param(
    [string]$Server = "pdx1-shared-a4-08.dreamhost.com",
    [string]$User = "pcbender", 
    [string]$RemotePath = "/home/pcbender/webbness.net"
)

Write-Host "🚀 Deploying Weaver PHP to $User@$Server`:$RemotePath" -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Green

# Check if we're in the right directory
if (-not (Test-Path "Weaver\php")) {
    Write-Host "❌ Error: Weaver\php directory not found!" -ForegroundColor Red
    Write-Host "Make sure you're running this from the DynamicStatic root directory." -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Install production dependencies
Write-Host "📦 Installing production dependencies..." -ForegroundColor Cyan
Push-Location "Weaver\php"
try {
    & composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) {
        throw "Composer install failed"
    }
} catch {
    Write-Host "❌ Error: Composer install failed!" -ForegroundColor Red
    Pop-Location
    Read-Host "Press Enter to exit"
    exit 1
}
Pop-Location

# Create temporary exclude file
$excludeFile = "deploy-excludes.txt"
@(
    ".git/",
    ".vscode/",
    "node_modules/",
    ".env",
    ".env.local",
    "*.log",
    "temp/",
    "cache/",
    "composer.lock",
    "phpunit.xml",
    "tests/"
) | Out-File -FilePath $excludeFile -Encoding UTF8

Write-Host "📤 Syncing files to server..." -ForegroundColor Cyan

# Check for rsync availability
$rsyncPath = $null
$paths = @(
    "rsync",
    "wsl",
    "C:\Program Files\Git\usr\bin\rsync.exe"
)

foreach ($path in $paths) {
    try {
        if ($path -eq "wsl") {
            & wsl which rsync 2>$null
            if ($LASTEXITCODE -eq 0) {
                $rsyncPath = "wsl rsync"
                break
            }
        } else {
            & where.exe $path 2>$null
            if ($LASTEXITCODE -eq 0) {
                $rsyncPath = $path
                break
            }
        }
    } catch {
        continue
    }
}

if (-not $rsyncPath) {
    Write-Host "❌ rsync not found! Available options:" -ForegroundColor Red
    Write-Host "1. Install WSL: wsl --install" -ForegroundColor Yellow
    Write-Host "2. Install Git for Windows (includes rsync)" -ForegroundColor Yellow
    Write-Host "3. Use VS Code SFTP extension" -ForegroundColor Yellow
    Write-Host "4. Manual SCP: scp -r Weaver\php\* ${User}@${Server}:${RemotePath}/" -ForegroundColor Yellow
    Remove-Item $excludeFile -ErrorAction SilentlyContinue
    Read-Host "Press Enter to exit"
    exit 1
}

# Execute rsync
$rsyncArgs = @(
    "-avz",
    "--progress", 
    "--delete",
    "--exclude-from=$excludeFile",
    "Weaver/php/",
    "${User}@${Server}:${RemotePath}/"
)

Write-Host "Running: $rsyncPath $rsyncArgs" -ForegroundColor Gray

if ($rsyncPath -eq "wsl rsync") {
    & wsl rsync @rsyncArgs
} else {
    & $rsyncPath @rsyncArgs
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Error: File sync failed!" -ForegroundColor Red
    Write-Host "Troubleshooting:" -ForegroundColor Yellow
    Write-Host "1. Test SSH: ssh ${User}@${Server}" -ForegroundColor Yellow
    Write-Host "2. Verify remote path exists: $RemotePath" -ForegroundColor Yellow
    Remove-Item $excludeFile -ErrorAction SilentlyContinue
    Read-Host "Press Enter to exit"
    exit 1
}

# Copy environment file
Write-Host "🔧 Copying environment configuration..." -ForegroundColor Cyan
if (Test-Path "Weaver\.env.production") {
    try {
        & scp "Weaver\.env.production" "${User}@${Server}:${RemotePath}/.env"
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Environment file copied successfully" -ForegroundColor Green
        } else {
            Write-Host "⚠️  Warning: Could not copy .env.production file" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "⚠️  Warning: Could not copy .env.production file" -ForegroundColor Yellow
    }
} else {
    Write-Host "⚠️  Warning: .env.production not found" -ForegroundColor Yellow
}

# Set permissions
Write-Host "🔐 Setting proper permissions..." -ForegroundColor Cyan
try {
    & ssh "${User}@${Server}" "cd '$RemotePath' && find . -type f -exec chmod 644 {} \; && find . -type d -exec chmod 755 {} \; && chmod 600 .env 2>/dev/null || true"
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Permissions set successfully" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Warning: Could not set all permissions" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Warning: Could not set permissions" -ForegroundColor Yellow
}

# Cleanup
Remove-Item $excludeFile -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "================================================================" -ForegroundColor Green
Write-Host "✅ Deployment completed successfully!" -ForegroundColor Green  
Write-Host "================================================================" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Post-deployment checklist:" -ForegroundColor Cyan
Write-Host "   1. Test OAuth: https://$Server/oauth/authorize" -ForegroundColor White
Write-Host "   2. Test API: https://$Server/jobs" -ForegroundColor White
Write-Host "   3. Check server logs for errors" -ForegroundColor White
Write-Host "   4. Verify .env configuration" -ForegroundColor White
Write-Host ""
Write-Host "🌐 Your Weaver API is now live at: https://$Server" -ForegroundColor Green
Write-Host ""
Read-Host "Press Enter to exit"
