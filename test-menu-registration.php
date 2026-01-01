<?php
/**
 * Test script to validate EcoVadis plugin menu registration
 * 
 * This script simulates WordPress environment to check if the admin menu
 * registration would work correctly.
 */

// Simulate WordPress constants and functions
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('DUO_ISO42K_PATH')) {
    define('DUO_ISO42K_PATH', __DIR__ . '/');
}

if (!defined('DUO_ISO42K_URL')) {
    define('DUO_ISO42K_URL', 'http://localhost/wp-content/plugins/ecovadis-plugin/');
}

// Mock WordPress functions
function add_action($hook, $callback, $priority = 10, $args = 1) {
    echo "✓ Hook registered: $hook\n";
    return true;
}

function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon = '', $position = null) {
    echo "✓ Main menu registered: '$menu_title' (slug: $menu_slug)\n";
    return true;
}

function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback) {
    echo "  ├─ Submenu registered: '$menu_title' (slug: $menu_slug)\n";
    return true;
}

function error_log($message) {
    echo "[LOG] $message\n";
}

echo "===========================================\n";
echo "EcoVadis Plugin Menu Registration Test\n";
echo "===========================================\n\n";

// Load the admin class
require_once __DIR__ . '/includes/class-iso42k-admin.php';

// Check if class exists
if (!class_exists('ISO42K_Admin')) {
    echo "❌ ERROR: ISO42K_Admin class not found!\n";
    exit(1);
}

echo "✓ ISO42K_Admin class loaded successfully\n\n";

// Check if init method exists
if (!method_exists('ISO42K_Admin', 'init')) {
    echo "❌ ERROR: init() method not found in ISO42K_Admin class!\n";
    exit(1);
}

echo "✓ init() method exists\n\n";

// Check if register_menus method exists
if (!method_exists('ISO42K_Admin', 'register_menus')) {
    echo "❌ ERROR: register_menus() method not found in ISO42K_Admin class!\n";
    exit(1);
}

echo "✓ register_menus() method exists\n\n";

echo "Initializing plugin...\n";
echo "-------------------------------------------\n";

// Initialize the admin class
ISO42K_Admin::init();

echo "\n-------------------------------------------\n";
echo "Testing direct menu registration...\n";
echo "-------------------------------------------\n";

// Test direct call to register_menus
ISO42K_Admin::register_menus();

echo "\n===========================================\n";
echo "✅ TEST COMPLETED SUCCESSFULLY\n";
echo "===========================================\n\n";
echo "If all items above are marked with ✓, the\n";
echo "menu registration should work correctly in\n";
echo "WordPress.\n\n";
echo "Expected menu structure:\n";
echo "  Ecovadis (Main Menu)\n";
echo "    ├─ Dashboard\n";
echo "    ├─ Leads\n";
echo "    ├─ Settings\n";
echo "    ├─ API Monitoring\n";
echo "    ├─ Zapier Monitoring\n";
echo "    ├─ Database Diagnostic\n";
echo "    └─ System & Debug\n";
echo "\n";
