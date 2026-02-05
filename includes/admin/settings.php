<?php
/**
 * Settings Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check for image processing library availability
$has_gd = extension_loaded('gd') && function_exists('gd_info');
$has_imagick = extension_loaded('imagick') && class_exists('Imagick');
$image_library = $has_imagick ? 'imagick' : ($has_gd ? 'gd' : false);

// Handle form submission
if (isset($_POST['ekwa_bag_save_settings']) && check_admin_referer('ekwa_bag_settings_nonce', 'ekwa_bag_settings_nonce_field')) {
    $settings = array(
        // Color Settings
        'color_bg'          => sanitize_hex_color($_POST['ekwa_bag_color_bg'] ?? '#f5f3f0'),
        'color_card_bg'     => sanitize_hex_color($_POST['ekwa_bag_color_card_bg'] ?? '#ffffff'),
        'color_text'        => sanitize_hex_color($_POST['ekwa_bag_color_text'] ?? '#1a1a1a'),
        'color_text_soft'   => sanitize_hex_color($_POST['ekwa_bag_color_text_soft'] ?? '#777777'),
        'color_accent'      => sanitize_hex_color($_POST['ekwa_bag_color_accent'] ?? '#c9a87c'),
        'color_accent_dark' => sanitize_hex_color($_POST['ekwa_bag_color_accent_dark'] ?? '#b08d5b'),
        'color_border'      => sanitize_hex_color($_POST['ekwa_bag_color_border'] ?? '#e8e4df'),
        
        // Gallery Settings
        'card_design'         => sanitize_text_field($_POST['ekwa_bag_card_design'] ?? 'stacked'),
        'cards_per_row'       => absint($_POST['ekwa_bag_cards_per_row'] ?? 3),
        
        // Watermark Settings
        'watermark_enabled'   => isset($_POST['ekwa_bag_watermark_enabled']) ? 1 : 0,
        'watermark_type'      => sanitize_text_field($_POST['ekwa_bag_watermark_type'] ?? 'text'),
        'watermark_text'      => sanitize_text_field($_POST['ekwa_bag_watermark_text'] ?? ''),
        'watermark_image'     => absint($_POST['ekwa_bag_watermark_image'] ?? 0),
        'watermark_position'  => sanitize_text_field($_POST['ekwa_bag_watermark_position'] ?? 'bottom-right'),
        'watermark_opacity'   => absint($_POST['ekwa_bag_watermark_opacity'] ?? 50),
        'watermark_size'      => absint($_POST['ekwa_bag_watermark_size'] ?? 20),
        'watermark_color'     => sanitize_hex_color($_POST['ekwa_bag_watermark_color'] ?? '#ffffff'),
        'watermark_padding'   => absint($_POST['ekwa_bag_watermark_padding'] ?? 10),
    );
    
    update_option('ekwa_bag_settings', $settings);
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'ekwa-before-after-gallery') . '</p></div>';
}

// Get current settings
$settings = get_option('ekwa_bag_settings', array());
$defaults = array(
    // Color Defaults
    'color_bg'          => '#f5f3f0',
    'color_card_bg'     => '#ffffff',
    'color_text'        => '#1a1a1a',
    'color_text_soft'   => '#777777',
    'color_accent'      => '#c9a87c',
    'color_accent_dark' => '#b08d5b',
    'color_border'      => '#e8e4df',
    
    // Gallery Defaults
    'card_design'         => 'stacked',
    'cards_per_row'       => 3,
    
    // Watermark Defaults
    'watermark_enabled'   => 0,
    'watermark_type'      => 'text',
    'watermark_text'      => '',
    'watermark_image'     => 0,
    'watermark_position'  => 'bottom-right',
    'watermark_opacity'   => 50,
    'watermark_size'      => 20,
    'watermark_color'     => '#ffffff',
    'watermark_padding'   => 10,
);
$settings = wp_parse_args($settings, $defaults);

// Get watermark image URL if set
$watermark_image_url = '';
if (!empty($settings['watermark_image'])) {
    $watermark_image_url = wp_get_attachment_image_url($settings['watermark_image'], 'thumbnail');
}

// Check if watermark is enabled but not configured
$watermark_warning = false;
if ($settings['watermark_enabled']) {
    if ($settings['watermark_type'] === 'text' && empty($settings['watermark_text'])) {
        $watermark_warning = __('Watermark is enabled but no text is set. Please enter watermark text below.', 'ekwa-before-after-gallery');
    } elseif ($settings['watermark_type'] === 'image' && empty($settings['watermark_image'])) {
        $watermark_warning = __('Watermark is enabled but no image is selected. Please select a watermark image below.', 'ekwa-before-after-gallery');
    }
}
?>

<div class="wrap ekwa-bag-settings">
    <h1><?php esc_html_e('Gallery Settings', 'ekwa-before-after-gallery'); ?></h1>
    
    <?php if ($watermark_warning): ?>
    <div class="notice notice-warning">
        <p><strong><?php esc_html_e('Watermark Configuration:', 'ekwa-before-after-gallery'); ?></strong> 
        <?php echo esc_html($watermark_warning); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!$image_library): ?>
    <div class="notice notice-error">
        <p><strong><?php esc_html_e('Warning:', 'ekwa-before-after-gallery'); ?></strong> 
        <?php esc_html_e('Neither GD Library nor Imagick extension is available. Watermark functionality will not work. Please contact your hosting provider to enable one of these PHP extensions.', 'ekwa-before-after-gallery'); ?></p>
    </div>
    <?php else: ?>
    <div class="notice notice-info">
        <p><strong><?php esc_html_e('Image Library:', 'ekwa-before-after-gallery'); ?></strong> 
        <?php echo $has_imagick ? 'Imagick' : 'GD Library'; ?> <?php esc_html_e('is available for image processing.', 'ekwa-before-after-gallery'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Settings Tabs -->
    <nav class="nav-tab-wrapper ekwa-bag-tabs">
        <a href="#colors" class="nav-tab nav-tab-active" data-tab="colors"><?php esc_html_e('Colors', 'ekwa-before-after-gallery'); ?></a>
        <a href="#gallery" class="nav-tab" data-tab="gallery"><?php esc_html_e('Gallery', 'ekwa-before-after-gallery'); ?></a>
        <a href="#watermark" class="nav-tab" data-tab="watermark"><?php esc_html_e('Watermark', 'ekwa-before-after-gallery'); ?></a>
        <a href="#tools" class="nav-tab" data-tab="tools"><?php esc_html_e('Tools', 'ekwa-before-after-gallery'); ?></a>
    </nav>

    <form method="post" action="" id="ekwa-bag-settings-form">
        <?php wp_nonce_field('ekwa_bag_settings_nonce', 'ekwa_bag_settings_nonce_field'); ?>

        <!-- Colors Tab -->
        <div class="ekwa-bag-tab-content active" data-tab="colors">
            <div class="ekwa-bag-settings-section">
                <h2><?php esc_html_e('Gallery Colors', 'ekwa-before-after-gallery'); ?></h2>
                <p class="description"><?php esc_html_e('Customize the color scheme of your before/after gallery.', 'ekwa-before-after-gallery'); ?></p>

                <div class="ekwa-bag-color-grid">
                    <div class="ekwa-bag-color-field">
                        <label for="ekwa_bag_color_bg"><?php esc_html_e('Background Color', 'ekwa-before-after-gallery'); ?></label>
                        <input type="text" name="ekwa_bag_color_bg" id="ekwa_bag_color_bg" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['color_bg']); ?>" data-default-color="#f5f3f0">
                        <span class="description"><?php esc_html_e('Main gallery background', 'ekwa-before-after-gallery'); ?></span>
                    </div>

                    <div class="ekwa-bag-color-field">
                        <label for="ekwa_bag_color_card_bg"><?php esc_html_e('Card Background', 'ekwa-before-after-gallery'); ?></label>
                        <input type="text" name="ekwa_bag_color_card_bg" id="ekwa_bag_color_card_bg" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['color_card_bg']); ?>" data-default-color="#ffffff">
                        <span class="description"><?php esc_html_e('Case card background', 'ekwa-before-after-gallery'); ?></span>
                    </div>

                    <div class="ekwa-bag-color-field">
                        <label for="ekwa_bag_color_text"><?php esc_html_e('Primary Text', 'ekwa-before-after-gallery'); ?></label>
                        <input type="text" name="ekwa_bag_color_text" id="ekwa_bag_color_text" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['color_text']); ?>" data-default-color="#1a1a1a">
                        <span class="description"><?php esc_html_e('Main text color', 'ekwa-before-after-gallery'); ?></span>
                    </div>

                    <div class="ekwa-bag-color-field">
                        <label for="ekwa_bag_color_text_soft"><?php esc_html_e('Secondary Text', 'ekwa-before-after-gallery'); ?></label>
                        <input type="text" name="ekwa_bag_color_text_soft" id="ekwa_bag_color_text_soft" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['color_text_soft']); ?>" data-default-color="#777777">
                        <span class="description"><?php esc_html_e('Subtle text, descriptions', 'ekwa-before-after-gallery'); ?></span>
                    </div>

                    <div class="ekwa-bag-color-field">
                        <label for="ekwa_bag_color_accent"><?php esc_html_e('Accent Color', 'ekwa-before-after-gallery'); ?></label>
                        <input type="text" name="ekwa_bag_color_accent" id="ekwa_bag_color_accent" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['color_accent']); ?>" data-default-color="#c9a87c">
                        <span class="description"><?php esc_html_e('Buttons, active states', 'ekwa-before-after-gallery'); ?></span>
                    </div>

                    <div class="ekwa-bag-color-field">
                        <label for="ekwa_bag_color_accent_dark"><?php esc_html_e('Accent Hover', 'ekwa-before-after-gallery'); ?></label>
                        <input type="text" name="ekwa_bag_color_accent_dark" id="ekwa_bag_color_accent_dark" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['color_accent_dark']); ?>" data-default-color="#b08d5b">
                        <span class="description"><?php esc_html_e('Hover state for accent', 'ekwa-before-after-gallery'); ?></span>
                    </div>

                    <div class="ekwa-bag-color-field">
                        <label for="ekwa_bag_color_border"><?php esc_html_e('Border Color', 'ekwa-before-after-gallery'); ?></label>
                        <input type="text" name="ekwa_bag_color_border" id="ekwa_bag_color_border" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['color_border']); ?>" data-default-color="#e8e4df">
                        <span class="description"><?php esc_html_e('Card borders, dividers', 'ekwa-before-after-gallery'); ?></span>
                    </div>
                </div>

                <div class="ekwa-bag-color-actions">
                    <button type="button" class="button" id="ekwa-bag-reset-colors"><?php esc_html_e('Reset to Defaults', 'ekwa-before-after-gallery'); ?></button>
                </div>

                <!-- Color Preview -->
                <div class="ekwa-bag-color-preview-section">
                    <h3><?php esc_html_e('Preview', 'ekwa-before-after-gallery'); ?></h3>
                    <div class="ekwa-bag-preview-box" id="ekwa-bag-color-preview">
                        <div class="preview-header">
                            <h4><?php esc_html_e('Gallery Title', 'ekwa-before-after-gallery'); ?></h4>
                            <p><?php esc_html_e('Browse our treatment results', 'ekwa-before-after-gallery'); ?></p>
                        </div>
                        <div class="preview-tags">
                            <span class="preview-tag"><?php esc_html_e('All', 'ekwa-before-after-gallery'); ?></span>
                            <span class="preview-tag active"><?php esc_html_e('Cosmetic', 'ekwa-before-after-gallery'); ?></span>
                            <span class="preview-tag"><?php esc_html_e('Restorative', 'ekwa-before-after-gallery'); ?></span>
                        </div>
                        <div class="preview-card">
                            <div class="preview-image"></div>
                            <div class="preview-content">
                                <h5><?php esc_html_e('Case Title', 'ekwa-before-after-gallery'); ?></h5>
                                <p><?php esc_html_e('Sample description text...', 'ekwa-before-after-gallery'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gallery Tab -->
        <div class="ekwa-bag-tab-content" data-tab="gallery">
            <div class="ekwa-bag-settings-section">
                <h2><?php esc_html_e('Gallery Settings', 'ekwa-before-after-gallery'); ?></h2>

                <div class="ekwa-bag-settings-row">
                    <label for="ekwa_bag_card_design"><?php esc_html_e('Card Design', 'ekwa-before-after-gallery'); ?></label>
                    <div class="ekwa-bag-field">
                        <select name="ekwa_bag_card_design" id="ekwa_bag_card_design" class="ekwa-bag-select">
                            <option value="stacked" <?php selected($settings['card_design'], 'stacked'); ?>><?php esc_html_e('Stacked - Before/After vertically stacked', 'ekwa-before-after-gallery'); ?></option>
                            <option value="side-by-side" <?php selected($settings['card_design'], 'side-by-side'); ?>><?php esc_html_e('Side by Side - Before/After horizontally', 'ekwa-before-after-gallery'); ?></option>
                            <option value="overlay" <?php selected($settings['card_design'], 'overlay'); ?>><?php esc_html_e('Overlay - Hover to reveal after', 'ekwa-before-after-gallery'); ?></option>
                            <option value="minimal" <?php selected($settings['card_design'], 'minimal'); ?>><?php esc_html_e('Minimal - Clean single image card', 'ekwa-before-after-gallery'); ?></option>
                        </select>
                        <span class="description"><?php esc_html_e('Choose how cards are displayed in the gallery grid', 'ekwa-before-after-gallery'); ?></span>
                    </div>
                </div>

                <div class="ekwa-bag-settings-row">
                    <label for="ekwa_bag_cards_per_row"><?php esc_html_e('Cards Per Row', 'ekwa-before-after-gallery'); ?></label>
                    <div class="ekwa-bag-field">
                        <input type="number" name="ekwa_bag_cards_per_row" id="ekwa_bag_cards_per_row" value="<?php echo esc_attr($settings['cards_per_row']); ?>" min="1" max="6">
                        <span class="description"><?php esc_html_e('Number of cards to display per row (1-6)', 'ekwa-before-after-gallery'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Watermark Tab -->
        <div class="ekwa-bag-tab-content" data-tab="watermark">
            <div class="ekwa-bag-settings-section">
                <h2><?php esc_html_e('Watermark Settings', 'ekwa-before-after-gallery'); ?></h2>
                
                <?php if (!$image_library): ?>
                <div class="ekwa-bag-warning-box">
                    <span class="dashicons dashicons-warning"></span>
                    <p><?php esc_html_e('Watermark functionality requires either GD Library or Imagick PHP extension. Please contact your hosting provider to enable one of these extensions.', 'ekwa-before-after-gallery'); ?></p>
                </div>
                <?php endif; ?>

                <div class="ekwa-bag-settings-row">
                    <label><?php esc_html_e('Enable Watermark', 'ekwa-before-after-gallery'); ?></label>
                    <div class="ekwa-bag-field">
                        <label class="ekwa-bag-toggle">
                            <input type="checkbox" name="ekwa_bag_watermark_enabled" id="ekwa_bag_watermark_enabled" value="1" <?php checked($settings['watermark_enabled'], 1); ?> <?php disabled(!$image_library); ?>>
                            <span class="ekwa-bag-toggle-slider"></span>
                        </label>
                        <span class="description"><?php esc_html_e('Apply watermark to gallery images', 'ekwa-before-after-gallery'); ?></span>
                    </div>
                </div>

                <div class="ekwa-bag-watermark-options" id="ekwa-bag-watermark-options">
                    <div class="ekwa-bag-settings-row">
                        <label><?php esc_html_e('Watermark Type', 'ekwa-before-after-gallery'); ?></label>
                        <div class="ekwa-bag-field">
                            <div class="ekwa-bag-radio-group">
                                <label class="ekwa-bag-radio">
                                    <input type="radio" name="ekwa_bag_watermark_type" value="text" <?php checked($settings['watermark_type'], 'text'); ?>>
                                    <span><?php esc_html_e('Text Watermark', 'ekwa-before-after-gallery'); ?></span>
                                </label>
                                <label class="ekwa-bag-radio">
                                    <input type="radio" name="ekwa_bag_watermark_type" value="image" <?php checked($settings['watermark_type'], 'image'); ?>>
                                    <span><?php esc_html_e('Image/Logo Watermark', 'ekwa-before-after-gallery'); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Text Watermark Options -->
                    <div class="ekwa-bag-watermark-type-options" id="ekwa-bag-text-options">
                        <div class="ekwa-bag-settings-row">
                            <label for="ekwa_bag_watermark_text"><?php esc_html_e('Watermark Text', 'ekwa-before-after-gallery'); ?></label>
                            <div class="ekwa-bag-field">
                                <input type="text" name="ekwa_bag_watermark_text" id="ekwa_bag_watermark_text" value="<?php echo esc_attr($settings['watermark_text']); ?>" placeholder="<?php esc_attr_e('© Your Practice Name', 'ekwa-before-after-gallery'); ?>">
                            </div>
                        </div>

                        <div class="ekwa-bag-settings-row">
                            <label for="ekwa_bag_watermark_color"><?php esc_html_e('Text Color', 'ekwa-before-after-gallery'); ?></label>
                            <div class="ekwa-bag-field">
                                <input type="text" name="ekwa_bag_watermark_color" id="ekwa_bag_watermark_color" class="ekwa-bag-color-picker" value="<?php echo esc_attr($settings['watermark_color']); ?>" data-default-color="#ffffff">
                            </div>
                        </div>

                        <div class="ekwa-bag-settings-row">
                            <label for="ekwa_bag_watermark_size"><?php esc_html_e('Font Size', 'ekwa-before-after-gallery'); ?></label>
                            <div class="ekwa-bag-field">
                                <input type="number" name="ekwa_bag_watermark_size" id="ekwa_bag_watermark_size" value="<?php echo esc_attr($settings['watermark_size']); ?>" min="8" max="72">
                                <span class="description"><?php esc_html_e('Size in pixels', 'ekwa-before-after-gallery'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Image Watermark Options -->
                    <div class="ekwa-bag-watermark-type-options" id="ekwa-bag-image-options" style="display: none;">
                        <div class="ekwa-bag-settings-row">
                            <label><?php esc_html_e('Watermark Image', 'ekwa-before-after-gallery'); ?></label>
                            <div class="ekwa-bag-field">
                                <div class="ekwa-bag-watermark-image-field">
                                    <div class="ekwa-bag-watermark-preview" id="ekwa-bag-watermark-preview">
                                        <?php if ($watermark_image_url): ?>
                                            <img src="<?php echo esc_url($watermark_image_url); ?>" alt="Watermark">
                                        <?php else: ?>
                                            <span class="no-image"><?php esc_html_e('No image selected', 'ekwa-before-after-gallery'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="ekwa_bag_watermark_image" id="ekwa_bag_watermark_image" value="<?php echo esc_attr($settings['watermark_image']); ?>">
                                    <div class="ekwa-bag-watermark-buttons">
                                        <button type="button" class="button" id="ekwa-bag-select-watermark"><?php esc_html_e('Select Image', 'ekwa-before-after-gallery'); ?></button>
                                        <button type="button" class="button" id="ekwa-bag-remove-watermark"><?php esc_html_e('Remove', 'ekwa-before-after-gallery'); ?></button>
                                    </div>
                                </div>
                                <span class="description"><?php esc_html_e('Use a PNG with transparent background for best results.', 'ekwa-before-after-gallery'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Common Watermark Options -->
                    <div class="ekwa-bag-settings-row">
                        <label><?php esc_html_e('Position', 'ekwa-before-after-gallery'); ?></label>
                        <div class="ekwa-bag-field">
                            <div class="ekwa-bag-position-grid">
                                <?php
                                $positions = array(
                                    'top-left'     => __('Top Left', 'ekwa-before-after-gallery'),
                                    'top-center'   => __('Top Center', 'ekwa-before-after-gallery'),
                                    'top-right'    => __('Top Right', 'ekwa-before-after-gallery'),
                                    'middle-left'  => __('Middle Left', 'ekwa-before-after-gallery'),
                                    'middle-center'=> __('Center', 'ekwa-before-after-gallery'),
                                    'middle-right' => __('Middle Right', 'ekwa-before-after-gallery'),
                                    'bottom-left'  => __('Bottom Left', 'ekwa-before-after-gallery'),
                                    'bottom-center'=> __('Bottom Center', 'ekwa-before-after-gallery'),
                                    'bottom-right' => __('Bottom Right', 'ekwa-before-after-gallery'),
                                );
                                foreach ($positions as $value => $label):
                                ?>
                                <label class="ekwa-bag-position-option <?php echo $settings['watermark_position'] === $value ? 'active' : ''; ?>">
                                    <input type="radio" name="ekwa_bag_watermark_position" value="<?php echo esc_attr($value); ?>" <?php checked($settings['watermark_position'], $value); ?>>
                                    <span class="position-dot"></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="ekwa-bag-settings-row">
                        <label for="ekwa_bag_watermark_opacity"><?php esc_html_e('Opacity', 'ekwa-before-after-gallery'); ?></label>
                        <div class="ekwa-bag-field">
                            <div class="ekwa-bag-range-field">
                                <input type="range" name="ekwa_bag_watermark_opacity" id="ekwa_bag_watermark_opacity" value="<?php echo esc_attr($settings['watermark_opacity']); ?>" min="10" max="100">
                                <span class="range-value"><?php echo esc_html($settings['watermark_opacity']); ?>%</span>
                            </div>
                        </div>
                    </div>

                    <div class="ekwa-bag-settings-row">
                        <label for="ekwa_bag_watermark_padding"><?php esc_html_e('Padding', 'ekwa-before-after-gallery'); ?></label>
                        <div class="ekwa-bag-field">
                            <input type="number" name="ekwa_bag_watermark_padding" id="ekwa_bag_watermark_padding" value="<?php echo esc_attr($settings['watermark_padding']); ?>" min="0" max="100">
                            <span class="description"><?php esc_html_e('Distance from edge in pixels', 'ekwa-before-after-gallery'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tools Tab -->
        <div class="ekwa-bag-tab-content" data-tab="tools">
            <div class="ekwa-bag-settings-section">
                <h2><?php esc_html_e('Bulk Watermark Tool', 'ekwa-before-after-gallery'); ?></h2>
                <p class="description"><?php esc_html_e('Apply or remove watermarks from all gallery images at once. Original images are preserved and can be restored.', 'ekwa-before-after-gallery'); ?></p>

                <?php if (!$image_library): ?>
                <div class="ekwa-bag-warning-box">
                    <span class="dashicons dashicons-warning"></span>
                    <p><?php esc_html_e('Watermark tools require GD Library or Imagick PHP extension.', 'ekwa-before-after-gallery'); ?></p>
                </div>
                <?php else: ?>
                
                <div class="ekwa-bag-tool-box">
                    <div class="ekwa-bag-tool-info">
                        <h4><?php esc_html_e('Test Configuration', 'ekwa-before-after-gallery'); ?></h4>
                        <p><?php esc_html_e('Check if watermark settings are correctly configured.', 'ekwa-before-after-gallery'); ?></p>
                    </div>
                    <button type="button" class="button" id="ekwa-bag-test-watermark">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e('Test Config', 'ekwa-before-after-gallery'); ?>
                    </button>
                </div>
                <div id="ekwa-bag-test-result" class="ekwa-bag-test-result" style="display:none;"></div>
                
                <div class="ekwa-bag-tool-box">
                    <div class="ekwa-bag-tool-info">
                        <h4><?php esc_html_e('Apply Watermark to All Images', 'ekwa-before-after-gallery'); ?></h4>
                        <p><?php esc_html_e('This will create watermarked copies of all gallery images. Original images are preserved. Already watermarked images will be skipped.', 'ekwa-before-after-gallery'); ?></p>
                    </div>
                    <button type="button" class="button button-primary" id="ekwa-bag-bulk-watermark" <?php disabled(!$settings['watermark_enabled']); ?>>
                        <span class="dashicons dashicons-images-alt2"></span>
                        <?php esc_html_e('Apply Watermarks', 'ekwa-before-after-gallery'); ?>
                    </button>
                </div>
                
                <div class="ekwa-bag-tool-box">
                    <div class="ekwa-bag-tool-info">
                        <h4><?php esc_html_e('Clear & Reapply All Watermarks', 'ekwa-before-after-gallery'); ?></h4>
                        <p><?php esc_html_e('Remove all existing watermarks and reapply fresh. Use this if watermarks are not showing correctly.', 'ekwa-before-after-gallery'); ?></p>
                    </div>
                    <button type="button" class="button button-secondary" id="ekwa-bag-clear-reapply" <?php disabled(!$settings['watermark_enabled']); ?>>
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Clear & Reapply', 'ekwa-before-after-gallery'); ?>
                    </button>
                </div>

                <div class="ekwa-bag-tool-box">
                    <div class="ekwa-bag-tool-info">
                        <h4><?php esc_html_e('Remove All Watermarks', 'ekwa-before-after-gallery'); ?></h4>
                        <p><?php esc_html_e('Restore original images by removing watermarked versions.', 'ekwa-before-after-gallery'); ?></p>
                    </div>
                    <button type="button" class="button" id="ekwa-bag-remove-watermarks">
                        <span class="dashicons dashicons-undo"></span>
                        <?php esc_html_e('Remove Watermarks', 'ekwa-before-after-gallery'); ?>
                    </button>
                </div>

                <div class="ekwa-bag-progress-section" id="ekwa-bag-progress" style="display: none;">
                    <div class="ekwa-bag-progress-bar">
                        <div class="ekwa-bag-progress-fill"></div>
                    </div>
                    <div class="ekwa-bag-progress-text">
                        <span class="progress-status"><?php esc_html_e('Processing...', 'ekwa-before-after-gallery'); ?></span>
                        <span class="progress-count">0/0</span>
                    </div>
                </div>

                <?php endif; ?>
            </div>

            <div class="ekwa-bag-settings-section">
                <h2><?php esc_html_e('Import / Export', 'ekwa-before-after-gallery'); ?></h2>
                <p class="description"><?php esc_html_e('Export your gallery settings or import from another installation.', 'ekwa-before-after-gallery'); ?></p>

                <div class="ekwa-bag-tool-box">
                    <div class="ekwa-bag-tool-info">
                        <h4><?php esc_html_e('Export Settings', 'ekwa-before-after-gallery'); ?></h4>
                        <p><?php esc_html_e('Download your current settings as a JSON file.', 'ekwa-before-after-gallery'); ?></p>
                    </div>
                    <button type="button" class="button" id="ekwa-bag-export-settings">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export', 'ekwa-before-after-gallery'); ?>
                    </button>
                </div>

                <div class="ekwa-bag-tool-box">
                    <div class="ekwa-bag-tool-info">
                        <h4><?php esc_html_e('Import Settings', 'ekwa-before-after-gallery'); ?></h4>
                        <p><?php esc_html_e('Upload a previously exported settings file.', 'ekwa-before-after-gallery'); ?></p>
                    </div>
                    <input type="file" id="ekwa-bag-import-file" accept=".json" style="display: none;">
                    <button type="button" class="button" id="ekwa-bag-import-settings">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Import', 'ekwa-before-after-gallery'); ?>
                    </button>
                </div>
            </div>

            <div class="ekwa-bag-settings-section">
                <h2><?php esc_html_e('System Information', 'ekwa-before-after-gallery'); ?></h2>
                
                <table class="ekwa-bag-system-info">
                    <tr>
                        <th><?php esc_html_e('Plugin Version', 'ekwa-before-after-gallery'); ?></th>
                        <td><?php echo esc_html(EKWA_BAG_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('WordPress Version', 'ekwa-before-after-gallery'); ?></th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('PHP Version', 'ekwa-before-after-gallery'); ?></th>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('GD Library', 'ekwa-before-after-gallery'); ?></th>
                        <td>
                            <?php if ($has_gd): ?>
                                <span class="status-ok"><?php esc_html_e('Available', 'ekwa-before-after-gallery'); ?></span>
                                <?php
                                $gd_info = gd_info();
                                echo ' (' . esc_html($gd_info['GD Version'] ?? 'Unknown version') . ')';
                                ?>
                            <?php else: ?>
                                <span class="status-error"><?php esc_html_e('Not Available', 'ekwa-before-after-gallery'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Imagick', 'ekwa-before-after-gallery'); ?></th>
                        <td>
                            <?php if ($has_imagick): ?>
                                <span class="status-ok"><?php esc_html_e('Available', 'ekwa-before-after-gallery'); ?></span>
                                <?php
                                $imagick = new Imagick();
                                $version = $imagick->getVersion();
                                echo ' (' . esc_html($version['versionString'] ?? 'Unknown version') . ')';
                                ?>
                            <?php else: ?>
                                <span class="status-error"><?php esc_html_e('Not Available', 'ekwa-before-after-gallery'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Max Upload Size', 'ekwa-before-after-gallery'); ?></th>
                        <td><?php echo esc_html(size_format(wp_max_upload_size())); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Memory Limit', 'ekwa-before-after-gallery'); ?></th>
                        <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="ekwa_bag_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'ekwa-before-after-gallery'); ?>">
        </p>
    </form>
</div>
