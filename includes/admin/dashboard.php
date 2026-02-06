<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get stats
$total_cases = wp_count_posts('ekwa_bag_case')->publish;
$total_categories = wp_count_terms('ekwa_bag_category', array('hide_empty' => false));
if (is_wp_error($total_categories)) {
    $total_categories = 0;
}

// Get total image sets
$total_views = 0;
$cases = get_posts(array(
    'post_type' => 'ekwa_bag_case',
    'posts_per_page' => -1,
    'post_status' => 'publish',
));
foreach ($cases as $case) {
    $sets = get_post_meta($case->ID, '_ekwa_bag_image_sets', true);
    if (is_array($sets)) {
        $total_views += count($sets);
    }
}
?>

<div class="ekwa-bag-dashboard">
    <div class="ekwa-bag-dashboard-header">
        <h1><?php esc_html_e('Before After Gallery', 'ekwa-before-after-gallery'); ?></h1>
        <p><?php esc_html_e('Create beautiful before and after galleries for your dental or medical practice.', 'ekwa-before-after-gallery'); ?></p>
    </div>

    <div class="ekwa-bag-dashboard-cards">
        <div class="ekwa-bag-dashboard-card">
            <h3><?php esc_html_e('Gallery Cases', 'ekwa-before-after-gallery'); ?></h3>
            <div class="ekwa-bag-stat"><?php echo esc_html($total_cases); ?></div>
            <div class="ekwa-bag-stat-label"><?php esc_html_e('Published Cases', 'ekwa-before-after-gallery'); ?></div>
        </div>

        <div class="ekwa-bag-dashboard-card">
            <h3><?php esc_html_e('Categories', 'ekwa-before-after-gallery'); ?></h3>
            <div class="ekwa-bag-stat"><?php echo esc_html($total_categories); ?></div>
            <div class="ekwa-bag-stat-label"><?php esc_html_e('Treatment Categories', 'ekwa-before-after-gallery'); ?></div>
        </div>

        <div class="ekwa-bag-dashboard-card">
            <h3><?php esc_html_e('Image Views', 'ekwa-before-after-gallery'); ?></h3>
            <div class="ekwa-bag-stat"><?php echo esc_html($total_views); ?></div>
            <div class="ekwa-bag-stat-label"><?php esc_html_e('Total Before/After Sets', 'ekwa-before-after-gallery'); ?></div>
        </div>

        <div class="ekwa-bag-dashboard-card ekwa-bag-card-shortcode">
            <h3><?php esc_html_e('Shortcode', 'ekwa-before-after-gallery'); ?></h3>
            <p style="margin-bottom: 15px; color: #666;">
                <?php esc_html_e('Copy this shortcode and paste it into any page or post to display your gallery.', 'ekwa-before-after-gallery'); ?>
            </p>
            <div class="ekwa-bag-shortcode-box">
                <code>[ekwa_gallery]</code>
                <button type="button" class="ekwa-bag-copy-btn"><?php esc_html_e('Copy', 'ekwa-before-after-gallery'); ?></button>
            </div>
            
        </div>
    </div>

    <div class="ekwa-bag-quick-links">
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=ekwa_bag_case')); ?>" class="ekwa-bag-quick-link">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Add New Case', 'ekwa-before-after-gallery'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=ekwa_bag_case')); ?>" class="ekwa-bag-quick-link">
            <span class="dashicons dashicons-grid-view"></span>
            <?php esc_html_e('View All Cases', 'ekwa-before-after-gallery'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=ekwa_bag_category&post_type=ekwa_bag_case')); ?>" class="ekwa-bag-quick-link">
            <span class="dashicons dashicons-category"></span>
            <?php esc_html_e('Manage Categories', 'ekwa-before-after-gallery'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=ekwa-bag-settings')); ?>" class="ekwa-bag-quick-link">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Settings', 'ekwa-before-after-gallery'); ?>
        </a>
    </div>
</div>
