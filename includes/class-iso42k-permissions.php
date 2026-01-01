<?php
/**
 * ISO 42001 Permissions Class（修复add_custom_capabilities致命错误）
 */
class ISO42K_Permissions {
    /**
     * 初始化权限钩子（修复self废弃警告）
     */
    public static function init() {
        // 正确绑定init钩子，使用__CLASS__代替self
        add_action('init', [__CLASS__, 'add_custom_capabilities']);
    }

    /**
     * 定义ISO 42001评估相关自定义权限（确保方法存在）
     */
    public static function add_custom_capabilities() {
        // 给管理员角色添加评估管理权限
        $admin_role = get_role('administrator');
        if ($admin_role) {
            // 管理评估数据
            $admin_role->add_cap('manage_iso42k_assessments');
            // 查看评估结果
            $admin_role->add_cap('view_iso42k_results');
            // 导出评估报告
            $admin_role->add_cap('export_iso42k_reports');
        }
        
        // 给编辑角色添加仅查看权限（可选）
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('view_iso42k_results');
        }
    }
}