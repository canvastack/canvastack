#!/bin/bash

# CanvaStack Database Setup Script
# This script creates development and testing databases

set -e

echo "=========================================="
echo "CanvaStack Database Setup"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default values
DB_DEV="canvastack_dev"
DB_TEST="canvastack_test"
DB_USER="root"
DB_PASS=""

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --user)
            DB_USER="$2"
            shift 2
            ;;
        --password)
            DB_PASS="$2"
            shift 2
            ;;
        --dev-db)
            DB_DEV="$2"
            shift 2
            ;;
        --test-db)
            DB_TEST="$2"
            shift 2
            ;;
        --help)
            echo "Usage: ./setup-database.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --user USER          MySQL username (default: root)"
            echo "  --password PASS      MySQL password (default: empty)"
            echo "  --dev-db NAME        Development database name (default: canvastack_dev)"
            echo "  --test-db NAME       Testing database name (default: canvastack_test)"
            echo "  --help               Show this help message"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Function to execute MySQL command
mysql_exec() {
    if [ -z "$DB_PASS" ]; then
        mysql -u "$DB_USER" -e "$1" 2>/dev/null
    else
        mysql -u "$DB_USER" -p"$DB_PASS" -e "$1" 2>/dev/null
    fi
}

# Check if MySQL is accessible
echo -n "Checking MySQL connection... "
if mysql_exec "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "Cannot connect to MySQL. Please check your credentials."
    exit 1
fi

# Create development database
echo -n "Creating development database ($DB_DEV)... "
if mysql_exec "CREATE DATABASE IF NOT EXISTS $DB_DEV CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    exit 1
fi

# Create testing database
echo -n "Creating testing database ($DB_TEST)... "
if mysql_exec "CREATE DATABASE IF NOT EXISTS $DB_TEST CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    exit 1
fi

# Verify databases
echo -n "Verifying databases... "
DEV_EXISTS=$(mysql_exec "SHOW DATABASES LIKE '$DB_DEV';" | grep -c "$DB_DEV" || true)
TEST_EXISTS=$(mysql_exec "SHOW DATABASES LIKE '$DB_TEST';" | grep -c "$DB_TEST" || true)

if [ "$DEV_EXISTS" -eq 1 ] && [ "$TEST_EXISTS" -eq 1 ]; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    exit 1
fi

echo ""
echo "=========================================="
echo -e "${GREEN}Database setup completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Development database: $DB_DEV"
echo "Testing database: $DB_TEST"
echo ""
echo "Next steps:"
echo "1. Update your .env file with database credentials"
echo "2. Run: php artisan migrate"
echo "3. Run: php artisan db:seed --class=CanvastackDevelopmentSeeder"
echo ""
