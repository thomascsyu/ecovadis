<?php
if (!defined('ABSPATH')) exit;

class ISO42K_Admin_Leads {

  public static function render() {
    if (!current_user_can('manage_options')) {
      wp_die('Insufficient permissions.');
    }

    // Handle view action
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
      self::view_lead_details(intval($_GET['id']));
      return;
    }

    // CSV Export
    if (isset($_GET['iso42k_export']) && $_GET['iso42k_export'] === 'csv') {
      self::export_csv();
      return;
    }

    $leads = ISO42K_Leads::get_all();

    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">Leads</h1>
      
      <a href="<?php echo esc_url(admin_url('admin.php?page=iso42k-leads&iso42k_export=csv')); ?>" 
         class="page-title-action">Export CSV</a>
      
      <hr class="wp-header-end">

      <?php if (isset($_GET['retry_ai']) && $_GET['retry_ai'] === 'success'): ?>
        <div class="notice notice-success is-dismissible">
          <p>AI analysis regenerated successfully!</p>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['retry_ai']) && $_GET['retry_ai'] === 'failed'): ?>
        <div class="notice notice-error is-dismissible">
          <p>Failed to generate AI analysis. Please check your AI settings.</p>
        </div>
      <?php endif; ?>

      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th style="width:130px;">Date</th>
            <th>Company</th>
            <th style="width:60px;">Staff</th>
            <th>Contact</th>
            <th>Email</th>
            <th style="width:120px;">Phone</th>
            <th style="width:80px;">Score</th>
            <th style="width:110px;">Maturity</th>
            <th style="width:100px;">AI Analysis</th>
            <th style="width:100px;">PDF Download</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($leads)): ?>
          <tr><td colspan="11" style="text-align:center;padding:40px;"><em>No leads yet.</em></td></tr>
        <?php else: ?>
          <?php foreach ($leads as $l): ?>
            <?php
            $lead_id = intval($l['id'] ?? 0);
            $has_ai = !empty($l['ai_summary']);
            
            $view_url = add_query_arg([
              'page' => 'iso42k-leads',
              'action' => 'view',
              'id' => $lead_id
            ], admin_url('admin.php'));
            
            // Get score color
            $score_color = self::get_score_color(intval($l['percent'] ?? 0));
            $maturity_color = self::get_maturity_color($l['maturity'] ?? 'Initial');
            ?>
            <tr>
              <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($l['created_at']))); ?></td>
              <td><strong><?php echo esc_html($l['company'] ?? ''); ?></strong></td>
              <td><?php echo self::get_staff_range_display($l['staff'] ?? 0); ?></td>
              <td><?php echo esc_html($l['name'] ?? ''); ?></td>
              <td>
                <a href="mailto:<?php echo esc_attr($l['email'] ?? ''); ?>">
                  <?php echo esc_html($l['email'] ?? ''); ?>
                </a>
              </td>
              <td><?php echo esc_html($l['phone'] ?? ''); ?></td>
              <td>
                <span class="iso42k-score-badge" 
                      style="display:inline-block;padding:6px 12px;border-radius:12px;color:#fff;font-weight:700;font-size:13px;background:<?php echo esc_attr($score_color); ?>;">
                  <?php echo esc_html($l['percent'] ?? 0); ?>%
                </span>
              </td>
              <td>
                <strong style="color:<?php echo esc_attr($maturity_color); ?>;">
                  <?php echo esc_html($l['maturity'] ?? 'Initial'); ?>
                </strong>
              </td>
              <td style="text-align:center;">
                <?php if ($has_ai): ?>
                  <span style="color:#10B981;font-weight:700;font-size:16px;" title="AI Analysis Generated">✓</span>
                <?php else: ?>
                  <span style="color:#DC2626;font-weight:700;font-size:18px;" title="AI Analysis Failed">✗</span>
                  <br>
                  <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=iso42k_retry_ai&lead_id=' . $lead_id), 'iso42k_retry_ai_' . $lead_id)); ?>" 
                     class="button button-small" 
                     style="margin-top:4px;font-size:11px;">
                    Retry
                  </a>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php 
                $download_count = get_option('iso42k_pdf_downloads_' . $lead_id, 0);
                if ($download_count > 0):
                ?>
                  <span style="color:#10B981;font-weight:600;" title="Downloaded <?php echo $download_count; ?> time(s)">
                    ✓ <?php echo $download_count; ?>x
                  </span>
                <?php else: ?>
                  <span style="color:#94a3b8;" title="Not downloaded yet">—</span>
                <?php endif; ?>
              </td>
              <td class="iso42k-actions">
                <a href="<?php echo esc_url($view_url); ?>" 
                   class="button button-small button-primary" 
                   style="margin-right:4px;padding:0 8px;height:26px;line-height:24px;"
                   title="View Details">
                  <span class="dashicons dashicons-visibility" style="font-size:16px;width:16px;height:16px;margin-top:4px;"></span>
                </a>
                <button type="button" 
                        class="button button-small iso42k-delete-lead" 
                        data-id="<?php echo esc_attr($lead_id); ?>"
                        data-name="<?php echo esc_attr($l['company'] ?? ''); ?>"
                        style="background:#DC2626;border-color:#DC2626;color:#fff;padding:0 8px;height:26px;line-height:24px;"
                        title="Delete Lead">
                  <span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;margin-top:4px;"></span>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>

      <div class="tablenav bottom">
        <div class="tablenav-pages">
          <span class="displaying-num"><?php echo count($leads); ?> items</span>
        </div>
      </div>
    </div>

    <style>
      .iso42k-actions {
        white-space: nowrap;
      }
      .iso42k-delete-lead:hover {
        background: #B91C1C !important;
        border-color: #B91C1C !important;
        color: #fff !important;
      }
    </style>

    <script>
    jQuery(document).ready(function($) {
      $('.iso42k-delete-lead').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        if (!confirm('Are you sure you want to delete the lead from "' + name + '"?\n\nThis action cannot be undone.')) {
          return;
        }

        const $row = $(this).closest('tr');

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'iso42k_delete_lead',
            nonce: '<?php echo wp_create_nonce("iso42k_delete_lead"); ?>',
            lead_id: id
          },
          beforeSend: function() {
            $row.css('opacity', '0.5');
          },
          success: function(response) {
            if (response.success) {
              $row.fadeOut(300, function() {
                $(this).remove();
                if ($('tbody tr').length === 0) {
                  location.reload();
                }
              });
            } else {
              alert('Error: ' + (response.data || 'Unknown error'));
              $row.css('opacity', '1');
            }
          },
          error: function() {
            alert('Network error. Please try again.');
            $row.css('opacity', '1');
          }
        });
      });
    });
    </script>
    <?php
  }

  /** 
   * Convert staff number to range display
   */
  private static function get_staff_range_display(int $staff_number): string {
    if ($staff_number >= 1 && $staff_number <= 10) {
      return '1-10';
    } elseif ($staff_number >= 11 && $staff_number <= 20) {
      return '11-20';
    } else {
      return '21+';
    }
  }

  /**
   * Get color for score badge
   */
  private static function get_score_color(int $score): string {
    if ($score < 40) return '#DC2626';
    if ($score < 70) return '#F59E0B';
    return '#10B981';
  }

  /**
   * Get color for maturity level
   */
  private static function get_maturity_color(string $maturity): string {
    switch ($maturity) {
      case 'Initial': 
        return '#DC2626';
      case 'Managed': 
        return '#F59E0B';
      case 'Established': 
        return '#10B981';
      case 'Optimised': 
        return '#3B82F6';
      default: 
        return '#6B7280';
    }
  }

  /**
   * View lead details page
   */
  private static function view_lead_details(int $lead_id) {
    $lead = ISO42K_Leads::get_by_id($lead_id);
    if (!$lead) {
      echo '<div class="wrap"><h1>Lead Details</h1><p>Lead not found.</p></div>';
      return;
    }

    $back_url = admin_url('admin.php?page=iso42k-leads');
    ?>
    <div class="wrap">
      <h1>
        Lead Details: <?php echo esc_html($lead['company'] ?? 'N/A'); ?>
        <a href="<?php echo esc_url($back_url); ?>" class="page-title-action">← Back to Leads</a>
      </h1>

      <div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;margin-top:20px;">
        <table class="widefat">
          <tr>
            <th style="width:180px;">Submitted</th>
            <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($lead['created_at']))); ?></td>
          </tr>
          <tr>
            <th>Company</th>
            <td><strong><?php echo esc_html($lead['company'] ?? ''); ?></strong></td>
          </tr>
          <tr>
            <th>Staff Size</th>
            <td><?php echo self::get_staff_range_display($lead['staff'] ?? 0); ?></td>
          </tr>
          <tr>
            <th>Contact Name</th>
            <td><?php echo esc_html($lead['name'] ?? ''); ?></td>
          </tr>
          <tr>
            <th>Email</th>
            <td>
              <a href="mailto:<?php echo esc_attr($lead['email'] ?? ''); ?>">
                <?php echo esc_html($lead['email'] ?? ''); ?>
              </a>
            </td>
          </tr>
          <tr>
            <th>Phone</th>
            <td><?php echo esc_html($lead['phone'] ?? ''); ?></td>
          </tr>
          <tr>
            <th>Score</th>
            <td>
              <span style="font-size:28px;font-weight:800;color:<?php echo esc_attr(self::get_score_color(intval($lead['percent'] ?? 0))); ?>;">
                <?php echo esc_html($lead['percent'] ?? 0); ?>%
              </span>
            </td>
          </tr>
          <tr>
            <th>Maturity Level</th>
            <td>
              <strong style="font-size:18px;color:<?php echo esc_attr(self::get_maturity_color($lead['maturity'] ?? 'Initial')); ?>;">
                <?php echo esc_html($lead['maturity'] ?? 'Initial'); ?>
              </strong>
            </td>
          </tr>
        </table>

        <?php if (!empty($lead['ai_summary'])): ?>
        <div style="margin-top:30px;padding:20px;background:#f0f9ff;border-left:4px solid #3b82f6;border-radius:8px;">
          <h2 style="margin:0 0 15px;">AI-Powered Gap Analysis</h2>
          <div style="white-space:pre-wrap;line-height:1.6;"><?php echo esc_html($lead['ai_summary']); ?></div>
        </div>
        <?php else: ?>
        <div style="margin-top:30px;padding:20px;background:#fee;border-left:4px solid #DC2626;border-radius:8px;">
          <p style="margin:0;color:#991b1b;"><strong>⚠️ AI Analysis Not Available</strong></p>
          <p style="margin:10px 0 0;">The AI analysis failed to generate. Check AI settings and API connectivity.</p>
          <p style="margin:10px 0 0;">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=iso42k_retry_ai&lead_id=' . $lead_id), 'iso42k_retry_ai_' . $lead_id)); ?>" 
               class="button button-primary">
              Retry AI Analysis
            </a>
          </p>
        </div>
        <?php endif; ?>

        <?php 
