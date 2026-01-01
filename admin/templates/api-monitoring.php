<?php
if (!defined('ABSPATH')) exit;

$metrics = get_option('iso42k_api_metrics', []);
if (!is_array($metrics)) $metrics = [];

$providers = [
  'deepseek' => 'DeepSeek',
  'qwen'     => 'Qwen',
];

function iso42k_fmt_dt($ts) {
  if (empty($ts)) return '—';
  $t = is_numeric($ts) ? (int)$ts : strtotime($ts);
  if (!$t) return '—';
  return date_i18n('Y-m-d H:i:s', $t);
}

$reset_url = wp_nonce_url(
  admin_url('admin-post.php?action=iso42k_reset_api_metrics'),
  'iso42k_reset_api_metrics'
);

?>
<div class="wrap">
  <h1>API Monitoring</h1>
  <p style="max-width: 900px;">
    This dashboard shows API usage for AI providers used by the plugin (DeepSeek/Qwen), including call count, errors, and last call time.
  </p>

  <div style="margin: 16px 0;">
    <a href="<?php echo esc_url($reset_url); ?>" class="button button-secondary">
      Reset API Metrics
    </a>
  </div>

  <table class="widefat striped" style="max-width: 1100px;">
    <thead>
      <tr>
        <th>Provider</th>
        <th>Total Calls</th>
        <th>Success</th>
        <th>Failures</th>
        <th>Last Call</th>
        <th>Last Error</th>
        <th>Avg Latency (ms)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($providers as $key => $label): 
        $p = $metrics[$key] ?? [];
        $total   = (int)($p['total'] ?? 0);
        $ok      = (int)($p['success'] ?? 0);
        $fail    = (int)($p['fail'] ?? 0);
        $last_at = iso42k_fmt_dt($p['last_call_at'] ?? null);
        $last_err = $p['last_error'] ?? '';
        $avg_ms  = (int)($p['avg_latency_ms'] ?? 0);
      ?>
      <tr>
        <td><strong><?php echo esc_html($label); ?></strong></td>
        <td><?php echo esc_html($total); ?></td>
        <td><?php echo esc_html($ok); ?></td>
        <td><?php echo esc_html($fail); ?></td>
        <td><?php echo esc_html($last_at); ?></td>
        <td>
          <?php if ($last_err): ?>
            <code style="white-space: normal;"><?php echo esc_html($last_err); ?></code>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td><?php echo esc_html($avg_ms); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 style="margin-top: 28px;">Notes</h2>
  <ul style="max-width: 1000px;">
    <li><strong>Total Calls</strong> counts AI API requests made by the plugin.</li>
    <li><strong>Failures</strong> counts HTTP errors or invalid API responses.</li>
    <li><strong>Last Error</strong> shows the most recent error message for quick debugging.</li>
  </ul>
</div>