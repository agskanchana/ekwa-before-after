<?php
/**
 * Frontend Gallery Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$show_filter = $atts['show_filter'] === 'yes';
$settings = get_option('ekwa_bag_settings', array());
$show_labels = isset($settings['show_before_after_labels']) ? $settings['show_before_after_labels'] : 1;

// Debug output
echo '<!-- EKWA DEBUG START -->';
echo '<!-- Cases count: ' . count($cases) . ' -->';
echo '<!-- Cases data: ' . esc_html(print_r($cases, true)) . ' -->';
echo '<!-- Categories data: ' . esc_html(print_r($categories, true)) . ' -->';
echo '<!-- EKWA DEBUG END -->';
?>

<div class="ekwa-bag-wrapper" data-show-labels="<?php echo esc_attr($show_labels); ?>">
    <?php if ($show_filter) : ?>
    <!-- Filter Section -->
    <div class="ekwa-bag-filter-section">
        <div class="ekwa-bag-filter-row">
            <span class="ekwa-bag-filter-label"><?php esc_html_e('Category:', 'ekwa-before-after-gallery'); ?></span>
            <div class="ekwa-bag-tag-group" id="ekwa-bag-mainTags">
                <!-- Rendered by JS -->
            </div>
        </div>
        <div class="ekwa-bag-filter-row ekwa-bag-sub-tags" id="ekwa-bag-subTags">
            <!-- Rendered by JS -->
        </div>
    </div>

    <!-- Results Info -->
    <div class="ekwa-bag-results-info">
        <?php esc_html_e('Showing', 'ekwa-before-after-gallery'); ?> <strong id="ekwa-bag-resultsCount"><?php echo count($cases); ?></strong> <?php esc_html_e('transformations', 'ekwa-before-after-gallery'); ?>
        <button class="ekwa-bag-clear-btn" id="ekwa-bag-clearBtn"><?php esc_html_e('Clear', 'ekwa-before-after-gallery'); ?></button>
    </div>
    <?php endif; ?>

    <!-- Card Grid -->
    <div class="ekwa-bag-card-grid" id="ekwa-bag-cardGrid">
        <?php if (empty($cases)) : ?>
            <div class="ekwa-bag-empty-state">
                <i class="fas fa-images"></i>
                <h3><?php esc_html_e('No Cases Found', 'ekwa-before-after-gallery'); ?></h3>
                <p><?php esc_html_e('Add your first before/after case from the admin panel.', 'ekwa-before-after-gallery'); ?></p>
            </div>
        <?php else : ?>
            <!-- Cards will be rendered by JavaScript -->
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div class="ekwa-bag-modal" id="ekwa-bag-modal">
        <div class="ekwa-bag-modal-backdrop" id="ekwa-bag-modalBackdrop"></div>
        <div class="ekwa-bag-modal-content">
            <div class="ekwa-bag-modal-header">
                <div class="ekwa-bag-modal-header-info">
                    <div class="ekwa-bag-modal-breadcrumb" id="ekwa-bag-modalBreadcrumb"><?php esc_html_e('Category', 'ekwa-before-after-gallery'); ?></div>
                    <h2 class="ekwa-bag-modal-title" id="ekwa-bag-modalTitle"><?php esc_html_e('Treatment Title', 'ekwa-before-after-gallery'); ?></h2>
                </div>
                <button class="ekwa-bag-modal-close" id="ekwa-bag-modalClose"><i class="fas fa-times"></i></button>
            </div>
            <div class="ekwa-bag-modal-body">
                <div class="ekwa-bag-modal-images" id="ekwa-bag-modalImages">
                    <div class="ekwa-bag-modal-img-box">
                        <img id="ekwa-bag-modalBefore" src="" alt="<?php esc_attr_e('Before', 'ekwa-before-after-gallery'); ?>">
                        <span class="ekwa-bag-modal-img-tag"><?php esc_html_e('Before', 'ekwa-before-after-gallery'); ?></span>
                    </div>
                    <div class="ekwa-bag-modal-img-box after">
                        <img id="ekwa-bag-modalAfter" src="" alt="<?php esc_attr_e('After', 'ekwa-before-after-gallery'); ?>">
                        <span class="ekwa-bag-modal-img-tag"><?php esc_html_e('After', 'ekwa-before-after-gallery'); ?></span>
                    </div>
                </div>
                <div class="ekwa-bag-modal-desc" id="ekwa-bag-modalDesc">
                    <p><?php esc_html_e('Description here...', 'ekwa-before-after-gallery'); ?></p>
                </div>
                <div class="ekwa-bag-modal-thumbs-section">
                    <div class="ekwa-bag-modal-thumbs-label"><?php esc_html_e('All Views', 'ekwa-before-after-gallery'); ?> (<span id="ekwa-bag-modalViewCount">0</span>)</div>
                    <div class="ekwa-bag-modal-thumbs" id="ekwa-bag-modalThumbs">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </div>
            <div class="ekwa-bag-modal-footer">
                <button class="ekwa-bag-modal-nav-btn" id="ekwa-bag-modalPrev"><i class="fas fa-chevron-left"></i> <?php esc_html_e('Previous', 'ekwa-before-after-gallery'); ?></button>
                <div class="ekwa-bag-modal-counter">
                    <strong id="ekwa-bag-modalCurrentNum">1</strong> / <span id="ekwa-bag-modalTotalNum">0</span>
                </div>
                <button class="ekwa-bag-modal-nav-btn" id="ekwa-bag-modalNext"><?php esc_html_e('Next', 'ekwa-before-after-gallery'); ?> <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>
