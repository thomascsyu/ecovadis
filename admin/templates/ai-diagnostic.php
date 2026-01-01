<?php
if (!defined('ABSPATH')) exit;

$settings = (array) get_option('iso42k_ai_settings', []);
$provider = $settings['provider'] ?? '';
$has_deepseek_key = !empty($settings['deepseek_api_key']);
$has_qwen_key = !empty($settings['qwen_api_key']);

?>
<div class="wrap">
  <h1>AI Configuration Diagnostic</h1>
  
  <table class="widefat">
    <tr>
      <th>AI Provider Selected</th>
      <td>
        <?php if (empty($provider)): ?>
          <span style="color:red;">✗ No provider selected</span>
        <?php else: ?>
          <span style="color:green;">✓ <?php echo esc_html(ucfirst($provider)); ?></span>
        <?php endif; ?>
      </td>
    </tr>
    <tr>
      <th>DeepSeek API Key</th>
      <td>
        <?php if ($has_deepseek_key): ?>
          <span style="color:green;">✓ Configured</span>
        <?php else: ?>
          <span style="color:red;">✗ Not configured</span>
        <?php endif; ?>
      </td>
    </tr>
    <tr>
      <th>Qwen API Key</th>
      <td>
        <?php if ($has_qwen_key): ?>
          <span style="color:green;">✓ Configured</span>
        <?php else: ?>
          <span style="color:red;">✗ Not configured</span>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <?php if (empty($provider) || ($provider === 'deepseek' && !$has_deepseek_key) || ($provider === 'qwen' && !$has_qwen_key)): ?>
    <div class="notice notice-error" style="margin-top:20px;">
      <p><strong>Action Required:</strong> Configure your AI settings to enable automated gap analysis.</p>
      <p>
        <a href="<?php echo admin_url('admin.php?page=iso42k-settings&tab=ai'); ?>" class="button button-primary">
          Configure AI Settings
        </a>
      </p>
    </div>
  <?php endif; ?>
</div>