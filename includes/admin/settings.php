<?php
/**
 * Settings Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['ekwa_bag_save_settings']) && check_admin_referer('ekwa_bag_settings_nonce', 'ekwa_bag_settings_nonce_field')) {
    $settings = array(
        // Settings can be added here in the future
    );
    
    update_option('ekwa_bag_settings', $settings);
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'ekwa-before-after-gallery') . '</p></div>';
}

// Get current settings
$settings = get_option('ekwa_bag_settings', array());
$defaults = array(
    // Defaults can be added here in the future
);
$settings = wp_parse_args($settings, $defaults);
?>

<div class="wrap ekwa-bag-settings">
    <h1><?php esc_html_e('Gallery Settings', 'ekwa-before-after-gallery'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('ekwa_bag_settings_nonce', 'ekwa_bag_settings_nonce_field'); ?>

        <div class="ekwa-bag-settings-section">
            <h2><?php esc_html_e('General Settings', 'ekwa-before-after-gallery'); ?></h2>
            <p><?php esc_html_e('Settings will be available in future updates.', 'ekwa-before-after-gallery'); ?></p>
        </div>

        <p class="submit">
            <input type="submit" name="ekwa_bag_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'ekwa-before-after-gallery'); ?>">
        </p>
    </form>
</div>
