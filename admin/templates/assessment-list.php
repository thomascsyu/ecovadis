<?php
/**
 * ISO 42001 Assessment List Template
 */
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'iso42001-gap-analysis'));
}

// 获取所有评估
global $wpdb;
$table = ISO42K_DB_TABLE;
$assessments = $wpdb->get_results("
    SELECT * FROM $table 
    WHERE deleted_at IS NULL 
    ORDER BY created_at DESC
");

// 解密组织名称
foreach ($assessments as &$assessment) {
    $assessment->organization_name = ISO42K_Encryption::decrypt($assessment->organization_name);
}
?>

<div class="wrap">
    <h1><?php esc_html_e('ISO 42001 Assessments', 'iso42001-gap-analysis'); ?></h1>

    <!-- 筛选栏 -->
    <div class="iso42k-filter-bar">
        <div class="iso42k-filter-group">
            <label for="iso42k-filter-size"><?php esc_html_e('Size:', 'iso42001-gap-analysis'); ?></label>
            <select id="iso42k-filter-size">
                <option value=""><?php esc_html_e('All Sizes', 'iso42001-gap-analysis'); ?></option>
                <option value="small"><?php esc_html_e('Small', 'iso42001-gap-analysis'); ?></option>
                <option value="medium"><?php esc_html_e('Medium', 'iso42001-gap-analysis'); ?></option>
                <option value="large"><?php esc_html_e('Large', 'iso42001-gap-analysis'); ?></option>
            </select>
        </div>

        <div class="iso42k-filter-group">
            <label for="iso42k-filter-maturity"><?php esc_html_e('Maturity:', 'iso42001-gap-analysis'); ?></label>
            <select id="iso42k-filter-maturity">
                <option value=""><?php esc_html_e('All Levels', 'iso42001-gap-analysis'); ?></option>
                <option value="initial"><?php esc_html_e('Initial', 'iso42001-gap-analysis'); ?></option>
                <option value="managed"><?php esc_html_e('Managed', 'iso42001-gap-analysis'); ?></option>
                <option value="defined"><?php esc_html_e('Defined', 'iso42001-gap-analysis'); ?></option>
                <option value="quantitative"><?php esc_html_e('Quantitative', 'iso42001-gap-analysis'); ?></option>
                <option value="optimizing"><?php esc_html_e('Optimizing', 'iso42001-gap-analysis'); ?></option>
            </select>
        </div>
    </div>

    <!-- 评估列表 -->
    <div class="iso42k-assessment-list">
        <table class="iso42k-assessment-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php esc_html_e('Organization', 'iso42001-gap-analysis'); ?></th>
                    <th><?php esc_html_e('Size', 'iso42001-gap-analysis'); ?></th>
                    <th><?php esc_html_e('Score', 'iso42001-gap-analysis'); ?></th>
                    <th><?php esc_html_e('Maturity', 'iso42001-gap-analysis'); ?></th>
                    <th><?php esc_html_e('Risk', 'iso42001-gap-analysis'); ?></th>
                    <th><?php esc_html_e('Date', 'iso42001-gap-analysis'); ?></th>
                    <th><?php esc_html_e('Actions', 'iso42001-gap-analysis'); ?></th>
                </tr>
            </thead>
            <tbody id="iso42k-assessment-table-body">
                <?php if (empty($assessments)) : ?>
                    <tr>
                        <td colspan="8" class="text-center"><?php esc_html_e('No assessments found', 'iso42001-gap-analysis'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($assessments as $assessment) : ?>
                        <tr>
                            <td><?php echo $assessment->id; ?></td>
                            <td><?php echo esc_html($assessment->organization_name); ?></td>
                            <td><span class="iso42k-badge iso42k-badge-<?php echo $assessment->company_size; ?>"><?php echo $assessment->company_size; ?></span></td>
                            <td><?php echo $assessment->percentage; ?>%</td>
                            <td class="iso42k-maturity-<?php echo $assessment->maturity_level; ?>"><?php echo $assessment->maturity_level; ?></td>
                            <td><?php echo $assessment->risk_rating; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($assessment->created_at)); ?></td>
                            <td><a href="#" class="iso42k-view-detail" data-id="<?php echo $assessment->id; ?>"><?php esc_html_e('View', 'iso42001-gap-analysis'); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Add to existing table header -->
<th><?php esc_html_e('Lead Status', 'iso42001-gap-analysis'); ?></th>

<!-- Add to table rows -->
<td>
    <?php 
    $days_old = (strtotime(current_time('mysql')) - strtotime($assessment->created_at)) / (60 * 60 * 24);
    if ($days_old < 3) {
        echo '<span class="status-pending">'.__('New', 'iso42001-gap-analysis').'</span>';
    } else {
        echo '<span class="status-publish">'.__('Processed', 'iso42001-gap-analysis').'</span>';
    }
    ?>
</td>

<!-- Update actions column -->
<td class="iso42k-actions">
    <a href="?page=iso42k-assessments&action=view&id=<?php echo $assessment->id; ?>" class="button button-small">
        <?php esc_html_e('View', 'iso42001-gap-analysis'); ?>
    </a>
    <button class="button button-small button-danger iso42k-delete-lead" data-id="<?php echo $assessment->id; ?>">
        <?php esc_html_e('Delete', 'iso42001-gap-analysis'); ?>
    </button>
</td>

    <!-- 详情容器 -->
    <div id="iso42k-assessment-detail-container" style="margin-top: 30px;"></div>
</div>