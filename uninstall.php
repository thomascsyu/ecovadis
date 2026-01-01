<?php
/**
 * ISO 42001 Gap Analysis - Uninstall File
 * 
 * Cleanup operations performed when uninstalling the plugin (triggered only when deleting plugin from WordPress admin)
 * Following WordPress official guidelines: https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 * 
 * Security notes:
 * - Executes only when WP_UNINSTALL_PLUGIN constant is defined (prevents direct access)
 * - Only cleans data created by this plugin, no impact on WP core/other plugins
 * - Requires network admin privileges in multisite environments
 */

// Security protection: Prevent direct access to this file
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Terminate execution on direct access
}

// -------------------------- Configuration (adjust as needed) --------------------------
$delete_drafts_table = true;  // Whether to delete assessment draft table (true=delete, false=keep)
$delete_options = true;       // Whether to delete plugin-related options (true=delete, false=keep)
// -----------------------------------------------------------------------------


/**
 * Clean up plugin data in single site environment
 */
function iso42k_uninstall_single_site() {
    global $wpdb;
    global $delete_drafts_table, $delete_options;

    // 1. Clean up plugin-related data tables (assessment draft table)
    if ($delete_drafts_table) {
        $table_name = $wpdb->prefix . 'iso42k_drafts';
        $wpdb->query("DROP TABLE IF EXISTS $table_name"); // Safely drop table (no action if doesn't exist)
    }

    // 2. Clean up plugin-related WP options (version, configuration, etc.)
    if ($delete_options) {
        $options = [
            'iso42k_plugin_version',
            'iso42k_last_updated',
            'iso42k_default_settings',
            'iso42k_assessment_count'
        ];
        
        // Bulk delete options
        foreach ($options as $option) {
            delete_option($option);
        }
    }

    // 3. Clean up temporary cache (optional)
    wp_cache_flush();
}


/**
 * Clean up plugin data in multisite environment
 */
function iso42k_uninstall_multisite() {
    global $wpdb;
    global $delete_drafts_table, $delete_options;

    // Get all site IDs
    $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    
    // Iterate through each site to clean data
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id); // Switch to target site

        // 1. Clean up draft table for current site
        if ($delete_drafts_table) {
            $table_name = $wpdb->prefix . 'iso42k_drafts';
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }

        // 2. Clean up plugin options for current site
        if ($delete_options) {
            $options = [
                'iso42k_plugin_version',
                'iso42k_last_updated',
                'iso42k_default_settings',
                'iso42k_assessment_count'
            ];
            
            foreach ($options as $option) {
                delete_option($option);
            }
        }

        restore_current_blog(); // Restore current site
    }

    // Clean up network-level options (if any)
    if ($delete_options) {
        delete_site_option('iso42k_network_settings');
    }

    // Clean up cache
    wp_cache_flush();
}

// Execute appropriate cleanup logic based on site type
if (is_multisite()) {
    iso42k_uninstall_multisite();
} else {
    iso42k_uninstall_single_site();
}

/**
 * Final cleanup: Remove all plugin-related rewrite rules (optional)
 */
flush_rewrite_rules();

// Uninstall completion notice (visible only in logs, no frontend output)
error_log('ISO 42001 Gap Analysis Plugin: Uninstall completed successfully');