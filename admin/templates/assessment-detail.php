<?php
/**
 * ISO 42001 Assessment Detail Template
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'iso42001-gap-analysis'));
}

$assessment_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
if (!$assessment_id) {
    wp_die(__('Invalid assessment ID', 'iso42001-gap-analysis'));
}

// 定义数据库表名（如果未定义）
if (!defined('ISO42K_DB_TABLE')) {
    global $wpdb;
    define('ISO42K_DB_TABLE', $wpdb->prefix . 'iso42k_assessments');
}

// 获取评估数据
global $wpdb;
$table = ISO42K_DB_TABLE;
$assessment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND deleted_at IS NULL", $assessment_id));

if (!$assessment) {
    wp_die(__('Assessment not found or deleted', 'iso42001-gap-analysis'));
}

// 引入加密类
require_once plugin_dir_path(__FILE__) . '../includes/class-iso42k-encryption.php';

// 解密所有敏感数据
$assessment->organization_name = ISO42K_Encryption::decrypt($assessment->organization_name);
$assessment->contact_name = ISO42K_Encryption::decrypt($assessment->contact_name);
$assessment->contact_email = ISO42K_Encryption::decrypt($assessment->contact_email);
$assessment->contact_phone = ISO42K_Encryption::decrypt($assessment->contact_phone);

// 解密评估数据
$encrypted_data = ISO42K_Encryption::decrypt($assessment->assessment_data);
$assessment->assessment_data = json_decode($encrypted_data ?: '{}', true);

// 获取AI分析（如果存在）
$ai_analysis = '';
if (isset($assessment->ai_analysis) && !empty($assessment->ai_analysis)) {
    $ai_analysis = ISO42K_Encryption::decrypt($assessment->ai_analysis);
}

// 格式化日期
$created_at = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($assessment->created_at));
$retention_date = date_i18n(get_option('date_format'), strtotime($assessment->data_retention_date));
?>

<div class="wrap">
    <h1><?php esc_html_e('Assessment Detail', 'iso42001-gap-analysis'); ?> | <?php echo esc_html($assessment->organization_name); ?></h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=iso42k-leads')); ?>" class="button button-secondary">
        <?php esc_html_e('Back to List', 'iso42001-gap-analysis'); ?>
    </a>

    <div class="iso42k-assessment-detail">
        <!-- 基本信息 -->
        <div class="iso42k-detail-header">
            <h2><?php echo esc_html($assessment->organization_name); ?></h2>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 10px 0;">
                <div><strong><?php esc_html_e('Assessment ID:', 'iso42001-gap-analysis'); ?></strong> <?php echo esc_html($assessment->assessment_uid); ?></div>
                <div><strong><?php esc_html_e('Company Size:', 'iso42001-gap-analysis'); ?></strong> <span class="iso42k-badge iso42k-badge-<?php echo esc_attr($assessment->company_size); ?>"><?php echo esc_html(ucfirst($assessment->company_size)); ?></span> (<?php echo esc_html($assessment->employee_count); ?> employees)</div>
                <div><strong><?php esc_html_e('Submitted:', 'iso42001-gap-analysis'); ?></strong> <?php echo esc_html($created_at); ?></div>
                <div><strong><?php esc_html_e('Data Retention Until:', 'iso42001-gap-analysis'); ?></strong> <?php echo esc_html($retention_date); ?></div>
            </div>

            <div style="margin: 15px 0;">
                <strong><?php esc_html_e('Contact Information:', 'iso42001-gap-analysis'); ?></strong><br>
                <?php echo esc_html($assessment->contact_name); ?> | 
                <?php echo esc_html($assessment->contact_email); ?> | 
                <?php echo esc_html($assessment->contact_phone); ?>
            </div>
        </div>

        <!-- 评估结果 -->
        <div class="iso42k-detail-section">
            <h3><?php esc_html_e('Assessment Results', 'iso42001-gap-analysis'); ?></h3>
            <table style="width: 100%; max-width: 600px; border-collapse: collapse;">
                <tr>
                    <td style="width: 30%; font-weight: 600; padding: 8px 0;"><?php esc_html_e('Compliance Score:', 'iso42001-gap-analysis'); ?></td>
                    <td style="font-size: 24px; font-weight: 700; color: #43a047; padding: 8px 0;"><?php echo esc_html($assessment->percentage); ?>%</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 8px 0;"><?php esc_html_e('Maturity Level:', 'iso42001-gap-analysis'); ?></td>
                    <td class="iso42k-maturity-<?php echo esc_attr($assessment->maturity_level); ?>" style="padding: 8px 0;">
                        <strong><?php echo esc_html(ucfirst($assessment->maturity_level)); ?></strong>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 8px 0;"><?php esc_html_e('Risk Rating:', 'iso42001-gap-analysis'); ?></td>
                    <td style="padding: 8px 0;"><?php echo esc_html(ucfirst($assessment->risk_rating)); ?></td>
                </tr>
            </table>

            <div style="margin-top: 15px;">
                <div style="font-weight: 600; margin-bottom: 5px;"><?php esc_html_e('Compliance Progress:', 'iso42001-gap-analysis'); ?></div>
                <div class="iso42k-progress-bar" style="height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                    <div class="iso42k-progress-fill iso42k-progress-<?php echo esc_attr($assessment->maturity_level); ?>" 
                         style="width: <?php echo esc_attr($assessment->percentage); ?>%; height: 100%; background: #43a047; transition: width 0.3s ease;"></div>
                </div>
            </div>
        </div>

        <!-- AI分析报告 -->
        <?php if (!empty($ai_analysis)): ?>
        <div class="iso42k-detail-section">
            <h3><?php esc_html_e('AI-Powered Gap Analysis', 'iso42001-gap-analysis'); ?></h3>
            <div class="iso42k-ai-analysis" style="background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa;">
                <?php echo nl2br(esc_html($ai_analysis)); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 问题回答详情 -->
        <?php if (isset($assessment->assessment_data['answers']) && !empty($assessment->assessment_data['answers'])): ?>
        <div class="iso42k-detail-section">
            <h3><?php esc_html_e('Question Responses', 'iso42001-gap-analysis'); ?></h3>
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                <?php foreach ($assessment->assessment_data['answers'] as $q_id => $answer): ?>
                    <div class="iso42k-question-item" style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                        <div class="iso42k-question-label" style="font-weight: 600; color: #333;">
                            <?php esc_html_e('Question', 'iso42001-gap-analysis'); ?> #<?php echo esc_html($q_id); ?>
                        </div>
                        <div class="iso42k-answer-value" style="color: #666; margin-top: 5px;"><?php echo esc_html($answer); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="iso42k-detail-section">
            <h3><?php esc_html_e('Question Responses', 'iso42001-gap-analysis'); ?></h3>
            <p><?php esc_html_e('No question responses found', 'iso42001-gap-analysis'); ?></p>
        </div>
        <?php endif; ?>

        <!-- 导出按钮 -->
        <div class="iso42k-detail-section" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <button class="button button-primary" id="iso42k-export-pdf">
                <?php esc_html_e('Export as PDF', 'iso42001-gap-analysis'); ?>
            </button>
            <button class="button button-secondary" id="iso42k-resend-email">
                <?php esc_html_e('Resend Report Email', 'iso42001-gap-analysis'); ?>
            </button>
            <button class="button button-delete" id="iso42k-delete-assessment" style="background: #dc3232; color: white;">
                <?php esc_html_e('Delete Assessment', 'iso42001-gap-analysis'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // 重新发送邮件
    $('#iso42k-resend-email').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to resend the report email?', 'iso42001-gap-analysis')); ?>')) {
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: {
                    action: 'iso42k_resend_email',
                    nonce: '<?php echo wp_create_nonce("iso42k_admin_nonce"); ?>',
                    assessment_id: <?php echo $assessment_id; ?>
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php echo esc_js(__('Email sent successfully!', 'iso42001-gap-analysis')); ?>');
                    } else {
                        alert('<?php echo esc_js(__('Failed to send email: ', 'iso42001-gap-analysis')); ?>' + (response.data || '<?php echo esc_js(__('Unknown error', 'iso42001-gap-analysis')); ?>'));
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Network error. Please try again.', 'iso42001-gap-analysis')); ?>');
                }
            });
        }
    });

    // 删除评估
    $('#iso42k-delete-assessment').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this assessment? This action cannot be undone.', 'iso42001-gap-analysis')); ?>')) {
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: {
                    action: 'iso42k_delete_assessment',
                    nonce: '<?php echo wp_create_nonce("iso42k_admin_nonce"); ?>',
                    assessment_id: <?php echo $assessment_id; ?>
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php echo esc_js(__('Assessment deleted successfully!', 'iso42001-gap-analysis')); ?>');
                        window.location.href = '<?php echo admin_url("admin.php?page=iso42k-leads"); ?>';
                    } else {
                        alert('<?php echo esc_js(__('Failed to delete assessment: ', 'iso42001-gap-analysis')); ?>' + (response.data || '<?php echo esc_js(__('Unknown error', 'iso42001-gap-analysis')); ?>'));
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Network error. Please try again.', 'iso42001-gap-analysis')); ?>');
                }
            });
        }
    });

    // 导出PDF
    $('#iso42k-export-pdf').on('click', function() {
        window.open('<?php echo admin_url("admin-ajax.php?action=iso42k_export_pdf&id=" . $assessment_id . "&nonce=" . wp_create_nonce("iso42k_export_nonce")); ?>', '_blank');
    });
});
</script>

<style>
.iso42k-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.iso42k-badge-small { background: #e3f2fd; color: #1976d2; }
.iso42k-badge-medium { background: #f3e5f5; color: #7b1fa2; }
.iso42k-badge-large { background: #e8f5e8; color: #2e7d32; }

.iso42k-detail-section {
    margin: 20px 0;
    padding: 20px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.iso42k-detail-section h3 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.button-delete:hover {
    background: #a00 !important;
}
</style>