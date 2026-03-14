@echo off
REM CanvaStack Development Symlink Script (Batch)
REM 
REM This script creates symlinks from package to main app for development.
REM No need to publish views every time you make changes!

echo === CanvaStack Development Symlink ===
echo.

REM Get script directory
set SCRIPT_DIR=%~dp0
set PACKAGE_DIR=%SCRIPT_DIR%..
set APP_DIR=%PACKAGE_DIR%\..\..\canvastack

echo Package: %PACKAGE_DIR%
echo App: %APP_DIR%
echo.

REM Check if app directory exists
if not exist "%APP_DIR%" (
    echo Error: App directory not found: %APP_DIR%
    exit /b 1
)

REM 1. Symlink filter-modal-v2.blade.php
echo [1/2] Symlinking filter-modal-v2.blade.php...

set SOURCE=%PACKAGE_DIR%\resources\views\components\table\filter-modal-v2.blade.php
set TARGET=%APP_DIR%\resources\views\vendor\canvastack\components\table\filter-modal.blade.php

REM Create target directory
if not exist "%APP_DIR%\resources\views\vendor\canvastack\components\table" (
    mkdir "%APP_DIR%\resources\views\vendor\canvastack\components\table"
)

REM Remove existing file
if exist "%TARGET%" (
    echo Removing existing: %TARGET%
    del /f /q "%TARGET%"
)

REM Create symlink (requires admin) or copy
mklink "%TARGET%" "%SOURCE%" >nul 2>&1
if errorlevel 1 (
    echo Warning: Failed to create symlink. Copying instead...
    copy /y "%SOURCE%" "%TARGET%" >nul
    echo Copied: %SOURCE% -^> %TARGET%
) else (
    echo Linked: Filter Modal
    echo   Source: %SOURCE%
    echo   Target: %TARGET%
)
echo.

REM 2. Symlink Vite build output
echo [2/2] Symlinking Vite build output...

set SOURCE=%PACKAGE_DIR%\public\build
set TARGET=%APP_DIR%\public\vendor\canvastack\build

REM Create target directory
if not exist "%APP_DIR%\public\vendor\canvastack" (
    mkdir "%APP_DIR%\public\vendor\canvastack"
)

REM Remove existing directory
if exist "%TARGET%" (
    echo Removing existing: %TARGET%
    rmdir /s /q "%TARGET%"
)

REM Create symlink (requires admin) or copy
mklink /d "%TARGET%" "%SOURCE%" >nul 2>&1
if errorlevel 1 (
    echo Warning: Failed to create symlink. Copying instead...
    xcopy /e /i /y "%SOURCE%" "%TARGET%" >nul
    echo Copied: %SOURCE% -^> %TARGET%
) else (
    echo Linked: Vite Build
    echo   Source: %SOURCE%
    echo   Target: %TARGET%
)
echo.

echo === Symlinks Created Successfully! ===
echo.
echo Important Notes:
echo 1. Changes in package will be reflected immediately in app
echo 2. No need to publish views after every change
echo 3. Run 'npm run dev' in package directory for HMR
echo 4. Run 'npm run build' to update production assets
echo.
echo Next Steps:
echo 1. cd packages\canvastack\canvastack
echo 2. npm run dev
echo 3. Open browser and test
echo.

pause
