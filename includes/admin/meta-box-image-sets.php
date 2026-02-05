<?php
/**
 * Meta Box - Image Sets Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ekwa-bag-image-sets">
    <?php if (!empty($image_sets)) : ?>
        <?php foreach ($image_sets as $index => $set) : ?>
            <div class="ekwa-bag-image-set">
                <div class="ekwa-bag-image-set-header">
                    <span class="ekwa-bag-image-set-title">
                        <span class="dashicons dashicons-move"></span>
                        <?php esc_html_e('Image Set #', 'ekwa-before-after-gallery'); ?><span class="ekwa-bag-set-number"><?php echo esc_html($index + 1); ?></span>
                    </span>
                    <?php if (count($image_sets) > 1) : ?>
                        <button type="button" class="ekwa-bag-remove-set">
                            <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Remove', 'ekwa-before-after-gallery'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="ekwa-bag-image-set-content <?php echo $pair_as_one_image ? 'single-image-mode' : ''; ?>">
                    <?php if ($pair_as_one_image) : ?>
                        <!-- Single Combined Image -->
                        <div class="ekwa-bag-image-field ekwa-bag-single-image-field" data-type="single">
                            <label><?php esc_html_e('Before/After Combined Image', 'ekwa-before-after-gallery'); ?></label>
                            <div class="ekwa-bag-image-preview">
                                <?php if (!empty($set['single_image'])) : ?>
                                    <?php echo wp_get_attachment_image($set['single_image'], 'medium'); ?>
                                <?php else : ?>
                                    <span class="ekwa-bag-no-image"><?php esc_html_e('No image selected', 'ekwa-before-after-gallery'); ?></span>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="ekwa_bag_image_sets[<?php echo esc_attr($index); ?>][single_image]" value="<?php echo esc_attr($set['single_image'] ?? ''); ?>">
                            <div class="ekwa-bag-image-buttons">
                                <button type="button" class="ekwa-bag-select-image"><?php esc_html_e('Select Image', 'ekwa-before-after-gallery'); ?></button>
                                <button type="button" class="ekwa-bag-remove-image"><?php esc_html_e('Remove', 'ekwa-before-after-gallery'); ?></button>
                            </div>
                            <p class="description"><?php esc_html_e('Upload one image containing both before and after photos side-by-side.', 'ekwa-before-after-gallery'); ?></p>
                        </div>
                    <?php else : ?>
                        <!-- Before Image -->
                        <div class="ekwa-bag-image-field" data-type="before">
                            <label><?php esc_html_e('Before Image', 'ekwa-before-after-gallery'); ?></label>
                            <div class="ekwa-bag-image-preview">
                                <?php if (!empty($set['before'])) : ?>
                                    <?php echo wp_get_attachment_image($set['before'], 'medium'); ?>
                                <?php else : ?>
                                    <span class="ekwa-bag-no-image"><?php esc_html_e('No image selected', 'ekwa-before-after-gallery'); ?></span>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="ekwa_bag_image_sets[<?php echo esc_attr($index); ?>][before]" value="<?php echo esc_attr($set['before'] ?? ''); ?>">
                            <div class="ekwa-bag-image-buttons">
                                <button type="button" class="ekwa-bag-select-image"><?php esc_html_e('Select Image', 'ekwa-before-after-gallery'); ?></button>
                                <button type="button" class="ekwa-bag-remove-image"><?php esc_html_e('Remove', 'ekwa-before-after-gallery'); ?></button>
                            </div>
                        </div>

                        <!-- After Image -->
                        <div class="ekwa-bag-image-field" data-type="after">
                            <label><?php esc_html_e('After Image', 'ekwa-before-after-gallery'); ?></label>
                            <div class="ekwa-bag-image-preview">
                                <?php if (!empty($set['after'])) : ?>
                                    <?php echo wp_get_attachment_image($set['after'], 'medium'); ?>
                                <?php else : ?>
                                    <span class="ekwa-bag-no-image"><?php esc_html_e('No image selected', 'ekwa-before-after-gallery'); ?></span>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="ekwa_bag_image_sets[<?php echo esc_attr($index); ?>][after]" value="<?php echo esc_attr($set['after'] ?? ''); ?>">
                            <div class="ekwa-bag-image-buttons">
                                <button type="button" class="ekwa-bag-select-image"><?php esc_html_e('Select Image', 'ekwa-before-after-gallery'); ?></button>
                                <button type="button" class="ekwa-bag-remove-image"><?php esc_html_e('Remove', 'ekwa-before-after-gallery'); ?></button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <button type="button" class="ekwa-bag-add-set">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php esc_html_e('Add Another Image Set', 'ekwa-before-after-gallery'); ?>
    </button>
</div>

<p class="description" style="margin-top: 15px;">
    <?php if ($pair_as_one_image) : ?>
        <?php esc_html_e('Add multiple combined before/after images to showcase different angles or stages of the treatment.', 'ekwa-before-after-gallery'); ?>
    <?php else : ?>
        <?php esc_html_e('Add multiple before/after image pairs to showcase different angles or stages of the treatment.', 'ekwa-before-after-gallery'); ?>
    <?php endif; ?>
</p>
