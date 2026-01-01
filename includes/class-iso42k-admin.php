<?php
if (!defined('ABSPATH')) exit;

/**
 * ISO42K_Admin
 * Admin panel, settings, and monitoring for ISO 42001 Gap Analysis
 * 
 * @version 7.3.0
 */
class ISO42K_Admin {

 public static function init() {
    // Verify required constants are defined
    if (!defined('DUO_ISO42K_PATH') || !defined('DUO_ISO42K_URL')) {
        error_log('ISO42K: Required constants DUO_ISO42K_PATH or DUO_ISO42K_URL are not defined');
        return;
    }

    // Include required classes with error checking
    $required_files = [
        'includes/class-iso42k-leads.php',
        'includes/class-iso42k-admin-leads.php',
        'includes/class-iso42k-logger.php',
        'includes/class-iso42k-ai.php',
        'includes/class-iso42k-zapier.php',
        'includes/class-iso42k-email.php'
    ];

    foreach ($required_files as $file) {
        $file_path = DUO_ISO42K_PATH . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log('ISO42K: Required file not found: ' . $file_path);
        }
    }

    // Register WordPress hooks
    add_action('admin_menu', [__CLASS__, 'register_menus']);
    add_action('admin_init', [__CLASS__, 'register_settings']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    add_action('wp_dashboard_setup', [__CLASS__, 'maybe_register_dashboard_widget']);
    
    // Add admin post handlers
    add_action('admin_post_iso42k_reset_api_metrics', [__CLASS__, 'reset_api_metrics']);
    add_action('admin_post_iso42k_retry_ai', [__CLASS__, 'handle_retry_ai']);
    add_action('admin_post_iso42k_create_table', [__CLASS__, 'handle_create_table']);
    
    // Add AJAX handlers - Point to correct classes
    add_action('wp_ajax_iso42k_test_admin_email', [__CLASS__, 'handle_test_admin_email']);
    add_action('wp_ajax_iso42k_test_zapier', [__CLASS__, 'handle_test_zapier']);
    add_action('wp_ajax_iso42k_test_deepseek', [__CLASS__, 'handle_test_deepseek']);
    add_action('wp_ajax_iso42k_test_qwen', [__CLASS__, 'handle_test_qwen']);
    add_action('wp_ajax_iso42k_test_grok', [__CLASS__, 'handle_test_grok']);
    add_action('wp_ajax_iso42k_delete_lead', ['ISO42K_Ajax', 'handle_delete_lead']);
    add_action('wp_ajax_iso42k_export_leads_csv', ['ISO42K_Ajax', 'handle_export_csv']);
    add_action('wp_ajax_iso42k_write_test_log', ['ISO42K_Ajax', 'handle_write_test_log']);
}
  
  
  public static function register_settings() {
    register_setting('iso42k_ai_group', 'iso42k_ai_settings', [
      'sanitize_callback' => [__CLASS__, 'sanitize_ai_settings']
    ]);
    register_setting('iso42k_email_group', 'iso42k_email_settings');
    register_setting('iso42k_display_group', 'iso42k_display_settings');
    register_setting('iso42k_debug_group', 'iso42k_debug_settings');
    register_setting('iso42k_zapier_group', 'iso42k_zapier_settings');
  }
  
  /**
   * Sanitize AI settings
   */
  public static function sanitize_ai_settings($input) {
    $sanitized = [];
    
    // DeepSeek settings
    if (isset($input['deepseek_api_key'])) {
      $sanitized['deepseek_api_key'] = sanitize_text_field($input['deepseek_api_key']);
    }
    if (isset($input['deepseek_model'])) {
      $sanitized['deepseek_model'] = sanitize_text_field($input['deepseek_model']);
    }
    if (isset($input['deepseek_endpoint'])) {
      $endpoint = trim($input['deepseek_endpoint']);
      // Only sanitize if not empty, otherwise keep it empty to use default
      if (!empty($endpoint)) {
        $sanitized['deepseek_endpoint'] = esc_url_raw($endpoint);
      } else {
        $sanitized['deepseek_endpoint'] = '';
      }
    }
    
    // Qwen settings
    if (isset($input['qwen_openrouter_api_key'])) {
      $sanitized['qwen_openrouter_api_key'] = sanitize_text_field($input['qwen_openrouter_api_key']);
    }
    if (isset($input['qwen_model'])) {
      $sanitized['qwen_model'] = sanitize_text_field($input['qwen_model']);
    }
    if (isset($input['qwen_endpoint'])) {
      $endpoint = trim($input['qwen_endpoint']);
      if (!empty($endpoint)) {
        $sanitized['qwen_endpoint'] = esc_url_raw($endpoint);
      } else {
        $sanitized['qwen_endpoint'] = '';
      }
    }
    
    // Grok settings
    if (isset($input['grok_openrouter_api_key'])) {
      $sanitized['grok_openrouter_api_key'] = sanitize_text_field($input['grok_openrouter_api_key']);
    }
    if (isset($input['grok_model'])) {
      $sanitized['grok_model'] = sanitize_text_field($input['grok_model']);
    }
    if (isset($input['grok_endpoint'])) {
      $endpoint = trim($input['grok_endpoint']);
      if (!empty($endpoint)) {
        $sanitized['grok_endpoint'] = esc_url_raw($endpoint);
      } else {
        $sanitized['grok_endpoint'] = '';
      }
    }
    
    // Log the sanitized settings for debugging
    ISO42K_Logger::log('=== AI Settings Being Saved ===');
    ISO42K_Logger::log('DeepSeek API Key: ' . (empty($sanitized['deepseek_api_key']) ? 'not set' : 'set (length: ' . strlen($sanitized['deepseek_api_key']) . ')'));
    ISO42K_Logger::log('DeepSeek Model: ' . ($sanitized['deepseek_model'] ?? 'not set'));
    ISO42K_Logger::log('DeepSeek Endpoint: ' . ($sanitized['deepseek_endpoint'] ?? 'not set'));
    ISO42K_Logger::log('Qwen API Key: ' . (empty($sanitized['qwen_openrouter_api_key']) ? 'not set' : 'set (length: ' . strlen($sanitized['qwen_openrouter_api_key']) . ')'));
    ISO42K_Logger::log('Qwen Model: ' . ($sanitized['qwen_model'] ?? 'not set'));
    ISO42K_Logger::log('Qwen Endpoint: ' . ($sanitized['qwen_endpoint'] ?? 'not set'));
    ISO42K_Logger::log('Grok API Key: ' . (empty($sanitized['grok_openrouter_api_key']) ? 'not set' : 'set (length: ' . strlen($sanitized['grok_openrouter_api_key']) . ')'));
    ISO42K_Logger::log('Grok Model: ' . ($sanitized['grok_model'] ?? 'not set'));
    ISO42K_Logger::log('Grok Endpoint: ' . ($sanitized['grok_endpoint'] ?? 'not set'));
    
    return $sanitized;
  }

