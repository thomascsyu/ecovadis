<?php
/**
 * ISO 42001 Database Handler
 */
class ISO42K_DB {
    /**
     * 初始化数据库类
     */
    public static function init() {
        // 注册数据清理定时任务
        add_action('iso42k_purge_expired_data', ['self', 'purge_expired_data']);
        if (!wp_next_scheduled('iso42k_purge_expired_data')) {
            wp_schedule_event(time(), 'daily', 'iso42k_purge_expired_data');
        }
    }

    /**
     * 创建数据库表
     */
    public static function create_table() {
        global $wpdb;
        $table_name = ISO42K_DB_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            assessment_uid char(36) NOT NULL UNIQUE,
            organization_name longtext NOT NULL,
            employee_count smallint(5) unsigned NOT NULL,
            company_size enum('small','medium','large') NOT NULL,
            contact_name longtext NOT NULL,
            contact_email longtext NOT NULL,
            contact_phone longtext NOT NULL,
            assessment_data longtext NOT NULL,
            percentage decimal(5,2) NOT NULL,
            maturity_level varchar(30) NOT NULL,
            risk_rating varchar(20) NOT NULL,
            data_retention_date date NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at datetime NULL,
            PRIMARY KEY (id),
            KEY idx_retention_date (data_retention_date),
            KEY idx_deleted_at (deleted_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * 插入评估数据
     * @param array $data 评估数据（已加密）
     * @return int|false 插入ID
     */
    public static function insert_assessment($data) {
        global $wpdb;
        $data['assessment_uid'] = wp_generate_uuid4();
        $data['created_at'] = current_time('mysql');
        
        // 计算90天留存日期
        $retention_date = date('Y-m-d', strtotime('+90 days', strtotime($data['created_at'])));
        $data['data_retention_date'] = $retention_date;

        return $wpdb->insert(ISO42K_DB_TABLE, $data);
    }

    /**
     * 清理过期数据（90天留存+30天软删除）
     */
    public static function purge_expired_data() {
        global $wpdb;
        $table_name = ISO42K_DB_TABLE;

        // 软删除：超过留存日期的记录
        $wpdb->update(
            $table_name,
            ['deleted_at' => current_time('mysql')],
            [
                'data_retention_date <' => current_time('mysql'),
                'deleted_at' => NULL
            ],
            ['%s'],
            ['%s', '%s']
        );

        // 硬删除：软删除超过30天的记录
        $wpdb->delete(
            $table_name,
            ['deleted_at <' => date('Y-m-d H:i:s', strtotime('-30 days'))],
            ['%s']
        );
    }

    /**
     * 卸载插件：删除表（可选，根据需求调整）
     */
    public static function uninstall() {
        global $wpdb;
        $table_name = ISO42K_DB_TABLE;
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // 清除定时任务
        $timestamp = wp_next_scheduled('iso42k_purge_expired_data');
        wp_unschedule_event($timestamp, 'iso42k_purge_expired_data');
    }
}