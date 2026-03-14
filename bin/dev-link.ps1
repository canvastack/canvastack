# CanvaStack Development Symlink Script (PowerShell)
# 
# This script creates symlinks from package to main app for development.
# No need to publish views every time you make changes!

Write-Host "=== CanvaStack Development Symlink ===" -ForegroundColor Green
Write-Host ""

# Get script directory
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$PackageDir = Split-Path -Parent $ScriptDir
$AppDir = Join-Path (Split-Path -Parent (Split-Path -Parent $PackageDir)) "canvastack"

Write-Host "Package: $PackageDir"
Write-Host "App: $AppDir"
Write-Host ""

# Check if app directory exists
if (-not (Test-Path $AppDir)) {
    Write-Host "Error: App directory not found: $AppDir" -ForegroundColor Red
    exit 1
}

# Function to create symlink
function Create-Symlink {
    param(
        [string]$Source,
        [string]$Target,
        [string]$Name
    )
    
    # Create target directory if not exists
    $TargetDir = Split-Path -Parent $Target
    if (-not (Test-Path $TargetDir)) {
        New-Item -ItemType Directory -Path $TargetDir -Force | Out-Null
    }
    
    # Remove existing file/symlink
    if (Test-Path $Target) {
        Write-Host "Removing existing: $Target" -ForegroundColor Yellow
        Remove-Item -Path $Target -Recurse -Force
    }
    
    # Create symlink
    Write-Host "Linking: $Name" -ForegroundColor Green
    
    # Check if source is directory or file
    if (Test-Path $Source -PathType Container) {
        # Directory symlink (requires admin on Windows)
        try {
            New-Item -ItemType SymbolicLink -Path $Target -Target $Source -Force | Out-Null
            Write-Host "  Source: $Source"
            Write-Host "  Target: $Target"
            Write-Host ""
        } catch {
            Write-Host "  Error: Failed to create symlink. Run as Administrator!" -ForegroundColor Red
            Write-Host "  Copying instead..." -ForegroundColor Yellow
            Copy-Item -Path $Source -Destination $Target -Recurse -Force
            Write-Host "  Copied: $Source -> $Target"
            Write-Host ""
        }
    } else {
        # File symlink (works without admin)
        New-Item -ItemType SymbolicLink -Path $Target -Target $Source -Force | Out-Null
        Write-Host "  Source: $Source"
        Write-Host "  Target: $Target"
        Write-Host ""
    }
}

# 1. Symlink filter-modal-v2.blade.php
Write-Host "[1/2] Symlinking filter-modal-v2.blade.php..." -ForegroundColor Green
Create-Symlink `
    -Source (Join-Path $PackageDir "resources\views\components\table\filter-modal-v2.blade.php") `
    -Target (Join-Path $AppDir "resources\views\vendor\canvastack\components\table\filter-modal.blade.php") `
    -Name "Filter Modal"

# 2. Symlink Vite build output
Write-Host "[2/2] Symlinking Vite build output..." -ForegroundColor Green
Create-Symlink `
    -Source (Join-Path $PackageDir "public\build") `
    -Target (Join-Path $AppDir "public\vendor\canvastack\build") `
    -Name "Vite Build"

Write-Host "=== Symlinks Created Successfully! ===" -ForegroundColor Green
Write-Host ""
Write-Host "Important Notes:" -ForegroundColor Yellow
Write-Host "1. Changes in package will be reflected immediately in app"
Write-Host "2. No need to publish views after every change"
Write-Host "3. Run 'npm run dev' in package directory for HMR"
Write-Host "4. Run 'npm run build' to update production assets"
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Green
Write-Host "1. cd packages/canvastack/canvastack"
Write-Host "2. npm run dev"
Write-Host "3. Open browser and test"
Write-Host ""
