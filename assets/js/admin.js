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
            this.$form = $('#ekwa-bag-settings-form');
            if (!this.$form.length) return;
            
            this.mediaFrame = null;
            this.init();
        }

        init() {
            this.initTabs();
            this.initColorPickers();
            this.initWatermarkOptions();
            this.initPositionGrid();
            this.initRangeSliders();
            this.initBulkTools();
            this.initImportExport();
            this.initCardDesignSelector();
        }

        // Card Design Selector
        initCardDesignSelector() {
            const $selector = $('.ekwa-bag-card-design-selector');
            if (!$selector.length) return;

            // Handle radio button change
            $selector.find('input[type="radio"]').on('change', function() {
                $selector.find('.ekwa-bag-design-option').removeClass('selected');
                $(this).closest('.ekwa-bag-design-option').addClass('selected');
            });

            // Initial state - mark selected option
            $selector.find('input[type="radio"]:checked').closest('.ekwa-bag-design-option').addClass('selected');
        }

        // Tab Navigation
        initTabs() {
            const self = this;
            
            $('.ekwa-bag-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                
                // Update tab buttons
                $('.ekwa-bag-tabs .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Update tab content
                $('.ekwa-bag-tab-content').removeClass('active');
                $(`.ekwa-bag-tab-content[data-tab="${tab}"]`).addClass('active');
                
                // Update URL hash
                window.location.hash = tab;
            });
            
            // Check for hash on load
            if (window.location.hash) {
                const tab = window.location.hash.substring(1);
                $(`.ekwa-bag-tabs .nav-tab[data-tab="${tab}"]`).trigger('click');
            }
        }

        // Color Pickers
        initColorPickers() {
            const self = this;
            
            if (typeof $.fn.wpColorPicker === 'function') {
                $('.ekwa-bag-color-picker').wpColorPicker({
                    change: function(event, ui) {
                        self.updateColorPreview();
                    },
                    clear: function() {
                        self.updateColorPreview();
                    }
                });
            }
            
            // Reset colors button
            $('#ekwa-bag-reset-colors').on('click', function() {
                const defaults = {
                    'ekwa_bag_color_bg': '#f5f3f0',
                    'ekwa_bag_color_card_bg': '#ffffff',
                    'ekwa_bag_color_text': '#1a1a1a',
                    'ekwa_bag_color_text_soft': '#777777',
                    'ekwa_bag_color_accent': '#c9a87c',
                    'ekwa_bag_color_accent_dark': '#b08d5b',
                    'ekwa_bag_color_border': '#e8e4df'
                };
                
                for (const [id, color] of Object.entries(defaults)) {
                    $(`#${id}`).wpColorPicker('color', color);
                }
                
                self.updateColorPreview();
            });
            
            // Initial preview
            this.updateColorPreview();
        }

        // Update color preview
        updateColorPreview() {
            const $preview = $('#ekwa-bag-color-preview');
            if (!$preview.length) return;
            
            const colors = {
                bg: $('#ekwa_bag_color_bg').val() || '#f5f3f0',
                cardBg: $('#ekwa_bag_color_card_bg').val() || '#ffffff',
                text: $('#ekwa_bag_color_text').val() || '#1a1a1a',
                textSoft: $('#ekwa_bag_color_text_soft').val() || '#777777',
                accent: $('#ekwa_bag_color_accent').val() || '#c9a87c',
                accentDark: $('#ekwa_bag_color_accent_dark').val() || '#b08d5b',
                border: $('#ekwa_bag_color_border').val() || '#e8e4df'
            };
            
            $preview.css('background', colors.bg);
            $preview.find('.preview-header h4').css('color', colors.text);
            $preview.find('.preview-header p').css('color', colors.textSoft);
            $preview.find('.preview-tag').css({
                'background': colors.cardBg,
                'border-color': colors.border,
                'color': colors.text
            });
            $preview.find('.preview-tag.active').css({
                'background': colors.accent,
                'border-color': colors.accent,
                'color': '#fff'
            });
            $preview.find('.preview-card').css('background', colors.cardBg);
            $preview.find('.preview-content h5').css('color', colors.text);
            $preview.find('.preview-content p').css('color', colors.textSoft);
        }

        // Watermark Options
        initWatermarkOptions() {
            const self = this;
            
            // Toggle watermark options
            $('#ekwa_bag_watermark_enabled').on('change', function() {
                $('#ekwa-bag-watermark-options').toggle($(this).is(':checked'));
                $('#ekwa-bag-bulk-watermark').prop('disabled', !$(this).is(':checked'));
            }).trigger('change');
            
            // Toggle watermark type options
            $('input[name="ekwa_bag_watermark_type"]').on('change', function() {
                const type = $(this).val();
                $('#ekwa-bag-text-options').toggle(type === 'text');
                $('#ekwa-bag-image-options').toggle(type === 'image');
            }).filter(':checked').trigger('change');
            
            // Watermark image selection
            $('#ekwa-bag-select-watermark').on('click', function(e) {
                e.preventDefault();
                self.openWatermarkMediaFrame();
            });
            
            $('#ekwa-bag-remove-watermark').on('click', function(e) {
                e.preventDefault();
                $('#ekwa_bag_watermark_image').val('');
                $('#ekwa-bag-watermark-preview').html('<span class="no-image">No image selected</span>');
            });
        }

        // Open media frame for watermark image
        openWatermarkMediaFrame() {
            const self = this;
            
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }
            
            this.mediaFrame = wp.media({
                title: 'Select Watermark Image',
                button: { text: 'Use This Image' },
                multiple: false,
                library: { type: 'image' }
            });
            
            this.mediaFrame.on('select', function() {
                const attachment = self.mediaFrame.state().get('selection').first().toJSON();
                const imageUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                
                $('#ekwa_bag_watermark_image').val(attachment.id);
                $('#ekwa-bag-watermark-preview').html(`<img src="${imageUrl}" alt="Watermark">`);
            });
            
            this.mediaFrame.open();
        }

        // Position Grid
        initPositionGrid() {
            $('.ekwa-bag-position-option').on('click', function() {
                $('.ekwa-bag-position-option').removeClass('active');
                $(this).addClass('active');
            });
        }

        // Range Sliders
        initRangeSliders() {
            $('input[type="range"]').on('input', function() {
                $(this).siblings('.range-value').text($(this).val() + '%');
            });
        }

        // Bulk Tools
        initBulkTools() {
            const self = this;
            
            // Test watermark configuration
            $('#ekwa-bag-test-watermark').on('click', function() {
                const $result = $('#ekwa-bag-test-result');
                $result.html('<p>Testing...</p>').show();
                
                $.ajax({
                    url: ekwaBagAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ekwa_bag_test_watermark',
                        nonce: ekwaBagAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const d = response.data;
                            let html = '<h4>Watermark Configuration Status:</h4><ul>';
                            html += `<li>Image Library: <strong>${d.library}</strong> ${d.is_available ? '✓' : '✗'}</li>`;
                            html += `<li>Watermark Enabled: <strong>${d.is_enabled ? 'Yes' : 'No'}</strong></li>`;
                            html += `<li>Watermark Type: <strong>${d.type}</strong></li>`;
                            
                            if (d.type === 'text') {
                                html += `<li>Has Text: <strong>${d.has_text ? 'Yes' : 'No'}</strong> ${d.text ? '("' + d.text + '")' : '⚠️ No text set!'}</li>`;
                            } else {
                                html += `<li>Has Image: <strong>${d.has_image ? 'Yes' : 'No'}</strong> ${d.has_image ? '(ID: ' + d.image_id + ')' : '⚠️ No image selected!'}</li>`;
                            }
                            
                            html += `<li>Fully Configured: <strong>${d.is_configured ? 'Yes ✓' : 'No ✗'}</strong></li>`;
                            html += '</ul>';
                            
                            if (!d.is_configured) {
                                html += '<p class="warning"><strong>⚠️ Watermark will not be applied!</strong> Please check settings above.</p>';
                            } else {
                                html += '<p class="success"><strong>✓ Configuration looks good!</strong></p>';
                            }
                            
                            $result.html(html);
                        } else {
                            $result.html('<p class="error">Error: ' + (response.data.message || 'Unknown error') + '</p>');
                        }
                    },
                    error: function() {
                        $result.html('<p class="error">Error: Request failed</p>');
                    }
                });
            });
            
            // Bulk apply watermarks
            $('#ekwa-bag-bulk-watermark').on('click', function() {
                if (!confirm('This will apply watermarks to all gallery images. Continue?')) {
                    return;
                }
                
                self.showProgress();
                
                $.ajax({
                    url: ekwaBagAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ekwa_bag_bulk_watermark',
                        nonce: ekwaBagAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = `Completed: ${response.data.success} watermarked, ${response.data.skipped} skipped, ${response.data.failed} failed`;
                            if (response.data.error_details) {
                                message += ` - ${response.data.error_details}`;
                            }
                            self.updateProgress(100, message);
                        } else {
                            self.updateProgress(0, 'Error: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        self.updateProgress(0, 'Error: Request failed');
                    }
                });
            });
            
            // Clear and reapply watermarks
            $('#ekwa-bag-clear-reapply').on('click', function() {
                if (!confirm('This will remove all existing watermarks and reapply them fresh. This may take a moment. Continue?')) {
                    return;
                }
                
                self.showProgress();
                
                $.ajax({
                    url: ekwaBagAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ekwa_bag_clear_and_reapply',
                        nonce: ekwaBagAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = `Completed: ${response.data.success} watermarked, ${response.data.failed} failed`;
                            if (response.data.error_details) {
                                message += ` - ${response.data.error_details}`;
                            }
                            self.updateProgress(100, message);
                            setTimeout(function() {
                                alert('Watermarks have been reapplied! Please refresh any gallery pages to see the changes.');
                            }, 500);
                        } else {
                            self.updateProgress(0, 'Error: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        self.updateProgress(0, 'Error: Request failed');
                    }
                });
            });
            
            // Remove watermarks
            $('#ekwa-bag-remove-watermarks').on('click', function() {
                if (!confirm('This will remove watermarks from all gallery images and restore originals. Continue?')) {
                    return;
                }
                
                self.showProgress();
                
                $.ajax({
                    url: ekwaBagAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ekwa_bag_remove_watermarks',
                        nonce: ekwaBagAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            self.updateProgress(100, `Completed: ${response.data.success} restored, ${response.data.skipped} skipped, ${response.data.failed} failed`);
                        } else {
                            self.updateProgress(0, 'Error: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        self.updateProgress(0, 'Error: Request failed');
                    }
                });
            });
        }

        showProgress() {
            $('#ekwa-bag-progress').show();
            this.updateProgress(50, 'Processing...');
        }

        updateProgress(percent, status) {
            $('#ekwa-bag-progress .ekwa-bag-progress-fill').css('width', percent + '%');
            $('#ekwa-bag-progress .progress-status').text(status);
        }

        // Import/Export
        initImportExport() {
            const self = this;
            
            // Export
            $('#ekwa-bag-export-settings').on('click', function() {
                $.ajax({
                    url: ekwaBagAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ekwa_bag_export_settings',
                        nonce: ekwaBagAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'ekwa-gallery-settings.json';
                            a.click();
                            URL.revokeObjectURL(url);
                        }
                    }
                });
            });
            
            // Import trigger
            $('#ekwa-bag-import-settings').on('click', function() {
                $('#ekwa-bag-import-file').trigger('click');
            });
            
            // Import file change
            $('#ekwa-bag-import-file').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = JSON.parse(e.target.result);
                        
                        $.ajax({
                            url: ekwaBagAdmin.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'ekwa_bag_import_settings',
                                nonce: ekwaBagAdmin.nonce,
                                import_data: JSON.stringify(data)
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('Settings imported successfully! Reloading page...');
                                    location.reload();
                                } else {
                                    alert('Error: ' + (response.data.message || 'Import failed'));
                                }
                            }
                        });
                    } catch (err) {
                        alert('Invalid JSON file');
                    }
                };
                reader.readAsText(file);
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
