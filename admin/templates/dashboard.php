<?php
/**
 * ISO 42001 Dashboard Template
 */
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'iso42001-gap-analysis'));
}

// 获取仪表盘数据
global $wpdb;
$table = ISO42K_DB_TABLE;

// 总评估数
$total_assessments = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE deleted_at IS NULL");

// 平均分数
$avg_percentage = $wpdb->get_var("SELECT AVG(percentage) FROM $table WHERE deleted_at IS NULL");
$avg_percentage = round($avg_percentage, 2);

// 按规模统计
$size_stats = $wpdb->get_results("
    SELECT company_size, COUNT(*) as count 
    FROM $table 
    WHERE deleted_at IS NULL 
    GROUP BY company_size
");

// 按成熟度统计
$maturity_stats = $wpdb->get_results("
    SELECT maturity_level, COUNT(*) as count 
    FROM $table 
    WHERE deleted_at IS NULL 
    GROUP BY maturity_level
");
?>

<div class="wrap">
    <h1><?php esc_html_e('ISO 42001 Dashboard', 'iso42001-gap-analysis'); ?></h1>

    <div class="iso42k-dashboard">
        <!-- 总评估数 -->
        <div class="iso42k-widget">
            <h3><?php esc_html_e('Total Assessments', 'iso42001-gap-analysis'); ?></h3>
            <div style="font-size: 36px; font-weight: 700; color: #0073aa;"><?php echo $total_assessments; ?></div>
            <p><?php esc_html_e('Completed assessments in the last 90 days', 'iso42001-gap-analysis'); ?></p>
        </div>

        <!-- 平均分数 -->
        <div class="iso42k-widget">
            <h3><?php esc_html_e('Average Compliance Score', 'iso42001-gap-analysis'); ?></h3>
            <div style="font-size: 36px; font-weight: 700; color: #43a047;"><?php echo $avg_percentage; ?>%</div>
            <div class="iso42k-progress-bar">
                <div class="iso42k-progress-fill" style="width: <?php echo $avg_percentage; ?>%; background: #43a047;"></div>
            </div>
        </div>

        <!-- 按规模统计 -->
        <div class="iso42k-widget">
            <h3><?php esc_html_e('Assessments by Company Size', 'iso42001-gap-analysis'); ?></h3>
            <div class="iso42k-chart-container">
                <canvas id="iso42k-size-chart"></canvas>
            </div>
        </div>

        <!-- 评估数量趋势 -->
        <div class="iso42k-widget">
            <h3><?php esc_html_e('Assessment Volume (Last 6 Months)', 'iso42001-gap-analysis'); ?></h3>
            <div class="iso42k-chart-container">
                <canvas id="iso42k-volume-chart"></canvas>
            </div>
        </div>

        <!-- 风险分布 -->
        <div class="iso42k-widget">
            <h3><?php esc_html_e('Risk Distribution', 'iso42001-gap-analysis'); ?></h3>
            <div class="iso42k-chart-container">
                <canvas id="iso42k-risk-chart"></canvas>
            </div>
        </div>

        <!-- 成熟度分布 -->
        <div class="iso42k-widget">
            <h3><?php esc_html_e('Maturity Level Distribution', 'iso42001-gap-analysis'); ?></h3>
            <div class="iso42k-chart-container">
                <canvas id="iso42k-maturity-chart"></canvas>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <a href="<?php echo admin_url('admin.php?page=iso42k-assessments'); ?>" class="button button-primary">
            <?php esc_html_e('View All Assessments', 'iso42001-gap-analysis'); ?>
        </a>
    </div>
</div>

<script>
// 规模分布图表
jQuery(document).ready(function($) {
    const sizeCtx = $('#iso42k-size-chart')[0].getContext('2d');
    new Chart(sizeCtx, {
        type: 'bar',
        data: {
            labels: ['Small', 'Medium', 'Large'],
            datasets: [{
                label: 'Number of Assessments',
                data: [
                    <?php echo $size_stats ? array_column($size_stats, 'count', 'company_size')['small'] ?? 0 : 0; ?>,
                    <?php echo $size_stats ? array_column($size_stats, 'count', 'company_size')['medium'] ?? 0 : 0; ?>,
                    <?php echo $size_stats ? array_column($size_stats, 'count', 'company_size')['large'] ?? 0 : 0; ?>
                ],
                backgroundColor: ['#0073aa', '#005073', '#00364c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // 成熟度分布图表
    const maturityCtx = $('#iso42k-maturity-chart')[0].getContext('2d');
    new Chart(maturityCtx, {
        type: 'pie',
        data: {
            labels: ['Initial', 'Managed', 'Defined', 'Quantitative', 'Optimizing'],
            datasets: [{
                data: [
                    <?php echo $maturity_stats ? array_column($maturity_stats, 'count', 'maturity_level')['initial'] ?? 0 : 0; ?>,
                    <?php echo $maturity_stats ? array_column($maturity_stats, 'count', 'maturity_level')['managed'] ?? 0 : 0; ?>,
                    <?php echo $maturity_stats ? array_column($maturity_stats, 'count', 'maturity_level')['defined'] ?? 0 : 0; ?>,
                    <?php echo $maturity_stats ? array_column($maturity_stats, 'count', 'maturity_level')['quantitative'] ?? 0 : 0; ?>,
                    <?php echo $maturity_stats ? array_column($maturity_stats, 'count', 'maturity_level')['optimizing'] ?? 0 : 0; ?>
                ],
                backgroundColor: ['#e53935', '#fb8c00', '#ffb300', '#43a047', '#1b5e20'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>