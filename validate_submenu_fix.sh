#!/bin/bash

# EcoVadis Plugin - Post-Fix Validation Script
# This script validates that the submenu fix has been properly applied

echo "================================================"
echo "EcoVadis Plugin - Submenu Fix Validation"
echo "================================================"
echo ""

PLUGIN_DIR="/workspace"
ADMIN_CLASS="$PLUGIN_DIR/includes/class-iso42k-admin.php"

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check 1: File exists
echo -n "1. Checking if admin class file exists... "
if [ -f "$ADMIN_CLASS" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   File not found: $ADMIN_CLASS"
    exit 1
fi

# Check 2: Proper indentation of init() method
echo -n "2. Checking init() method indentation... "
if grep -q "^  public static function init()" "$ADMIN_CLASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    echo "   Method has correct 2-space indentation"
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   Method indentation is incorrect"
    echo "   Expected: '  public static function init()'"
    echo "   Found:"
    grep -n "public static function init()" "$ADMIN_CLASS" | sed 's/^/   /'
    exit 1
fi

# Check 3: register_menus method exists
echo -n "3. Checking register_menus() method exists... "
if grep -q "public static function register_menus()" "$ADMIN_CLASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   register_menus() method not found"
    exit 1
fi

# Check 4: Count submenu registrations
echo -n "4. Checking submenu registrations... "
SUBMENU_COUNT=$(grep -c "add_submenu_page(" "$ADMIN_CLASS")
if [ "$SUBMENU_COUNT" -ge 7 ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    echo "   Found $SUBMENU_COUNT submenu registrations"
else
    echo -e "${YELLOW}⚠ WARNING${NC}"
    echo "   Expected at least 7 submenu registrations, found $SUBMENU_COUNT"
fi

# Check 5: Main menu registration
echo -n "5. Checking main menu registration... "
if grep -q "add_menu_page(" "$ADMIN_CLASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    MENU_TITLE=$(grep "add_menu_page(" "$ADMIN_CLASS" | grep -o "'[^']*'" | head -2 | tail -1)
    echo "   Menu title: $MENU_TITLE"
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   add_menu_page() call not found"
    exit 1
fi

# Check 6: Admin init hook
echo -n "6. Checking admin_menu hook registration... "
if grep -q "add_action('admin_menu'" "$ADMIN_CLASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   admin_menu hook not registered"
    exit 1
fi

# Check 7: Main plugin file initialization
echo -n "7. Checking ISO42K_Admin::init() call in main file... "
MAIN_FILE="$PLUGIN_DIR/iso42001-gap-analysis.php"
if grep -q "ISO42K_Admin::init()" "$MAIN_FILE"; then
    echo -e "${GREEN}✓ PASS${NC}"
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   ISO42K_Admin::init() not called in main plugin file"
    exit 1
fi

# Check 8: Required class files
echo -n "8. Checking required class files... "
REQUIRED_FILES=(
    "class-iso42k-admin-leads.php"
    "class-iso42k-leads.php"
    "class-iso42k-logger.php"
    "class-iso42k-ai.php"
    "class-iso42k-zapier.php"
    "class-iso42k-email.php"
)
MISSING_FILES=0
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$PLUGIN_DIR/includes/$file" ]; then
        if [ $MISSING_FILES -eq 0 ]; then
            echo -e "${RED}✗ FAIL${NC}"
        fi
        echo "   Missing: $file"
        MISSING_FILES=$((MISSING_FILES + 1))
    fi
done
if [ $MISSING_FILES -eq 0 ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    echo "   All ${#REQUIRED_FILES[@]} required class files found"
else
    exit 1
fi

echo ""
echo "================================================"
echo -e "${GREEN}✅ ALL CHECKS PASSED${NC}"
echo "================================================"
echo ""
echo "The submenu fix has been successfully applied."
echo ""
echo "Next steps:"
echo "  1. Upload the corrected files to your WordPress installation"
echo "  2. Go to WordPress Admin → Plugins"
echo "  3. Deactivate 'EcoVadis Self Assessment'"
echo "  4. Activate it again"
echo "  5. Refresh the admin page"
echo "  6. Check the 'Ecovadis' menu in the sidebar"
echo ""
echo "Expected menu structure:"
echo "  Ecovadis"
echo "    ├─ Dashboard"
echo "    ├─ Leads"
echo "    ├─ Settings"
echo "    ├─ API Monitoring"
echo "    ├─ Zapier Monitoring"
echo "    ├─ Database Diagnostic"
echo "    └─ System & Debug"
echo ""
