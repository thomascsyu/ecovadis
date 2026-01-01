#!/bin/bash
# Quick validation script for EcoVadis plugin menu fix

echo "========================================"
echo "EcoVadis Plugin Menu Fix Validator"
echo "========================================"
echo ""

# Check if the file exists
if [ ! -f "includes/class-iso42k-admin.php" ]; then
    echo "❌ ERROR: includes/class-iso42k-admin.php not found"
    exit 1
fi

echo "✓ Plugin file found"

# Check for the class declaration
if grep -q "^class ISO42K_Admin" includes/class-iso42k-admin.php; then
    echo "✓ ISO42K_Admin class declaration found"
else
    echo "❌ ERROR: ISO42K_Admin class not found"
    exit 1
fi

# Check for init method with proper indentation
if grep -q "^  public static function init()" includes/class-iso42k-admin.php; then
    echo "✓ init() method has correct indentation (2 spaces)"
else
    echo "❌ ERROR: init() method indentation issue"
    exit 1
fi

# Check for register_menus method
if grep -q "public static function register_menus()" includes/class-iso42k-admin.php; then
    echo "✓ register_menus() method found"
else
    echo "❌ ERROR: register_menus() method not found"
    exit 1
fi

# Check for admin_menu hook registration
if grep -q "add_action('admin_menu', \[__CLASS__, 'register_menus'\])" includes/class-iso42k-admin.php; then
    echo "✓ admin_menu hook is registered"
else
    echo "❌ ERROR: admin_menu hook not registered"
    exit 1
fi

# Check brace balance
open_braces=$(grep -o '{' includes/class-iso42k-admin.php | wc -l)
close_braces=$(grep -o '}' includes/class-iso42k-admin.php | wc -l)

if [ "$open_braces" -eq "$close_braces" ]; then
    echo "✓ Braces are balanced ($open_braces opening, $close_braces closing)"
else
    echo "❌ ERROR: Braces not balanced ($open_braces opening, $close_braces closing)"
    exit 1
fi

# Check if main plugin file initializes the admin class
if grep -q "ISO42K_Admin::init();" iso42001-gap-analysis.php; then
    echo "✓ Admin class is initialized in main plugin file"
else
    echo "❌ ERROR: Admin class not initialized"
    exit 1
fi

# Check for required class files
echo ""
echo "Checking required class files:"
required_files=(
    "includes/class-iso42k-leads.php"
    "includes/class-iso42k-admin-leads.php"
    "includes/class-iso42k-logger.php"
    "includes/class-iso42k-ai.php"
    "includes/class-iso42k-zapier.php"
    "includes/class-iso42k-email.php"
)

all_files_present=true
for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
    else
        echo "  ❌ $file (missing)"
        all_files_present=false
    fi
done

echo ""
if [ "$all_files_present" = true ]; then
    echo "========================================"
    echo "✅ ALL CHECKS PASSED!"
    echo "========================================"
    echo ""
    echo "The plugin menu should now work correctly."
    echo ""
    echo "Next steps:"
    echo "1. Upload the plugin to WordPress"
    echo "2. Deactivate and reactivate the plugin"
    echo "3. Check for the 'Ecovadis' menu in admin"
    echo ""
    exit 0
else
    echo "========================================"
    echo "⚠️  SOME FILES ARE MISSING"
    echo "========================================"
    exit 1
fi