$answers = is_string($lead['answers']) ? json_decode($lead['answers'], true) : $lead['answers'];
if (!empty($answers) && is_array($answers)): 
?>
        <div style="margin-top:30px;">
          <h2>Assessment Answers (<?php echo count($lead['answers']); ?> questions)</h2>
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width:50px;">#</th>
                <th>Answer</th>
                <th style="width:150px;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lead['answers'] as $index => $answer): ?>
                <tr>
                  <td><?php echo intval($index) + 1; ?></td>
                  <td><strong><?php echo esc_html($answer); ?></strong></td>
                  <td>
                    <?php
                    $answer_upper = strtoupper(trim($answer));
                    switch ($answer_upper) {
                      case 'A':
                        echo '<span style="color:#10B981;font-weight:600;">✓ Fully Implemented</span>';
                        break;
                      case 'B':
                        echo '<span style="color:#F59E0B;font-weight:600;">◐ Partial</span>';
                        break;
                      case 'C':
                        echo '<span style="color:#DC2626;font-weight:600;">✗ Not Implemented</span>';
                        break;
                      default:
                        echo '<span style="color:#6B7280;">Unknown</span>';
                    }
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <div style="margin-top:30px;padding-top:20px;border-top:2px solid #eee;">
          <a href="mailto:<?php echo esc_attr($lead['email'] ?? ''); ?>" class="button button-primary">
            Send Email
          </a>
          <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=iso42k_delete_lead_single&lead_id=' . $lead_id), 'iso42k_delete_lead_' . $lead_id)); ?>" 
             class="button button-delete"
             onclick="return confirm('Are you sure you want to delete this lead? This action cannot be undone.');"
             style="background:#DC2626;border-color:#DC2626;color:#fff;">
            Delete Lead
          </a>
        </div>
      </div>
    </div>
    <?php
  }

  /**
   * Export leads to CSV
   */
  private static function export_csv() {
    if (!current_user_can('manage_options')) {
      wp_die('Insufficient permissions.');
    }

    $leads = ISO42K_Leads::get_all();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=iso42k-leads-' . gmdate('Ymd-His') . '.csv');

    $out = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($out, [
      'Date', 'Company', 'Staff', 'Contact', 'Email', 'Phone', 
      'Score', 'Maturity', 'AI Available'
    ]);

    // CSV rows
    foreach ($leads as $l) {
      fputcsv($out, [
        $l['created_at'] ?? '',
        $l['company'] ?? '',
        self::get_staff_range_display($l['staff'] ?? 0),
        $l['name'] ?? '',
        $l['email'] ?? '',
        $l['phone'] ?? '',
        ($l['percent'] ?? 0) . '%',
        $l['maturity'] ?? '',
        !empty($l['ai_summary']) ? 'Yes' : 'No'
      ]);
    }
    
    fclose($out);
    exit;
  }
}