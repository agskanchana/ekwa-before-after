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

// Get all categories for the carousel shortcode builder
$all_categories = get_terms(array(
    'taxonomy'   => 'ekwa_bag_category',
    'hide_empty' => false,
));
if (is_wp_error($all_categories)) {
    $all_categories = array();
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

        <!-- Gallery Shortcode -->
        <div class="ekwa-bag-dashboard-card ekwa-bag-card-shortcode">
            <h3><?php esc_html_e('Gallery Shortcode', 'ekwa-before-after-gallery'); ?></h3>
            <p style="margin-bottom: 15px; color: #666;">
                <?php esc_html_e('Copy this shortcode and paste it into any page or post to display your gallery.', 'ekwa-before-after-gallery'); ?>
            </p>
            <div class="ekwa-bag-shortcode-box">
                <code>[ekwa_gallery]</code>
                <button type="button" class="ekwa-bag-copy-btn"><?php esc_html_e('Copy', 'ekwa-before-after-gallery'); ?></button>
            </div>
        </div>

        <!-- Carousel Shortcode Builder -->
        <div class="ekwa-bag-dashboard-card ekwa-bag-card-shortcode ekwa-bag-card-builder">
            <h3><?php esc_html_e('Category Carousel Shortcode', 'ekwa-before-after-gallery'); ?></h3>
            <p style="margin-bottom: 15px; color: #666;">
                <?php esc_html_e('Build your carousel shortcode. If no category is selected, it auto-detects from the page slug.', 'ekwa-before-after-gallery'); ?>
            </p>

            <div class="ekwa-bag-shortcode-builder">
                <!-- Row 1: Category + Limits -->
                <div class="ekwa-bag-builder-group">
                    <h4 class="ekwa-bag-builder-group-title"><span class="dashicons dashicons-category"></span> <?php esc_html_e('Content', 'ekwa-before-after-gallery'); ?></h4>
                    <div class="ekwa-bag-builder-row">
                        <div class="ekwa-bag-builder-field ekwa-bag-builder-field-wide">
                            <label for="ekwa-sc-category"><?php esc_html_e('Category', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-category">
                                <option value=""><?php esc_html_e('Auto-detect from page slug', 'ekwa-before-after-gallery'); ?></option>
                                <?php foreach ($all_categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->slug); ?>">
                                        <?php echo esc_html($cat->name); ?>
                                        <?php if ($cat->parent) : ?>
                                            <?php
                                            $parent = get_term($cat->parent, 'ekwa_bag_category');
                                            if ($parent && !is_wp_error($parent)) {
                                                echo ' (' . esc_html($parent->name) . ')';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ekwa-bag-builder-field">
                            <label for="ekwa-sc-limit"><?php esc_html_e('Max Items', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-limit">
                                <option value=""><?php esc_html_e('All', 'ekwa-before-after-gallery'); ?></option>
                                <option value="3">3</option>
                                <option value="5">5</option>
                                <option value="8">8</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Responsive slides per view -->
                <div class="ekwa-bag-builder-group">
                    <h4 class="ekwa-bag-builder-group-title"><span class="dashicons dashicons-desktop"></span> <?php esc_html_e('Slides Per View', 'ekwa-before-after-gallery'); ?></h4>
                    <div class="ekwa-bag-builder-row ekwa-bag-builder-row-3">
                        <div class="ekwa-bag-builder-field">
                            <label for="ekwa-sc-perpage"><?php esc_html_e('Desktop', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-perpage">
                                <option value=""><?php esc_html_e('Default', 'ekwa-before-after-gallery'); ?></option>
                                <?php for ($i = 1; $i <= 6; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="ekwa-bag-builder-field">
                            <label for="ekwa-sc-perpage-tablet"><?php esc_html_e('Tablet', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-perpage-tablet">
                                <option value=""><?php esc_html_e('Default', 'ekwa-before-after-gallery'); ?></option>
                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="ekwa-bag-builder-field">
                            <label for="ekwa-sc-perpage-mobile"><?php esc_html_e('Mobile', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-perpage-mobile">
                                <option value=""><?php esc_html_e('Default', 'ekwa-before-after-gallery'); ?></option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Controls -->
                <div class="ekwa-bag-builder-group">
                    <h4 class="ekwa-bag-builder-group-title"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Controls', 'ekwa-before-after-gallery'); ?></h4>
                    <div class="ekwa-bag-builder-row ekwa-bag-builder-row-3">
                        <div class="ekwa-bag-builder-field">
                            <label for="ekwa-sc-arrows"><?php esc_html_e('Arrows', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-arrows">
                                <option value=""><?php esc_html_e('Default', 'ekwa-before-after-gallery'); ?></option>
                                <option value="yes"><?php esc_html_e('Yes', 'ekwa-before-after-gallery'); ?></option>
                                <option value="no"><?php esc_html_e('No', 'ekwa-before-after-gallery'); ?></option>
                            </select>
                        </div>
                        <div class="ekwa-bag-builder-field">
                            <label for="ekwa-sc-dots"><?php esc_html_e('Dots', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-dots">
                                <option value=""><?php esc_html_e('Default', 'ekwa-before-after-gallery'); ?></option>
                                <option value="yes"><?php esc_html_e('Yes', 'ekwa-before-after-gallery'); ?></option>
                                <option value="no"><?php esc_html_e('No', 'ekwa-before-after-gallery'); ?></option>
                            </select>
                        </div>
                        <div class="ekwa-bag-builder-field">
                            <label for="ekwa-sc-autoplay"><?php esc_html_e('Autoplay', 'ekwa-before-after-gallery'); ?></label>
                            <select id="ekwa-sc-autoplay">
                                <option value=""><?php esc_html_e('Default', 'ekwa-before-after-gallery'); ?></option>
                                <option value="yes"><?php esc_html_e('Yes', 'ekwa-before-after-gallery'); ?></option>
                                <option value="no"><?php esc_html_e('No', 'ekwa-before-after-gallery'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="ekwa-bag-shortcode-box" style="margin-top: 15px;">
                    <code id="ekwa-sc-output">[ekwa_category_carousel]</code>
                    <button type="button" class="ekwa-bag-copy-btn"><?php esc_html_e('Copy', 'ekwa-before-after-gallery'); ?></button>
                </div>
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
