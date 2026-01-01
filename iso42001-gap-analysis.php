<?php
/**
 * Plugin Name: ISO 42001 Gap Analysis
 * Description: ISO 42001/2023 self-assessment gap analysis with maturity scoring.
 * Version: 7.1.5
 * Author: Your Company
 * License: GPL v2 or later
 * Text Domain: iso42001-gap-analysis
 */

if (!defined('ABSPATH')) {
  exit;
}

// Database version for tracking schema updates
if (!defined('ISO42K_DB_VERSION')) {
  define('ISO42K_DB_VERSION', '1.1');
}

/* =====================================================
   CONSTANTS
===================================================== */

if (!defined('DUO_ISO42K_PATH')) {
  define('DUO_ISO42K_PATH', plugin_dir_path(__FILE__));
}

if (!defined('DUO_ISO42K_URL')) {
  define('DUO_ISO42K_URL', plugin_dir_url(__FILE__));
}

/* =====================================================
   INCLUDES (ORDER MATTERS)
===================================================== */

// Core classes
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-logger.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-leads.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-pdf.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-email.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-ai.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-questions.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-scoring.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-assessment.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-ajax.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-admin.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-shortcode.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-autosave.php';
require_once DUO_ISO42K_PATH . 'includes/class-iso42k-zapier.php';

// Initialize
ISO42K_Ajax::init();
ISO42K_Admin::init();
ISO42K_Shortcode::init();
ISO42K_AutoSave::init();

// Database update mechanism
add_action('plugins_loaded', function () {
  $current_db_version = get_option('iso42k_db_version', '1.0');
  
  if (version_compare($current_db_version, ISO42K_DB_VERSION, '<')) {
    // Update the database
    ISO42K_Leads::create_table(); // This will add any missing columns
    update_option('iso42k_db_version', ISO42K_DB_VERSION);
  }
});






/**
 * Secure PDF Download Handler
 * Handles: /?iso42k_download=pdf&token=xxx
 */
add_action('template_redirect', function() {
    if (!isset($_GET['iso42k_download']) || $_GET['iso42k_download'] !== 'pdf') {
        return;
    }
    
    $token = sanitize_text_field($_GET['token'] ?? '');
    if (empty($token)) {
        wp_die('Invalid download link. Please check your email for the correct link.');
    }
    
    // Get token data
    $data = get_option('iso42k_pdf_token_' . $token);
    if (!$data || !is_array($data)) {
        wp_die('This download link is invalid or has expired. PDF reports are available for 7 days after assessment completion.');
    }
    
    // Check expiration
    if (time() > ($data['expires'] ?? 0)) {
        delete_option('iso42k_pdf_token_' . $token);
        wp_die('This download link has expired. PDF reports are available for 7 days. Please contact us if you need a new copy.');
    }
    
    $file_path = $data['path'] ?? '';
    if (!file_exists($file_path)) {
        ISO42K_Logger::log('PDF download failed - file not found: ' . $file_path);
        wp_die('PDF file not found. Please contact support.');
    }
    
    // Track download
    $lead_id = $data['lead_id'] ?? 0;
    ISO42K_Logger::log('✅ PDF downloaded: Lead ID ' . $lead_id . ' | Email: ' . $data['email']);
    
    // Increment download counter
    $download_count = get_option('iso42k_pdf_downloads_' . $lead_id, 0);
    update_option('iso42k_pdf_downloads_' . $lead_id, $download_count + 1);
    
    // Optional: Update lead record with download timestamp
    if (class_exists('ISO42K_Leads')) {
        global $wpdb;
        $table = $wpdb->prefix . 'iso42k_leads';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET pdf_downloaded_at = %s WHERE id = %d",
            current_time('mysql'),
            $lead_id
        ));
    }
    
    // Determine content type based on file extension
    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($file_extension === 'html' || $file_extension === 'htm') {
        $content_type = 'text/html';
        // For HTML files, we want inline display rather than download
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    } else {
        $content_type = 'application/pdf';
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    }
    header('Content-Length: ' . filesize($file_path));
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    header('Pragma: public');
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    readfile($file_path);
    exit;
}, 1);

// Schedule daily cleanup of expired PDF tokens
add_action('iso42k_daily_cleanup', function() {
    global $wpdb;
    
    $options = $wpdb->get_results(
        "SELECT option_name FROM {$wpdb->options} 
         WHERE option_name LIKE 'iso42k_pdf_token_%'",
        ARRAY_A
    );
    
    $deleted = 0;
    foreach ($options as $row) {
        $data = get_option($row['option_name']);
        if (is_array($data) && time() > ($data['expires'] ?? 0)) {
            delete_option($row['option_name']);
            $deleted++;
        }
    }
    
    ISO42K_Logger::log('Cleanup: Removed ' . $deleted . ' expired PDF tokens');
});

if (!wp_next_scheduled('iso42k_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'iso42k_daily_cleanup');
}

/* =====================================================
   ACTIVATION HOOK
===================================================== */

register_activation_hook(__FILE__, function () {
  ISO42K_Leads::create_table();
  
  // Set default settings
  if (!get_option('iso42k_email_settings')) {
    update_option('iso42k_email_settings', [
      'from_name' => get_bloginfo('name'),
      'from_email' => get_option('admin_email'),
      'admin_notification_enabled' => true,
      'admin_notification_emails' => get_option('admin_email'),
    ]);
  }
  
  // Set default Zapier settings
  if (!get_option('iso42k_zapier_settings')) {
    update_option('iso42k_zapier_settings', [
      'enabled' => false,
      'webhook_url' => '',
      'include_answers' => true,
      'include_ai' => true,
    ]);
  }
});