<?php
if (!defined('ABSPATH')) exit;

class ISO42K_Shortcode {

  public static function init() {
    // Single shortcode only (requested)
    add_shortcode('iso42k_assessment', [__CLASS__, 'render']);
  }

  
  public static function render() {

    $root = trailingslashit(dirname(__FILE__, 2));
    $plugin_file = $root . 'iso42001-gap-analysis.php';
    $base_url = plugin_dir_url($plugin_file);

    $css_path = $root . 'public/css/iso42k-public.css';
    $js_path  = $root . 'public/js/iso42k-flow.js';

    $css_ver = file_exists($css_path) ? (string) filemtime($css_path) : '1.0.0';
    $js_ver  = file_exists($js_path)  ? (string) filemtime($js_path)  : '1.0.0';

    // Enqueue assets (shortcode-safe)
    wp_enqueue_style(
      'iso42k-public',
      $base_url . 'public/css/iso42k-public.css',
      [],
      $css_ver
    );

    wp_enqueue_script(
      'iso42k-flow',
      $base_url . 'public/js/iso42k-flow.js',
      ['jquery'],
      $js_ver,
      true // Load in footer
    );

    // Create nonce once to be used in both locations
    $nonce = wp_create_nonce('iso42k_assessment_nonce');
    
    // 1. Localize script (Standard method - keeps dependencies happy)
    wp_localize_script('iso42k-flow', 'ISO42K', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => $nonce,
    ]);

    ob_start();
    ?>
    <script type="text/javascript">
      window.ISO42K = window.ISO42K || <?php echo json_encode([
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce'    => $nonce
      ]); ?>;
    </script>

    <!-- ISO42K Nonce Meta Tag -->
    <meta name="iso42k-nonce" content="<?php echo esc_attr($nonce); ?>" />
    <div id="iso42k-app">
      <?php
      // Render all steps so the front-end flow can switch screens without
      // re-rendering the shortcode.
      $templates = [
        'step-intro.php',
        'step-questions.php',
        'step-contact.php',
        'step-results.php',
      ];

      $missing = [];
      foreach ($templates as $filename) {
        $tpl_path = $root . 'public/templates/' . $filename;
        if (file_exists($tpl_path)) {
          include $tpl_path;
        } else {
          $missing[] = $filename;
        }
      }

      if (!empty($missing)) {
        echo '<div class="iso42k-error" style="max-width:780px;margin:16px auto;">Assessment template missing: ' . esc_html(implode(', ', $missing)) . '</div>';
      }
      ?>
    </div>
    <?php
    return ob_get_clean();
  }
}