<?php
/**
 * ISO 42001 Auto-save Class（修复self废弃警告+未闭合括号）
 */
if (!defined('ABSPATH')) exit;

class ISO42K_AutoSave {
    // 自动保存间隔（秒）
    const ISO42K_AUTOSAVE_INTERVAL = 120;

    /**
     * 初始化自动保存（修复self废弃警告）
     */
    public static function init() {
        // 使用__CLASS__代替self，兼容PHP 8.2+
        add_action('wp_ajax_iso42k_autosave_draft', [__CLASS__, 'iso42k_handle_autosave']);
        add_action('wp_ajax_nopriv_iso42k_autosave_draft', [__CLASS__, 'iso42k_handle_autosave']);
        add_action('wp_footer', [__CLASS__, 'iso42k_enqueue_autosave_script']);
    }

    /**
     * 入队自动保存脚本
     */
    public static function iso42k_enqueue_autosave_script() {
        // 仅在评估页面加载脚本
        if (!is_page('iso42001-assessment') && !get_query_var('iso42k_step')) {
            return;
        }

        // 获取版本号
        $plugin_data = get_file_data(__FILE__, ['Version' => 'Version']);
        $version = $plugin_data['Version'] ?? '1.0.0';

        wp_enqueue_script(
            'iso42k-autosave-script',
            plugins_url('/public/js/iso42k-autosave.js', __FILE__),
            ['jquery'],
            $version,
            true
        );

        // 本地化脚本（修复未闭合括号+添加esc_js转义）
        wp_localize_script(
            'iso42k-autosave-script',
            'iso42kAutosave',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('iso42k_autosave_nonce'),
                'interval' => self::ISO42K_AUTOSAVE_INTERVAL,
                'messages' => [
                    'saving' => esc_js(__('Saving your progress...', 'iso42001-gap-analysis')),
                    'saved' => esc_js(__('Saved!', 'iso42001-gap-analysis')),
                    'error' => esc_js(__('Failed to save. Please try again.', 'iso42001-gap-analysis'))
                ]
            ]
        );
    }

    /**
     * 处理自动保存AJAX请求
     */
    public static function iso42k_handle_autosave() {
        // 验证nonce（添加空值保护）
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'iso42k_autosave_nonce')) {
            wp_send_json_error(__('Security verification failed', 'iso42001-gap-analysis'));
        }

        // 获取并验证数据
        $iso42k_assessment_uid = sanitize_text_field($_POST['assessment_uid'] ?? '');
        $iso42k_draft_data = isset($_POST['draft_data']) ? json_decode(stripslashes($_POST['draft_data']), true) : [];

        if (empty($iso42k_assessment_uid) || empty($iso42k_draft_data)) {
            wp_send_json_error(__('Invalid save data', 'iso42001-gap-analysis'));
        }

        // 保存到数据库
        global $wpdb;
        $iso42k_table = $wpdb->prefix . 'iso42k_drafts';

        // 创建表如果不存在
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $iso42k_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            assessment_uid varchar(36) NOT NULL,
            draft_data longtext NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (assessment_uid)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $iso42k_saved = $wpdb->replace(
            $iso42k_table,
            [
                'assessment_uid' => $iso42k_assessment_uid,
                'draft_data' => wp_json_encode($iso42k_draft_data),
                'updated_at' => current_time('mysql')
            ],
            [
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($iso42k_saved !== false) {
            wp_send_json_success(__('Draft saved successfully', 'iso42001-gap-analysis'));
        } else {
            wp_send_json_error(__('Failed to save draft', 'iso42001-gap-analysis'));
        }
    }

    /**
     * 获取草稿数据
     */
    public static function get_draft($assessment_uid) {
        global $wpdb;
        $iso42k_table = $wpdb->prefix . 'iso42k_drafts';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT draft_data FROM $iso42k_table WHERE assessment_uid = %s",
            $assessment_uid
        ));
        
        if ($result) {
            return json_decode($result, true);
        }
        
        return null;
    }

    /**
     * 删除草稿数据
     */
    public static function delete_draft($assessment_uid) {
        global $wpdb;
        $iso42k_table = $wpdb->prefix . 'iso42k_drafts';
        
        return $wpdb->delete(
            $iso42k_table,
            ['assessment_uid' => $assessment_uid],
            ['%s']
        );
    }
}