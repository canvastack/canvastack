#!/bin/bash

# CanvaStack Development Symlink Script
# 
# This script creates symlinks from package to main app for development.
# No need to publish views every time you make changes!

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== CanvaStack Development Symlink ===${NC}"
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PACKAGE_DIR="$(dirname "$SCRIPT_DIR")"
APP_DIR="$(dirname "$(dirname "$PACKAGE_DIR")")/canvastack"

echo "Package: $PACKAGE_DIR"
echo "App: $APP_DIR"
echo ""

# Check if app directory exists
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}Error: App directory not found: $APP_DIR${NC}"
    exit 1
fi

# Function to create symlink
create_symlink() {
    local source=$1
    local target=$2
    local name=$3
    
    # Create target directory if not exists
    mkdir -p "$(dirname "$target")"
    
    # Remove existing file/symlink
    if [ -e "$target" ] || [ -L "$target" ]; then
        echo -e "${YELLOW}Removing existing: $target${NC}"
        rm -rf "$target"
    fi
    
    # Create symlink
    echo -e "${GREEN}Linking: $name${NC}"
    ln -sf "$source" "$target"
    echo "  Source: $source"
    echo "  Target: $target"
    echo ""
}

# 1. Symlink filter-modal-v2.blade.php
echo -e "${GREEN}[1/3] Symlinking filter-modal-v2.blade.php...${NC}"
create_symlink \
    "$PACKAGE_DIR/resources/views/components/table/filter-modal-v2.blade.php" \
    "$APP_DIR/resources/views/vendor/canvastack/components/table/filter-modal.blade.php" \
    "Filter Modal"

# 2. Symlink Vite build output
echo -e "${GREEN}[2/3] Symlinking Vite build output...${NC}"
create_symlink \
    "$PACKAGE_DIR/public/build" \
    "$APP_DIR/public/vendor/canvastack/build" \
    "Vite Build"

# 3. Symlink ViteAssetLoader (for development)
echo -e "${GREEN}[3/3] Symlinking ViteAssetLoader...${NC}"
# This is already handled by Composer autoload, no need to symlink

echo -e "${GREEN}=== Symlinks Created Successfully! ===${NC}"
echo ""
echo -e "${YELLOW}Important Notes:${NC}"
echo "1. Changes in package will be reflected immediately in app"
echo "2. No need to publish views after every change"
echo "3. Run 'npm run dev' in package directory for HMR"
echo "4. Run 'npm run build' to update production assets"
echo ""
echo -e "${GREEN}Next Steps:${NC}"
echo "1. cd packages/canvastack/canvastack"
echo "2. npm run dev"
echo "3. Open browser and test"
echo ""
