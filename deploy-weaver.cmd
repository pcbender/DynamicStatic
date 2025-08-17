@echo off
setlocal enabledelayedexpansion

REM Weaver PHP Deployment Script for Windows
REM Usage: deploy-weaver.cmd [server] [user] [path] [url]

set "SERVER=%~1"
set "USER=%~2" 
set "REMOTE_PATH=%~3"
set "URL=%~4"

REM Default values if not provided
if "%SERVER%"=="" set "SERVER=pdx1-shared-a4-08.dreamhost.com"
if "%USER%"=="" set "USER=pcbender"
if "%REMOTE_PATH%"=="" set "REMOTE_PATH=/home/pcbender/webbness.net"
if "%URL%"=="" set "URL=https://webbness.net"

echo.
echo ================================================================
echo  Weaver PHP Deployment to %USER%@%SERVER%:%REMOTE_PATH%
echo ================================================================
echo.

REM Check if we're in the right directory
if not exist "Weaver\php" (
    echo Error: Weaver\php directory not found!
    echo Make sure you're running this from the DynamicStatic root directory.
    pause
    exit /b 1
)

echo Installing production dependencies...
cd "Weaver\php"
composer install --no-dev --optimize-autoloader --no-interaction
if errorlevel 1 (
    echo Error: Composer install failed!
    pause
    exit /b 1
)
cd ..\..

echo.
echo Syncing files to server...
echo Command: rsync -avz --progress --delete --exclude-from=deploy-excludes.txt "apps/weaver-laravel/" "%USER%@%SERVER%:%REMOTE_PATH%/"
echo.

REM Create exclude file for rsync
echo .git/ > deploy-excludes.txt
echo .vscode/ >> deploy-excludes.txt
echo node_modules/ >> deploy-excludes.txt
echo vendor/ >> deploy-excludes.txt
echo .env >> deploy-excludes.txt
echo .env.local >> deploy-excludes.txt
echo *.log >> deploy-excludes.txt
echo temp/ >> deploy-excludes.txt
echo cache/ >> deploy-excludes.txt
echo composer.lock >> deploy-excludes.txt
echo phpunit.xml >> deploy-excludes.txt
echo tests/ >> deploy-excludes.txt

REM Check if rsync is available (WSL, Git Bash, or native)
where rsync >nul 2>&1
if errorlevel 1 (
    echo rsync not found! You have these options:
    echo.
    echo Option 1: Use Windows Subsystem for Linux ^(WSL^)
    echo   wsl rsync -avz --progress --delete --exclude-from=deploy-excludes.txt "apps/weaver-laravel/" "%USER%@%SERVER%:%REMOTE_PATH%/"
    echo.
    echo Option 2: Use Git Bash
    echo   "C:\Program Files\Git\usr\bin\rsync.exe" -avz --progress --delete --exclude-from=deploy-excludes.txt "apps/weaver-laravel/" "%USER%@%SERVER%:%REMOTE_PATH%/"
    echo.
    echo Option 3: Manual SCP upload
    echo   scp -r Weaver\php\* %USER%@%SERVER%:%REMOTE_PATH%/
    echo.
    echo Option 4: Use VS Code SFTP extension
    echo   Right-click on Weaver\php folder and select "Upload Folder"
    echo.
    pause
    goto :cleanup
)

REM Run rsync
rsync -avz --progress --delete --exclude-from=deploy-excludes.txt "apps/weaver-laravel/" "%USER%@%SERVER%:%REMOTE_PATH%/"
if errorlevel 1 (
    echo Error: File sync failed!
    echo.
    echo Troubleshooting:
    echo 1. Check SSH key authentication: ssh %USER%@%SERVER%
    echo 2. Verify server path exists: %REMOTE_PATH%
    echo 3. Check firewall/network connectivity
    pause
    goto :cleanup
)

echo.
echo Copying environment configuration...
if exist "Weaver\.env.production" (
    scp "Weaver\.env.production" "%USER%@%SERVER%:%REMOTE_PATH%/.env"
    if errorlevel 1 (
        echo Warning: Could not copy .env.production file
    ) else (
        echo Environment file copied successfully
    )
) else (
    echo Warning: .env.production not found. Create one or manually configure .env on server.
)

echo.
echo Setting proper permissions...
ssh "%USER%@%SERVER%" "cd %REMOTE_PATH% && find . -type f -exec chmod 644 {} \; && find . -type d -exec chmod 755 {} \; && chmod 600 .env 2>/dev/null || true"
if errorlevel 1 (
    echo Warning: Could not set permissions. You may need to do this manually.
)

echo.
echo ================================================================
echo Deployment completed successfully!
echo ================================================================
echo.
echo Post-deployment checklist:
echo   1. Test OAuth: %URL%/oauth/authorize
echo   2. Test API: %URL%/jobs
echo   3. Check server logs for errors
echo   4. Verify .env configuration
echo.
echo Your Weaver API is now live at: %URL%
echo.

:cleanup
if exist deploy-excludes.txt del deploy-excludes.txt
pause