  public static function register_menus() {
    // Main menu
    add_menu_page(
      'ISO 42001 Gap Analysis',
      'ISO 42001',
      'manage_options',
      'iso42k-dashboard',
      [__CLASS__, 'render_dashboard'],
      'dashicons-shield',
      56
    );

    // Remove duplicate submenu
    add_action('admin_menu', function () {
      remove_submenu_page('iso42k-dashboard', 'iso42k-dashboard');
    }, 999);

    // Submenus
    add_submenu_page(
      'iso42k-dashboard',
      'Leads',
      'Leads',
      'manage_options',
      'iso42k-leads',
      ['ISO42K_Admin_Leads', 'render']
    );

    add_submenu_page(
      'iso42k-dashboard',
      'Settings',
      'Settings',
      'manage_options',
      'iso42k-settings',
      [__CLASS__, 'render_settings_page']
    );

    add_submenu_page(
      'iso42k-dashboard',
      'API Monitoring',
      'API Monitoring',
      'manage_options',
      'iso42k-api-monitoring',
      [__CLASS__, 'render_api_monitoring']
    );

    add_submenu_page(
      'iso42k-dashboard',
      'Zapier Monitoring',
      'Zapier Monitoring',
      'manage_options',
      'iso42k-zapier-monitoring',
      [__CLASS__, 'render_zapier_monitoring']
    );

    add_submenu_page(
      'iso42k-dashboard',
      'Database Diagnostic',
      'Database Diagnostic',
      'manage_options',
      'iso42k-database-diagnostic',
      [__CLASS__, 'render_database_diagnostic']
    );

    add_submenu_page(
      'iso42k-dashboard',
      'System & Debug',
      'System & Debug',
      'manage_options',
      'iso42k-debug',
      [__CLASS__, 'render_debug_settings']
    );
  }

