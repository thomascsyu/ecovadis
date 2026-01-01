<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'iso42k_leads';

// Check if table exists
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;

// Get table structure
$columns = [];
if ($table_exists) {
  $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}", ARRAY_A);
}

// Get row count
$row_count = 0;
if ($table_exists) {
  $row_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
}

// Get sample data
$sample_data = [];
if ($table_exists && $row_count > 0) {
  $sample_data = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 3", ARRAY_A);
}

?>
<div class="wrap">
  <h1>Database Diagnostic</h1>

  <h2>Table Information</h2>
  <table class="widefat">
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
          <span style="color:red;">✗ No - Table needs to be created</span>
          <p>
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=iso42k_create_table'), 'iso42k_create_table'); ?>" 
               class="button button-primary">
              Create Table Now
            </a>
          </p>
        <?php endif; ?>
      </td>
    </tr>
    <tr>
      <th>Total Records</th>
      <td><?php echo esc_html($row_count); ?></td>
    </tr>
  </table>

  <?php if ($table_exists && !empty($columns)): ?>
    <h2>Table Structure</h2>
    <table class="widefat striped">
      <thead>
        <tr>
          <th>Field</th>
          <th>Type</th>
          <th>Null</th>
          <th>Key</th>
          <th>Default</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($columns as $col): ?>
          <tr>
            <td><code><?php echo esc_html($col['Field']); ?></code></td>
            <td><?php echo esc_html($col['Type']); ?></td>
            <td><?php echo esc_html($col['Null']); ?></td>
            <td><?php echo esc_html($col['Key']); ?></td>
            <td><?php echo esc_html($col['Default'] ?? 'NULL'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if (!empty($sample_data)): ?>
    <h2>Sample Data (Last 3 Records)</h2>
    <table class="widefat striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Company</th>
          <th>Email</th>
          <th>Score</th>
          <th>AI Analysis</th>
          <th>Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sample_data as $row): ?>
          <tr>
            <td><?php echo esc_html($row['id']); ?></td>
            <td><?php echo esc_html($row['company']); ?></td>
            <td><?php echo esc_html($row['email']); ?></td>
            <td><?php echo esc_html($row['percent']); ?>%</td>
            <td>
              <?php if (!empty($row['ai_summary'])): ?>
                <span style="color:green;">✓ Available</span>
              <?php else: ?>
                <span style="color:red;">✗ Missing</span>
              <?php endif; ?>
            </td>
            <td><?php echo esc_html($row['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($wpdb->last_error): ?>
    <div class="notice notice-error" style="margin-top:20px;">
      <h3>Database Error Detected</h3>
      <pre><?php echo esc_html($wpdb->last_error); ?></pre>
    </div>
  <?php endif; ?>
</div>