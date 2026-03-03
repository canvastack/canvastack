@echo off
REM CanvaStack Database Setup Script for Windows
REM This script creates development and testing databases

setlocal enabledelayedexpansion

echo ==========================================
echo CanvaStack Database Setup
echo ==========================================
echo.

REM Default values
set DB_DEV=canvastack_dev
set DB_TEST=canvastack_test
set DB_USER=root
set DB_PASS=

REM Parse command line arguments
:parse_args
if "%~1"=="" goto end_parse
if "%~1"=="--user" (
    set DB_USER=%~2
    shift
    shift
    goto parse_args
)
if "%~1"=="--password" (
    set DB_PASS=%~2
    shift
    shift
    goto parse_args
)
if "%~1"=="--dev-db" (
    set DB_DEV=%~2
    shift
    shift
    goto parse_args
)
if "%~1"=="--test-db" (
    set DB_TEST=%~2
    shift
    shift
    goto parse_args
)
if "%~1"=="--help" (
    echo Usage: setup-database.bat [OPTIONS]
    echo.
    echo Options:
    echo   --user USER          MySQL username (default: root^)
    echo   --password PASS      MySQL password (default: empty^)
    echo   --dev-db NAME        Development database name (default: canvastack_dev^)
    echo   --test-db NAME       Testing database name (default: canvastack_test^)
    echo   --help               Show this help message
    exit /b 0
)
shift
goto parse_args
:end_parse

REM Check if MySQL is accessible
echo Checking MySQL connection...
if "%DB_PASS%"=="" (
    mysql -u %DB_USER% -e "SELECT 1;" >nul 2>&1
) else (
    mysql -u %DB_USER% -p%DB_PASS% -e "SELECT 1;" >nul 2>&1
)

if errorlevel 1 (
    echo [FAILED] Cannot connect to MySQL. Please check your credentials.
    exit /b 1
)
echo [OK]

REM Create development database
echo Creating development database (%DB_DEV%^)...
if "%DB_PASS%"=="" (
    mysql -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS %DB_DEV% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
) else (
    mysql -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_DEV% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
)

if errorlevel 1 (
    echo [FAILED]
    exit /b 1
)
echo [OK]

REM Create testing database
echo Creating testing database (%DB_TEST%^)...
if "%DB_PASS%"=="" (
    mysql -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS %DB_TEST% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
) else (
    mysql -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_TEST% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
)

if errorlevel 1 (
    echo [FAILED]
    exit /b 1
)
echo [OK]

REM Verify databases
echo Verifying databases...
if "%DB_PASS%"=="" (
    mysql -u %DB_USER% -e "SHOW DATABASES LIKE '%DB_DEV%';" | find "%DB_DEV%" >nul
) else (
    mysql -u %DB_USER% -p%DB_PASS% -e "SHOW DATABASES LIKE '%DB_DEV%';" | find "%DB_DEV%" >nul
)

if errorlevel 1 (
    echo [FAILED]
    exit /b 1
)
echo [OK]

echo.
echo ==========================================
echo Database setup completed successfully!
echo ==========================================
echo.
echo Development database: %DB_DEV%
echo Testing database: %DB_TEST%
echo.
echo Next steps:
echo 1. Update your .env file with database credentials
echo 2. Run: php artisan migrate
echo 3. Run: php artisan db:seed --class=CanvastackDevelopmentSeeder
echo.

endlocal
