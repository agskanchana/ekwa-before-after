/**
 * EKWA Before After Gallery - Admin JavaScript
 */

(function($) {
    'use strict';

    // Image Sets Manager
    class EKWAImageSetsManager {
        constructor() {
            this.$container = $('.ekwa-bag-image-sets');
            if (!this.$container.length) return;
            
            this.mediaFrame = null;
            this.currentField = null;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initSortable();
        }

        bindEvents() {
            const self = this;

            // Add new set
            $(document).on('click', '.ekwa-bag-add-set', function(e) {
                e.preventDefault();
                self.addNewSet();
            });

            // Remove set
            $(document).on('click', '.ekwa-bag-remove-set', function(e) {
                e.preventDefault();
                if (confirm(ekwaBagAdmin.strings.removeSet)) {
                    $(this).closest('.ekwa-bag-image-set').slideUp(300, function() {
                        $(this).remove();
                        self.updateSetNumbers();
                    });
                }
            });

            // Select image
            $(document).on('click', '.ekwa-bag-select-image', function(e) {
                e.preventDefault();
                self.currentField = $(this).closest('.ekwa-bag-image-field');
                self.openMediaFrame();
            });

            // Remove image
            $(document).on('click', '.ekwa-bag-remove-image', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.ekwa-bag-image-field');
                $field.find('input[type="hidden"]').val('');
                $field.find('.ekwa-bag-image-preview').html('<span class="ekwa-bag-no-image">No image selected</span>');
            });
        }

        initSortable() {
            this.$container.sortable({
                items: '.ekwa-bag-image-set',
                handle: '.ekwa-bag-image-set-title',
                cursor: 'move',
                placeholder: 'ekwa-bag-sortable-placeholder',
                update: () => this.updateSetNumbers()
            });
        }

        addNewSet() {
            const count = this.$container.find('.ekwa-bag-image-set').length;
            const html = this.getSetTemplate(count + 1);
            
            $(html).hide().insertBefore('.ekwa-bag-add-set').slideDown(300);
        }

        getSetTemplate(number) {
            return `
                <div class="ekwa-bag-image-set">
                    <div class="ekwa-bag-image-set-header">
                        <span class="ekwa-bag-image-set-title">
                            <span class="dashicons dashicons-move"></span>
                            Image Set #<span class="ekwa-bag-set-number">${number}</span>
                        </span>
                        <button type="button" class="ekwa-bag-remove-set">
                            <span class="dashicons dashicons-trash"></span> Remove
                        </button>
                    </div>
                    <div class="ekwa-bag-image-set-content">
                        <div class="ekwa-bag-image-field" data-type="before">
                            <label>Before Image</label>
                            <div class="ekwa-bag-image-preview">
                                <span class="ekwa-bag-no-image">No image selected</span>
                            </div>
                            <input type="hidden" name="ekwa_bag_image_sets[${number - 1}][before]" value="">
                            <div class="ekwa-bag-image-buttons">
                                <button type="button" class="ekwa-bag-select-image">Select Image</button>
                                <button type="button" class="ekwa-bag-remove-image">Remove</button>
                            </div>
                        </div>
                        <div class="ekwa-bag-image-field" data-type="after">
                            <label>After Image</label>
                            <div class="ekwa-bag-image-preview">
                                <span class="ekwa-bag-no-image">No image selected</span>
                            </div>
                            <input type="hidden" name="ekwa_bag_image_sets[${number - 1}][after]" value="">
                            <div class="ekwa-bag-image-buttons">
                                <button type="button" class="ekwa-bag-select-image">Select Image</button>
                                <button type="button" class="ekwa-bag-remove-image">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        updateSetNumbers() {
            this.$container.find('.ekwa-bag-image-set').each(function(index) {
                const $set = $(this);
                $set.find('.ekwa-bag-set-number').text(index + 1);
                $set.find('input[name*="before"]').attr('name', `ekwa_bag_image_sets[${index}][before]`);
                $set.find('input[name*="after"]').attr('name', `ekwa_bag_image_sets[${index}][after]`);
            });
        }

        openMediaFrame() {
            const self = this;

            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }

            this.mediaFrame = wp.media({
                title: ekwaBagAdmin.strings.selectImage,
                button: {
                    text: ekwaBagAdmin.strings.useImage
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            this.mediaFrame.on('select', function() {
                const attachment = self.mediaFrame.state().get('selection').first().toJSON();
                const imageUrl = attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                
                self.currentField.find('input[type="hidden"]').val(attachment.id);
                self.currentField.find('.ekwa-bag-image-preview').html(`<img src="${imageUrl}" alt="">`);
            });

            this.mediaFrame.open();
        }
    }

    // Copy Shortcode
    class EKWACopyShortcode {
        constructor() {
            this.init();
        }

        init() {
            $(document).on('click', '.ekwa-bag-copy-btn', function() {
                const $btn = $(this);
                const text = $btn.prev('code').text();
                
                navigator.clipboard.writeText(text).then(function() {
                    $btn.text('Copied!').addClass('copied');
                    setTimeout(function() {
                        $btn.text('Copy').removeClass('copied');
                    }, 2000);
                });
            });
        }
    }

    // Settings Page
    class EKWASettings {
        constructor() {
            this.init();
        }

        init() {
            // Color picker preview
            $('input[data-color-preview]').on('input', function() {
                const color = $(this).val();
                $(this).siblings('.ekwa-bag-color-preview').css('background-color', color);
            });
        }
    }

    // Initialize
    $(document).ready(function() {
        new EKWAImageSetsManager();
        new EKWACopyShortcode();
        new EKWASettings();
    });

})(jQuery);