  public static function enqueue_admin_assets($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'iso42k') === false) {
      return;
    }

    if (!defined('DUO_ISO42K_URL') || !defined('DUO_ISO42K_PATH')) {
      error_log('ISO42K: Constants not defined in enqueue_admin_assets');
      return;
    }

    $css_url = DUO_ISO42K_URL . 'admin/css/iso42k-admin.css';
    $js_url  = DUO_ISO42K_URL . 'admin/js/iso42k-admin.js';

    $css_path = DUO_ISO42K_PATH . 'admin/css/iso42k-admin.css';
    $js_path = DUO_ISO42K_PATH . 'admin/js/iso42k-admin.js';
    
    // Enqueue CSS
    if (file_exists($css_path)) {
      $css_ver = filemtime($css_path);
      wp_enqueue_style('iso42k-admin', $css_url, [], $css_ver);
    }
    
    // Enqueue JS
    if (file_exists($js_path)) {
      $js_ver = filemtime($js_path);
      wp_enqueue_script('iso42k-admin', $js_url, ['jquery'], $js_ver, true);
      
      // Localize script
      wp_localize_script('iso42k-admin', 'ISO42K_ADMIN', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('iso42k_admin_nonce'),
        'plugin_url' => DUO_ISO42K_URL,
        'admin_url' => admin_url('admin.php?page=iso42k-dashboard'),
      ]);
    } else {
      error_log('ISO42K: Admin JS not found: ' . $js_path);
    }
  }

  /* =====================================================
     DASHBOARD
  ===================================================== */

  public static function render_dashboard() {
    ?>
    <div class="iso42k-container">
      <div class="iso42k-dashboard-header">
        <h1 class="iso42k-dashboard-title">ISO 42001 Gap Analysis Dashboard</h1>
        <p>Manage leads, configuration, AI analysis, and system controls.</p>
      </div>
      
      <?php self::render_shortcode_box(); ?>
      
      <?php if (class_exists('ISO42K_Leads')): ?>
        <?php $stats = ISO42K_Leads::stats(); ?>
        <div class="iso42k-stats-grid">
          <div class="iso42k-stat-card">
            <div class="iso42k-stat-title">Total Leads</div>
            <div class="iso42k-stat-value"><?php echo esc_html($stats['total'] ?? 0); ?></div>
            <div class="iso42k-stat-trend">All time</div>
          </div>
          <div class="iso42k-stat-card">
            <div class="iso42k-stat-title">Average Score</div>
            <div class="iso42k-stat-value"><?php echo esc_html($stats['avg_score'] ?? 0); ?>%</div>
            <div class="iso42k-stat-trend <?php echo ($stats['avg_score'] ?? 0) >= 50 ? '' : 'down'; ?>">
              <?php echo ($stats['avg_score'] ?? 0) >= 50 ? 'Good' : 'Needs Improvement'; ?>
            </div>
          </div>
          <div class="iso42k-stat-card">
            <div class="iso42k-stat-title">Last 7 Days</div>
            <div class="iso42k-stat-value"><?php echo esc_html($stats['last7'] ?? 0); ?></div>
            <div class="iso42k-stat-trend">New leads</div>
          </div>
          <div class="iso42k-stat-card">
            <div class="iso42k-stat-title">Top Maturity</div>
            <div class="iso42k-stat-value"><?php echo esc_html($stats['top_maturity'] ?? 'N/A'); ?></div>
            <div class="iso42k-stat-trend">Current level</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php
  }

  /* =====================================================
     SETTINGS PAGE (COMBINED TABS)
  ===================================================== */

  public static function render_settings_page() {
    $tab = sanitize_key($_GET['tab'] ?? 'ai');
    if (!in_array($tab, ['ai','email','zapier','display'], true)) {
      $tab = 'ai';
    }

    ?>
    <div class="iso42k-container">
      <div class="iso42k-dashboard-header">
        <h1 class="iso42k-dashboard-title">ISO 42001 Assessment Settings</h1>
      </div>

      <div class="iso42k-settings-container">
        <div class="iso42k-settings-tabs">
          <div class="iso42k-tab <?php echo $tab==='ai'?'active':''; ?>" data-tab="iso42k-ai-settings">
            <i class="dashicons dashicons-ai"></i> AI Integration
          </div>
          <div class="iso42k-tab <?php echo $tab==='email'?'active':''; ?>" data-tab="iso42k-email-settings">
            <i class="dashicons dashicons-email"></i> Email Settings
          </div>
          <div class="iso42k-tab <?php echo $tab==='zapier'?'active':''; ?>" data-tab="iso42k-zapier-settings">
            <i class="dashicons dashicons-controls-repeat"></i> Zapier Integration
          </div>
          <div class="iso42k-tab <?php echo $tab==='display'?'active':''; ?>" data-tab="iso42k-display-settings">
            <i class="dashicons dashicons-layout"></i> Display Settings
          </div>
        </div>

        <div id="iso42k-ai-settings" class="iso42k-tab-content <?php echo $tab==='ai'?'active':''; ?>">
          <?php self::render_ai_settings_block(); ?>
        </div>
        
        <div id="iso42k-email-settings" class="iso42k-tab-content <?php echo $tab==='email'?'active':''; ?>">
          <?php self::render_email_settings_block(); ?>
        </div>
        
        <div id="iso42k-zapier-settings" class="iso42k-tab-content <?php echo $tab==='zapier'?'active':''; ?>">
          <?php self::render_zapier_settings_block(); ?>
        </div>
        
        <div id="iso42k-display-settings" class="iso42k-tab-content <?php echo $tab==='display'?'active':''; ?>">
          <?php self::render_display_settings_block(); ?>
        </div>
      </div>
    </div>
    <?php
  }

  /* =====================================================
     AI SETTINGS TAB
  ===================================================== */

  private static function render_ai_settings_block() {
    $s = (array) get_option('iso42k_ai_settings', []);
    ?>
    <div class="iso42k-form-section">
      <h3><span class="dashicons dashicons-superhero"></span> AI Provider Configuration</h3>
      <p style="color: var(--iso42k-gray); margin: 0 0 24px;">Configure one or more AI providers for automated gap analysis. Each provider requires its own API key and can be tested independently.</p>
      
      <form method="post" action="options.php">
        <?php settings_fields('iso42k_ai_group'); ?>

        <!-- DeepSeek Configuration -->
        <div class="iso42k-ai-provider-card" style="background: var(--iso42k-white); border: 2px solid var(--iso42k-border); border-radius: var(--iso42k-border-radius-lg); padding: 24px; margin-bottom: 24px;">
          <h4 style="margin: 0 0 20px; font-size: 18px; color: var(--iso42k-dark); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-cloud" style="color: var(--iso42k-primary);"></span>
            DeepSeek
          </h4>
          
          <div class="iso42k-form-grid">
            <div class="iso42k-form-group">
              <label for="iso42k-deepseek-api-key">
                <span class="dashicons dashicons-lock"></span>
                API Key
              </label>
              <input type="password" class="iso42k-form-control" id="iso42k-deepseek-api-key"
                name="iso42k_ai_settings[deepseek_api_key]"
                value="<?php echo esc_attr($s['deepseek_api_key'] ?? ''); ?>"
                autocomplete="off"
                placeholder="sk-...">
              <p class="description">DeepSeek API key from <a href="https://platform.deepseek.com/" target="_blank">platform.deepseek.com</a></p>
            </div>

            <div class="iso42k-form-group">
              <label for="iso42k-deepseek-model">
                <span class="dashicons dashicons-admin-generic"></span>
                Model
              </label>
              <input type="text" class="iso42k-form-control" id="iso42k-deepseek-model"
                name="iso42k_ai_settings[deepseek_model]"
                value="<?php echo esc_attr($s['deepseek_model'] ?? 'deepseek-chat'); ?>"
                placeholder="deepseek-chat">
              <p class="description">Model name (default: deepseek-chat)</p>
            </div>
          </div>

          <div class="iso42k-form-group" style="margin-top: 16px;">
            <label for="iso42k-deepseek-endpoint">
              <span class="dashicons dashicons-admin-site-alt3"></span>
              Endpoint Override (optional)
            </label>
            <input type="url" class="iso42k-form-control" id="iso42k-deepseek-endpoint"
              name="iso42k_ai_settings[deepseek_endpoint]"
              value="<?php echo esc_attr($s['deepseek_endpoint'] ?? ''); ?>"
              placeholder="https://api.deepseek.com/v1/chat/completions">
            <p class="description">Leave blank to use default endpoint</p>
          </div>

          <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--iso42k-border);">
            <button type="button" class="iso42k-btn iso42k-btn-outline" id="iso42k-deepseek-test" style="width: 100%;">
              <span class="dashicons dashicons-update"></span>
              Test DeepSeek Connection
            </button>
            <div id="iso42k-deepseek-test-result" style="margin-top: 16px;"></div>
          </div>
        </div>

        <!-- Qwen via OpenRouter Configuration -->
        <div class="iso42k-ai-provider-card" style="background: var(--iso42k-white); border: 2px solid var(--iso42k-border); border-radius: var(--iso42k-border-radius-lg); padding: 24px; margin-bottom: 24px;">
          <h4 style="margin: 0 0 20px; font-size: 18px; color: var(--iso42k-dark); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-cloud" style="color: var(--iso42k-secondary);"></span>
            Qwen (via OpenRouter)
          </h4>
          
          <div class="iso42k-form-grid">
            <div class="iso42k-form-group">
              <label for="iso42k-qwen-openrouter-api-key">
                <span class="dashicons dashicons-lock"></span>
                OpenRouter API Key
              </label>
              <input type="password" class="iso42k-form-control" id="iso42k-qwen-openrouter-api-key"
                name="iso42k_ai_settings[qwen_openrouter_api_key]"
                value="<?php echo esc_attr($s['qwen_openrouter_api_key'] ?? ''); ?>"
                autocomplete="off"
                placeholder="sk-or-v1-...">
              <p class="description">OpenRouter API key from <a href="https://openrouter.ai/" target="_blank">openrouter.ai</a></p>
            </div>

            <div class="iso42k-form-group">
              <label for="iso42k-qwen-model">
                <span class="dashicons dashicons-admin-generic"></span>
                Model
              </label>
              <input type="text" class="iso42k-form-control" id="iso42k-qwen-model"
                name="iso42k_ai_settings[qwen_model]"
                value="<?php echo esc_attr($s['qwen_model'] ?? 'qwen/qwen-2.5-coder-32b-instruct'); ?>"
                placeholder="qwen/qwen-2.5-coder-32b-instruct">
              <p class="description">Model name on OpenRouter</p>
            </div>
          </div>

          <div class="iso42k-form-group" style="margin-top: 16px;">
            <label for="iso42k-qwen-endpoint">
              <span class="dashicons dashicons-admin-site-alt3"></span>
              Endpoint Override (optional)
            </label>
            <input type="url" class="iso42k-form-control" id="iso42k-qwen-endpoint"
              name="iso42k_ai_settings[qwen_endpoint]"
              value="<?php echo esc_attr($s['qwen_endpoint'] ?? ''); ?>"
              placeholder="https://openrouter.ai/api/v1/chat/completions">
            <p class="description">Leave blank to use default endpoint</p>
          </div>

          <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--iso42k-border);">
            <button type="button" class="iso42k-btn iso42k-btn-outline" id="iso42k-qwen-test" style="width: 100%;">
              <span class="dashicons dashicons-update"></span>
              Test Qwen Connection
            </button>
            <div id="iso42k-qwen-test-result" style="margin-top: 16px;"></div>
          </div>
        </div>

        <!-- Grok via OpenRouter Configuration -->
        <div class="iso42k-ai-provider-card" style="background: var(--iso42k-white); border: 2px solid var(--iso42k-border); border-radius: var(--iso42k-border-radius-lg); padding: 24px; margin-bottom: 24px;">
          <h4 style="margin: 0 0 20px; font-size: 18px; color: var(--iso42k-dark); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-cloud" style="color: var(--iso42k-warning);"></span>
            Grok (via OpenRouter)
          </h4>
          
          <div class="iso42k-form-grid">
            <div class="iso42k-form-group">
              <label for="iso42k-grok-openrouter-api-key">
                <span class="dashicons dashicons-lock"></span>
                OpenRouter API Key
              </label>
              <input type="password" class="iso42k-form-control" id="iso42k-grok-openrouter-api-key"
                name="iso42k_ai_settings[grok_openrouter_api_key]"
                value="<?php echo esc_attr($s['grok_openrouter_api_key'] ?? ''); ?>"
                autocomplete="off"
                placeholder="sk-or-v1-...">
              <p class="description">OpenRouter API key from <a href="https://openrouter.ai/" target="_blank">openrouter.ai</a></p>
            </div>

            <div class="iso42k-form-group">
              <label for="iso42k-grok-model">
                <span class="dashicons dashicons-admin-generic"></span>
                Model
              </label>
              <input type="text" class="iso42k-form-control" id="iso42k-grok-model"
                name="iso42k_ai_settings[grok_model]"
                value="<?php echo esc_attr($s['grok_model'] ?? 'x-ai/grok-beta'); ?>"
                placeholder="x-ai/grok-beta">
              <p class="description">Model name on OpenRouter</p>
            </div>
          </div>

          <div class="iso42k-form-group" style="margin-top: 16px;">
            <label for="iso42k-grok-endpoint">
              <span class="dashicons dashicons-admin-site-alt3"></span>
              Endpoint Override (optional)
            </label>
            <input type="url" class="iso42k-form-control" id="iso42k-grok-endpoint"
              name="iso42k_ai_settings[grok_endpoint]"
              value="<?php echo esc_attr($s['grok_endpoint'] ?? ''); ?>"
              placeholder="https://openrouter.ai/api/v1/chat/completions">
            <p class="description">Leave blank to use default endpoint</p>
          </div>

          <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--iso42k-border);">
            <button type="button" class="iso42k-btn iso42k-btn-outline" id="iso42k-grok-test" style="width: 100%;">
              <span class="dashicons dashicons-update"></span>
              Test Grok Connection
            </button>
            <div id="iso42k-grok-test-result" style="margin-top: 16px;"></div>
          </div>
        </div>

        <div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid var(--iso42k-border);">
          <button type="submit" class="iso42k-btn iso42k-btn-primary">
            <span class="dashicons dashicons-saved"></span>
            Save All AI Settings
          </button>
          <p class="description" style="margin-top: 12px;">
            💡 <strong>Tip:</strong> You can configure multiple AI providers. The system will use the first available provider when generating analysis.
          </p>
        </div>
      </form>
    </div>
    <?php
  }


  
  /* =====================================================
     EMAIL SETTINGS TAB
  ===================================================== */

  private static function render_email_settings_block() {
    $s = (array) get_option('iso42k_email_settings', []);
    $default_from_name = get_bloginfo('name');
    $default_from_email = get_option('admin_email');
    ?>
    <form method="post" action="options.php">
      <?php settings_fields('iso42k_email_group'); ?>

      <table class="form-table">
        <tr>
          <th><label for="iso42k-from-name">From Name</label></th>
          <td>
            <input type="text" class="regular-text" id="iso42k-from-name" 
              name="iso42k_email_settings[from_name]"
              value="<?php echo esc_attr($s['from_name'] ?? $default_from_name); ?>">
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-from-email">From Email</label></th>
          <td>
            <input type="email" class="regular-text" id="iso42k-from-email"
              name="iso42k_email_settings[from_email]"
              value="<?php echo esc_attr($s['from_email'] ?? $default_from_email); ?>">
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-company-logo-url">Logo URL</label></th>
          <td>
            <input type="url" class="regular-text" id="iso42k-company-logo-url"
              name="iso42k_email_settings[company_logo_url]"
              value="<?php echo esc_attr($s['company_logo_url'] ?? ''); ?>"
              placeholder="https://example.com/logo.png">
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-meeting-scheduler-url">Meeting Scheduler URL</label></th>
          <td>
            <input type="url" class="regular-text" id="iso42k-meeting-scheduler-url"
              name="iso42k_email_settings[meeting_scheduler_url]"
              value="<?php echo esc_attr($s['meeting_scheduler_url'] ?? ''); ?>"
              placeholder="https://calendly.com/your-link">
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-meeting-button-text">Meeting Button Text</label></th>
          <td>
            <input type="text" class="regular-text" id="iso42k-meeting-button-text"
              name="iso42k_email_settings[meeting_button_text]"
              value="<?php echo esc_attr($s['meeting_button_text'] ?? 'Book a Consultation'); ?>">
          </td>
        </tr>

        <tr>
          <th>Admin Notifications</th>
          <td>
            <label>
              <input type="checkbox" name="iso42k_email_settings[admin_notification_enabled]" value="1"
                     <?php checked(!empty($s['admin_notification_enabled']), true); ?>>
              Send copy of assessment results to admin
            </label>
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-admin-emails">Admin Email Recipients</label></th>
          <td>
            <input type="text" class="regular-text" id="iso42k-admin-emails"
              name="iso42k_email_settings[admin_notification_emails]"
              value="<?php echo esc_attr($s['admin_notification_emails'] ?? get_option('admin_email')); ?>"
              placeholder="admin@example.com, sales@example.com">
            <p class="description">Comma or semicolon separated. Leave blank to use WordPress admin email.</p>
          </td>
        </tr>

        <tr>
          <th>Current Configuration</th>
          <td>
            <?php 
            echo '<pre style="background:#f9f9f9;padding:10px;border:1px solid #ddd;overflow:auto;max-height:200px;font-size:12px;line-height:1.5;">';
            echo 'Admin Notifications: ' . (isset($s['admin_notification_enabled']) && $s['admin_notification_enabled'] ? 'ENABLED' : 'DISABLED (defaults to ENABLED)') . "\n";
            echo 'Admin Recipients: ' . ($s['admin_notification_emails'] ?? '(using ' . get_option('admin_email') . ')') . "\n";
            echo 'From Name: ' . ($s['from_name'] ?? get_bloginfo('name')) . "\n";
            echo 'From Email: ' . ($s['from_email'] ?? get_option('admin_email')) . "\n";
            echo '</pre>';
            ?>
            <p class="description">Verify these values match your expectations.</p>
          </td>
        </tr>

        <tr>
          <th>Test Admin Email</th>
          <td>
            <button type="button" class="button" id="iso42k-test-admin-email">Send Test Email</button>
            <p class="description">Sends a test notification to configured admin emails.</p>
            <div id="iso42k-test-email-result" style="margin-top:10px;"></div>
          </td>
        </tr>
      </table>

      <?php submit_button('Save Email Settings'); ?>
    </form>
    <?php
  }

  /* =====================================================
     ZAPIER SETTINGS TAB
  ===================================================== */

  private static function render_zapier_settings_block() {
    $s = (array) get_option('iso42k_zapier_settings', []);
    $enabled = isset($s['enabled']) ? (bool) $s['enabled'] : false;
    $include_answers = isset($s['include_answers']) ? (bool) $s['include_answers'] : true;
    $include_ai = isset($s['include_ai']) ? (bool) $s['include_ai'] : true;
    
    $metrics = class_exists('ISO42K_Zapier') ? ISO42K_Zapier::get_metrics() : [];
    ?>
    <form method="post" action="options.php">
      <?php settings_fields('iso42k_zapier_group'); ?>

      <table class="form-table">
        <tr>
          <th>Enable Zapier Integration</th>
          <td>
            <label>
              <input type="checkbox" name="iso42k_zapier_settings[enabled]" value="1"
                     <?php checked($enabled, true); ?>>
              Send assessment data to Zapier webhook
            </label>
            <p class="description">When enabled, assessment data will be sent to your Zapier webhook URL automatically.</p>
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-zapier-webhook">Zapier Webhook URL</label></th>
          <td>
            <input type="url" class="large-text code" id="iso42k-zapier-webhook"
              name="iso42k_zapier_settings[webhook_url]"
              value="<?php echo esc_attr($s['webhook_url'] ?? ''); ?>"
              placeholder="https://hooks.zapier.com/hooks/catch/12345/abcdef/">
            <p class="description">
              Get this URL from your Zapier "Webhooks by Zapier" trigger. 
              <a href="https://zapier.com/apps/webhook/integrations" target="_blank">Learn more</a>
            </p>
          </td>
        </tr>

        <tr>
          <th>Data Options</th>
          <td>
            <fieldset>
              <label>
                <input type="checkbox" name="iso42k_zapier_settings[include_answers]" value="1"
                       <?php checked($include_answers, true); ?>>
                Include all question answers in webhook payload
              </label>
              <br>
              <label style="margin-top:8px;display:inline-block;">
                <input type="checkbox" name="iso42k_zapier_settings[include_ai]" value="1"
                       <?php checked($include_ai, true); ?>>
                Include AI-powered gap analysis summary
              </label>
            </fieldset>
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-zapier-custom">Custom Fields (optional)</label></th>
          <td>
            <textarea id="iso42k-zapier-custom" name="iso42k_zapier_settings[custom_fields]" 
                      rows="5" class="large-text code"
                      placeholder="utm_source={company}&#10;lead_score={score}&&#10;custom_tag=ISO42001"><?php echo esc_textarea($s['custom_fields'] ?? ''); ?></textarea>
            <p class="description">
              Add custom fields (one per line) in format: <code>key=value</code><br>
              Use variables: <code>{company}</code>, <code>{staff}</code>, <code>{name}</code>, <code>{email}</code>, <code>{phone}</code>, <code>{score}</code>, <code>{maturity}</code>
            </p>
          </td>
        </tr>

        <?php if (!empty($metrics)): ?>
        <tr>
          <th>Webhook Statistics</th>
          <td>
            <table class="widefat" style="max-width:600px;">
              <tr>
                <td style="padding:8px;"><strong>Total Calls</strong></td>
                <td style="padding:8px;"><?php echo esc_html($metrics['total'] ?? 0); ?></td>
              </tr>
              <tr>
                <td style="padding:8px;"><strong>Successful</strong></td>
                <td style="padding:8px;color:#10B981;font-weight:600;"><?php echo esc_html($metrics['success'] ?? 0); ?></td>
              </tr>
              <tr>
                <td style="padding:8px;"><strong>Failed</strong></td>
                <td style="padding:8px;color:#DC2626;font-weight:600;"><?php echo esc_html($metrics['failed'] ?? 0); ?></td>
              </tr>
              <tr>
                <td style="padding:8px;"><strong>Avg Latency</strong></td>
                <td style="padding:8px;"><?php echo esc_html($metrics['avg_latency_ms'] ?? 0); ?> ms</td>
              </tr>
              <tr>
                <td style="padding:8px;"><strong>Last Call</strong></td>
                <td style="padding:8px;"><?php echo esc_html($metrics['last_call_at'] ?? 'Never'); ?></td>
              </tr>
              <?php if (!empty($metrics['last_error'])): ?>
              <tr>
                <td style="padding:8px;"><strong>Last Error</strong></td>
                <td style="padding:8px;color:#DC2626;"><code><?php echo esc_html($metrics['last_error']); ?></code></td>
              </tr>
              <?php endif; ?>
            </table>
          </td>
        </tr>
        <?php endif; ?>
      </table>

      <?php submit_button('Save Zapier Settings'); ?>
    </form>

    <hr>
    <h2>Test Webhook Connection</h2>
    <div class="iso42k-zapier-test-area">
      <button type="button" class="button button-primary" id="iso42k-zapier-test">Send Test Webhook to Zapier</button>
      <span class="iso42k-zapier-spinner" style="display: none;">
        <span class="spinner is-active" style="float: none; margin: 0 5px;"></span>
        <span>Testing connection...</span>
      </span>
      <div id="iso42k-zapier-test-result" style="margin-top:15px;display:none;"></div>
    </div>
    <?php
  }

  /* =====================================================
     DISPLAY SETTINGS TAB
  ===================================================== */

  private static function render_display_settings_block() {
    $s = (array) get_option('iso42k_display_settings', []);
    $dashboard_widget = isset($s['dashboard_widget']) ? (bool) $s['dashboard_widget'] : false;
    $admin_scheme = $s['admin_scheme'] ?? 'light';
    ?>
    <form method="post" action="options.php">
      <?php settings_fields('iso42k_display_group'); ?>

      <table class="form-table">
        <tr>
          <th>Dashboard Widget</th>
          <td>
            <label>
              <input type="checkbox" name="iso42k_display_settings[dashboard_widget]" value="1"
                     <?php checked($dashboard_widget, true); ?>>
              Enable WordPress Dashboard widget (quick stats)
            </label>
          </td>
        </tr>

        <tr>
          <th><label for="iso42k-admin-scheme">Admin Scheme</label></th>
          <td>
            <select name="iso42k_display_settings[admin_scheme]" id="iso42k-admin-scheme">
              <option value="light" <?php selected($admin_scheme, 'light'); ?>>Light</option>
              <option value="dark" <?php selected($admin_scheme, 'dark'); ?>>Dark</option>
            </select>
          </td>
        </tr>
      </table>

      <?php submit_button('Save Display Settings'); ?>
    </form>
    <?php
  }

  /* =====================================================
     AJAX HANDLERS
  ===================================================== */

  /**
   * Handle admin email test
   */
  public static function handle_test_admin_email() {
    check_ajax_referer('iso42k_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Insufficient permissions']);
      return;
    }
    
    ISO42K_Logger::log('=== Admin Email Test Started ===');
    
    // Get current admin email settings
    $email_settings = (array) get_option('iso42k_email_settings', []);
    $admin_emails = $email_settings['admin_notification_emails'] ?? get_option('admin_email');
    
    // Support both comma and semicolon separated emails
    $admin_emails = array_filter(array_map('trim', preg_split('/[,;]/', $admin_emails)));
    
    if (empty($admin_emails)) {
      ISO42K_Logger::log('❌ No admin email addresses configured');
      wp_send_json_error(['message' => 'No admin email addresses configured. Please set them in the email settings.']);
      return;
    }
    
    // Validate all email addresses first
    $valid_emails = [];
    $invalid_emails = [];
    foreach ($admin_emails as $email) {
      if (is_email($email)) {
        $valid_emails[] = $email;
      } else {
        $invalid_emails[] = $email;
      }
    }
    
    if (empty($valid_emails)) {
      ISO42K_Logger::log('❌ No valid admin email addresses found: ' . implode(', ', $admin_emails));
      wp_send_json_error(['message' => 'No valid email addresses found. Please check your admin email configuration.']);
      return;
    }
    
    // Log validation results
    ISO42K_Logger::log('✅ Valid emails: ' . implode(', ', $valid_emails));
    if (!empty($invalid_emails)) {
      ISO42K_Logger::log('⚠️ Invalid emails: ' . implode(', ', $invalid_emails));
    }
    
    $test_lead = [
      'company' => 'Test Company Ltd',
      'staff' => 25,
      'name' => 'Test User',
      'email' => 'test@example.com',
      'phone' => '+852 1234 5678',
      'percent' => 65,
      'maturity' => 'Established',
      'ai_summary' => 'This is a test AI summary for debugging purposes.'
    ];
    
    $sent = ISO42K_Email::send_admin($test_lead, 65, 'Established', 'Test AI summary');
    
    if ($sent) {
      ISO42K_Logger::log('✅ Test email sent successfully to: ' . implode(', ', $valid_emails));
      wp_send_json_success([
        'message' => 'Test email sent successfully to: ' . implode(', ', $valid_emails) . '! Check your inboxes.',
        'sent_to' => $valid_emails,
        'invalid_emails' => $invalid_emails
      ]);
    } else {
      ISO42K_Logger::log('❌ Test email failed to send to any recipients');
      wp_send_json_error(['message' => 'Email failed to send. Check your server mail configuration.']);
    }
  }

  /**
   * Handle Zapier webhook test
   */
  public static function handle_test_zapier() {
    check_ajax_referer('iso42k_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Insufficient permissions']);
      return;
    }

    $webhook_url = sanitize_url($_POST['webhook_url'] ?? '');
    
    if (empty($webhook_url)) {
      wp_send_json_error(['message' => 'Webhook URL is required']);
      return;
    }

    if (!class_exists('ISO42K_Zapier')) {
      wp_send_json_error(['message' => 'Zapier class not found']);
      return;
    }

    ISO42K_Logger::log('=== Zapier Test Started ===');
    ISO42K_Logger::log('Webhook URL: ' . $webhook_url);

    $result = ISO42K_Zapier::test_webhook($webhook_url);
    
    if ($result['success']) {
      ISO42K_Logger::log('✅ Zapier test successful');
      wp_send_json_success(['message' => $result['message']]);
    } else {
      ISO42K_Logger::log('❌ Zapier test failed: ' . $result['message']);
      wp_send_json_error(['message' => $result['message']]);
    }
  }

  /**
   * Handle DeepSeek connection test
   */
  public static function handle_test_deepseek() {
    check_ajax_referer('iso42k_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Insufficient permissions']);
      return;
    }

    ISO42K_Logger::log('=== DeepSeek Connection Test Started ===');

    $result = ISO42K_AI::test_connection('deepseek');
    
    if ($result['success']) {
      ISO42K_Logger::log('✅ DeepSeek test successful: ' . $result['message']);
      wp_send_json_success(['message' => $result['message']]);
    } else {
      ISO42K_Logger::log('❌ DeepSeek test failed: ' . $result['message']);
      wp_send_json_error(['message' => $result['message']]);
    }
  }

  /**
   * Handle Qwen via OpenRouter connection test
   */
  public static function handle_test_qwen() {
    check_ajax_referer('iso42k_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Insufficient permissions']);
      return;
    }

    ISO42K_Logger::log('=== Qwen via OpenRouter Connection Test Started ===');

    $result = ISO42K_AI::test_connection('qwen');
    
    if ($result['success']) {
      ISO42K_Logger::log('✅ Qwen test successful: ' . $result['message']);
      wp_send_json_success(['message' => $result['message']]);
    } else {
      ISO42K_Logger::log('❌ Qwen test failed: ' . $result['message']);
      wp_send_json_error(['message' => $result['message']]);
    }
  }

  /**
   * Handle Grok via OpenRouter connection test
   */
  public static function handle_test_grok() {
    check_ajax_referer('iso42k_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Insufficient permissions']);
      return;
    }

    ISO42K_Logger::log('=== Grok via OpenRouter Connection Test Started ===');

    $result = ISO42K_AI::test_connection('grok');
    
    if ($result['success']) {
      ISO42K_Logger::log('✅ Grok test successful: ' . $result['message']);
      wp_send_json_success(['message' => $result['message']]);
    } else {
      ISO42K_Logger::log('❌ Grok test failed: ' . $result['message']);
      wp_send_json_error(['message' => $result['message']]);
    }
  }

  /* =====================================================
     ADMIN POST HANDLERS
  ===================================================== */

  public static function handle_retry_ai() {
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $lead_id = intval($_GET['lead_id'] ?? 0);
    if (!$lead_id) {
      wp_die('Invalid lead ID');
    }

    check_admin_referer('iso42k_retry_ai_' . $lead_id);

    $lead = ISO42K_Leads::get_by_id($lead_id);
    if (!$lead) {
      wp_safe_redirect(admin_url('admin.php?page=iso42k-leads&retry_ai=failed&error=lead_not_found'));
      exit;
    }

    $answers = is_string($lead['answers']) ? json_decode($lead['answers'], true) : $lead['answers'];
    
    if (!is_array($answers) || empty($answers)) {
      ISO42K_Logger::log('Invalid or empty answers for lead ID: ' . $lead_id);
      wp_safe_redirect(admin_url('admin.php?page=iso42k-leads&retry_ai=failed&error=no_answers'));
      exit;
    }

    ISO42K_Logger::log('Retrying AI analysis for lead ID: ' . $lead_id);
    
    try {
      $raw_ai = ISO42K_AI::analyse($answers);
      
      if (empty($raw_ai)) {
        ISO42K_Logger::log('AI returned empty for lead ID: ' . $lead_id);
        wp_safe_redirect(admin_url('admin.php?page=iso42k-leads&retry_ai=failed&error=empty_response'));
        exit;
      }
      
      $ai_summary = self::clean_ai_markdown($raw_ai);
      
      $updated = ISO42K_Leads::update($lead_id, ['ai_summary' => $ai_summary]);
      
      if ($updated) {
        ISO42K_Logger::log('✅ AI regenerated for lead ID: ' . $lead_id);
        wp_safe_redirect(admin_url('admin.php?page=iso42k-leads&retry_ai=success'));
      } else {
        ISO42K_Logger::log('Failed to update lead: ' . $lead_id);
        wp_safe_redirect(admin_url('admin.php?page=iso42k-leads&retry_ai=failed&error=update_failed'));
      }
      exit;
      
    } catch (\Throwable $e) {
      ISO42K_Logger::log('AI retry exception: ' . $e->getMessage());
      wp_safe_redirect(admin_url('admin.php?page=iso42k-leads&retry_ai=failed&error=exception'));
      exit;
    }
  }

  public static function handle_create_table() {
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    
    check_admin_referer('iso42k_create_table');
    
    ISO42K_Leads::create_table();
    
    wp_safe_redirect(admin_url('admin.php?page=iso42k-database-diagnostic&created=1'));
    exit;
  }

  public static function reset_api_metrics() {
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    
    check_admin_referer('iso42k_reset_api_metrics');
    update_option('iso42k_api_metrics', []);
    
    wp_safe_redirect(admin_url('admin.php?page=iso42k-api-monitoring&reset=1'));
    exit;
  }

  /* =====================================================
     RENDERING METHODS
  ===================================================== */

  public static function render_api_monitoring() {
    if (!defined('DUO_ISO42K_PATH')) {
      echo '<div class="wrap"><h1>API Monitoring</h1><p>Plugin path not defined.</p></div>';
      return;
    }
    
    $template_path = DUO_ISO42K_PATH . 'admin/templates/api-monitoring.php';
    if (!file_exists($template_path)) {
      error_log('ISO42K: API monitoring template not found: ' . $template_path);
      echo '<div class="wrap"><h1>API Monitoring</h1><p>Template not found.</p></div>';
      return;
    }
    
    require $template_path;
  }

  public static function render_zapier_monitoring() {
    if (!defined('DUO_ISO42K_PATH')) {
      echo '<div class="wrap"><h1>Zapier Monitoring</h1><p>Plugin path not defined.</p></div>';
      return;
    }
    
    $template_path = DUO_ISO42K_PATH . 'admin/templates/zapier-monitoring.php';
    if (!file_exists($template_path)) {
      error_log('ISO42K: Zapier monitoring template not found: ' . $template_path);
      echo '<div class="wrap"><h1>Zapier Monitoring</h1><p>Template not found.</p></div>';
      return;
    }
    
    require $template_path;
  }

  public static function render_database_diagnostic() {
    if (!defined('DUO_ISO42K_PATH')) {
      echo '<div class="wrap"><h1>Database Diagnostic</h1><p>Plugin path not defined.</p></div>';
      return;
    }
    
    $template_path = DUO_ISO42K_PATH . 'admin/templates/database-diagnostic.php';
    if (!file_exists($template_path)) {
      error_log('ISO42K: Database diagnostic template not found: ' . $template_path);
      
      // Render inline if template missing
      self::render_database_diagnostic_inline();
      return;
    }
    
    require $template_path;
  }

  public static function render_debug_settings() {
    $s = (array) get_option('iso42k_debug_settings', []);
    $debug_on = !empty($s['debug']);

    $log_path = WP_CONTENT_DIR . '/debug.log';
    $log_tail = '';
    if (file_exists($log_path) && is_readable($log_path)) {
      $lines = @file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      if (is_array($lines)) {
        $tail = array_slice($lines, -200);
        $log_tail = implode("\n", $tail);
      }
    }
    ?>
    <div class="wrap">
      <h1>System & Debug</h1>

      <form method="post" action="options.php">
        <?php settings_fields('iso42k_debug_group'); ?>
        <table class="form-table">
          <tr>
            <th>Debug Mode</th>
            <td>
              <label>
                <input type="checkbox" name="iso42k_debug_settings[debug]" value="1" <?php checked($debug_on, true); ?>>
                Enable plugin debug logging
              </label>
              <p class="description">Logs will be written to <?php echo esc_html($log_path); ?></p>
            </td>
          </tr>
        </table>
        <?php submit_button('Save Debug Settings'); ?>
      </form>

      <hr>
      <h2>Utilities</h2>
      <button type="button" class="button" id="iso42k-write-test-log">Write Test Log Entry</button>
      <span id="iso42k-write-test-log-result" style="margin-left:10px;"></span>

      <hr>
      <h2>debug.log (last 200 lines)</h2>
      <?php if (empty($log_tail)): ?>
        <p><em>No log content found (or debug.log not readable).</em></p>
      <?php else: ?>
        <textarea readonly style="width:100%;height:340px;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;font-size:12px;line-height:1.4;"><?php
          echo esc_textarea($log_tail);
        ?></textarea>
        <p class="description">File: <?php echo esc_html($log_path); ?> | Size: <?php echo esc_html(size_format(filesize($log_path))); ?></p>
      <?php endif; ?>
    </div>
    <?php
  }

  public static function maybe_register_dashboard_widget() {
    $s = (array) get_option('iso42k_display_settings', []);
    if (empty($s['dashboard_widget'])) return;

    if (!class_exists('ISO42K_Leads')) {
      error_log('ISO42K: ISO42K_Leads class not found for dashboard widget');
      return;
    }

    wp_add_dashboard_widget(
      'iso42k_dashboard_widget',
      'ISO 42001 Gap Analysis',
      [__CLASS__, 'render_dashboard_widget'],
      null,
      null,
      'normal',
      'high'
    );
  }

  public static function render_shortcode_box() {
    ?>
    <div style="margin:16px 0;padding:14px 16px;border:1px solid #ccd0d4;background:#fff;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
      <h2 style="margin:0 0 8px;font-size:18px;">Assessment Shortcode</h2>
      <p style="margin:0 0 12px;color:#555;line-height:1.5;">
        Paste this into a WordPress page/post, or use Breakdance "Shortcode" element.
      </p>

      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
        <input type="text" readonly value="[iso42k_assessment]"
               style="width:240px;padding:8px 10px;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;font-size:14px;border:1px solid #d1d5db;border-radius:4px;background:#f9fafb;"
               onclick="this.select();">
        <button type="button"
          class="button iso42k-copy-shortcode"
          data-shortcode="[iso42k_assessment]"
          style="padding:6px 12px;">
          Copy
        </button>

        <span style="color:#666;font-size:13px;">(Legacy: <code>[iso42001_gap_analysis]</code>)</span>
      </div>

      <p style="margin:0;color:#666;font-size:13px;line-height:1.5;">
        <strong>Breakdance tip:</strong> Add a <strong>Shortcode</strong> element and enter <code>iso42k_assessment</code>
      </p>
    </div>
    <?php
  }

  public static function render_dashboard_widget() {
    if (!class_exists('ISO42K_Leads')) {
      echo '<p>Leads class not available.</p>';
      return;
    }
    
    $stats = ISO42K_Leads::stats();
    ?>
    <div style="line-height:1.6;">
      <p><strong>Total Leads:</strong> <?php echo esc_html($stats['total'] ?? 0); ?></p>
      <p><strong>Avg Score:</strong> <?php echo esc_html($stats['avg_score'] ?? 0); ?>%</p>
      <p><strong>Last 7 days:</strong> <?php echo esc_html($stats['last7'] ?? 0); ?></p>
      <p style="margin-top:12px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=iso42k-leads')); ?>" class="button button-small">
          View Leads
        </a>
      </p>
    </div>
    <?php
  }

  /* =====================================================
     INLINE DATABASE DIAGNOSTIC (FALLBACK)
  ===================================================== */

  private static function render_database_diagnostic_inline() {
    global $wpdb;
    $table = $wpdb->prefix . 'iso42k_leads';
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
    
    // Get record count if table exists
    $total_records = 0;
    if ($table_exists) {
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
    ?>
    <div class="wrap">
        <h1>Database Diagnostic</h1>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th colspan="2">Database Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th style="width:200px;">Table Name</th>
                    <td><code><?php echo esc_html($table); ?></code></td>
                </tr>
                <tr>
                    <th>Table Exists</th>
                    <td>
                        <?php if ($table_exists): ?>
                            <span style="color:green;">✓ Yes</span>
                        <?php else: ?>
                            <span style="color:red;">✗ No</span>
                            <p>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=iso42k_create_table'), 'iso42k_create_table'); ?>" 
                                   class="button button-primary">
                                    Create Table Now
                                </a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($table_exists): ?>
                <tr>
                    <th>Total Records</th>
                    <td><?php echo esc_html($total_records); ?></td>
                </tr>
                <tr>
                    <th>Table Structure</th>
                    <td>
                        <?php 
                        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");
                        if ($columns) {
                            echo '<ul style="margin:0;padding-left:15px;">';
                            foreach ($columns as $col) {
                                echo '<li><code>' . esc_html($col->Field) . '</code> - ' . esc_html($col->Type) . '</li>';
                            }
                            echo '</ul>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
  }

  /* =====================================================
     HELPER METHODS
  ===================================================== */

  /**
   * Clean AI markdown formatting
   */
  private static function clean_ai_markdown(string $text): string {
    if (empty($text)) return '';
    
    $text = preg_replace('/^#{1,6}\s+/m', '', $text);
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/s', '$1', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
    $text = preg_replace('/\*(.+?)\*/s', '$1', $text);
    $text = preg_replace('/__(.+?)__/s', '$1', $text);
    $text = preg_replace('/_(.+?)_/s', '$1', $text);
    $text = preg_replace('/^[\-\*\+]\s+/m', '• ', $text);
    $text = preg_replace('/^\d+\.\s+/m', '', $text);
    $text = preg_replace('/```[a-z]*\n/i', '', $text);
    $text = str_replace('```', '', $text);
    $text = preg_replace('/`(.+?)`/', '$1', $text);
    $text = preg_replace('/^>\s+/m', '', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = preg_replace('/ {2,}/', ' ', $text);
    
    return trim($text);
  }
}