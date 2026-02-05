/**
 * EKWA Before After Gallery - Frontend JavaScript
 */


(function($) {
    'use strict';

    // Main Gallery Class
    class EKWABeforeAfterGallery {
        constructor() {
            this.cases = [];
            this.categoryTree = {};
            this.filtered = [];
            this.activeMainCat = 'all';
            this.activeSubCat = null;
            this.currentCase = null;
            this.currentCaseIdx = 0;
            this.currentViewIdx = 0;
            this.cardDesign = 'stacked'; // Default design

            this.init();
        }

        init() {
            // Get data from localized script
            if (typeof ekwaBagFrontend !== 'undefined') {
                this.cases = ekwaBagFrontend.cases || [];
                this.categoryTree = ekwaBagFrontend.categories || {};
                this.cardDesign = ekwaBagFrontend.cardDesign || 'stacked';
                this.filtered = [...this.cases];
                
                // Debug logging
                console.log('EKWA Gallery initialized with', this.cases.length, 'cases');
                console.log('Card design:', this.cardDesign);
                console.log('Cases data:', this.cases);
                console.log('Categories:', this.categoryTree);
                
                // Show debug info if available
                if (ekwaBagFrontend.debug) {
                    console.log('DEBUG - All posts in database:', ekwaBagFrontend.debug);
                }
            } else {
                console.warn('EKWA Gallery: ekwaBagFrontend data not found');
            }
            
            // Add 'all' to categoryTree if missing
            if (!this.categoryTree.all) {
                this.categoryTree.all = { label: 'All', subCats: [] };
            }

            // Cache DOM elements
            this.cacheElements();
            
            // Bind events
            this.bindEvents();
            
            // Initial render
            this.renderMainTags();
            this.renderSubTags();
            this.filterCases();
        }

        cacheElements() {
            this.$wrapper = $('.ekwa-bag-wrapper');
            this.$mainTags = $('#ekwa-bag-mainTags');
            this.$subTags = $('#ekwa-bag-subTags');
            this.$cardGrid = $('#ekwa-bag-cardGrid');
            this.$resultsCount = $('#ekwa-bag-resultsCount');
            this.$clearBtn = $('#ekwa-bag-clearBtn');
            this.$modal = $('#ekwa-bag-modal');
            this.$modalBackdrop = $('#ekwa-bag-modalBackdrop');
            this.$modalClose = $('#ekwa-bag-modalClose');
            this.$modalBreadcrumb = $('#ekwa-bag-modalBreadcrumb');
            this.$modalTitle = $('#ekwa-bag-modalTitle');
            this.$modalDesc = $('#ekwa-bag-modalDesc');
            this.$modalBefore = $('#ekwa-bag-modalBefore');
            this.$modalAfter = $('#ekwa-bag-modalAfter');
            this.$modalThumbs = $('#ekwa-bag-modalThumbs');
            this.$modalViewCount = $('#ekwa-bag-modalViewCount');
            this.$modalCurrentNum = $('#ekwa-bag-modalCurrentNum');
            this.$modalTotalNum = $('#ekwa-bag-modalTotalNum');
            this.$modalPrev = $('#ekwa-bag-modalPrev');
            this.$modalNext = $('#ekwa-bag-modalNext');
        }

        bindEvents() {
            const self = this;

            // Clear button
            this.$clearBtn.on('click', function() {
                self.activeMainCat = 'all';
                self.activeSubCat = null;
                self.renderMainTags();
                self.renderSubTags();
                self.filterCases();
            });

            // Modal events
            this.$modalClose.on('click', function() {
                self.closeModal();
            });

            this.$modalBackdrop.on('click', function() {
                self.closeModal();
            });

            this.$modalPrev.on('click', function() {
                self.navCase(-1);
            });

            this.$modalNext.on('click', function() {
                self.navCase(1);
            });

            // Keyboard events
            $(document).on('keydown', function(e) {
                if (!self.$modal.hasClass('active')) return;
                
                if (e.key === 'Escape') self.closeModal();
                if (e.key === 'ArrowLeft') self.navCase(-1);
                if (e.key === 'ArrowRight') self.navCase(1);
            });
        }

        renderMainTags() {
            const self = this;
            let html = '';

            Object.entries(this.categoryTree).forEach(function([key, cat]) {
                const count = key === 'all' 
                    ? self.cases.length 
                    : self.cases.filter(c => c.mainCat === key).length;
                
                // Skip categories with 0 posts (except 'all')
                if (count === 0 && key !== 'all') return;
                
                html += `
                    <button class="ekwa-bag-tag-btn ${self.activeMainCat === key ? 'active' : ''}" data-cat="${key}">
                        ${cat.label}<span class="ekwa-bag-tag-count">${count}</span>
                    </button>
                `;
            });

            this.$mainTags.html(html);

            // Bind click events
            this.$mainTags.find('.ekwa-bag-tag-btn').on('click', function() {
                self.activeMainCat = $(this).data('cat');
                self.activeSubCat = null;
                self.renderMainTags();
                self.renderSubTags();
                self.filterCases();
            });
        }

        renderSubTags() {
            const self = this;
            const cat = this.categoryTree[this.activeMainCat];

            if (!cat || !cat.subCats || cat.subCats.length === 0) {
                this.$subTags.removeClass('visible').html('');
                return;
            }

            let html = `
                <span class="ekwa-bag-filter-label">Refine:</span>
                <div class="ekwa-bag-tag-group">
                    <button class="ekwa-bag-sub-tag-btn ${!this.activeSubCat ? 'active' : ''}" data-sub="all">All</button>
            `;

            cat.subCats.forEach(function(sub) {
                html += `
                    <button class="ekwa-bag-sub-tag-btn ${self.activeSubCat === sub.key ? 'active' : ''}" data-sub="${sub.key}">${sub.label}</button>
                `;
            });

            html += '</div>';

            this.$subTags.html(html).addClass('visible');

            // Bind click events
            this.$subTags.find('.ekwa-bag-sub-tag-btn').on('click', function() {
                const sub = $(this).data('sub');
                self.activeSubCat = sub === 'all' ? null : sub;
                self.renderSubTags();
                self.filterCases();
            });
        }

        filterCases() {
            if (this.activeMainCat === 'all') {
                this.filtered = [...this.cases];
            } else if (this.activeSubCat) {
                this.filtered = this.cases.filter(c => 
                    c.mainCat === this.activeMainCat && c.subCat === this.activeSubCat
                );
            } else {
                this.filtered = this.cases.filter(c => c.mainCat === this.activeMainCat);
            }

            this.$resultsCount.text(this.filtered.length);
            this.$clearBtn.toggleClass('visible', this.activeMainCat !== 'all');
            this.renderGrid();
        }

        renderGrid() {
            const self = this;

            // Get card class based on design setting
            const cardClassMap = {
                'stacked': 'ekwa-bag-stacked-card',
                'side-by-side': 'ekwa-bag-sidebyside-card',
                'overlay': 'ekwa-bag-overlay-card',
                'minimal': 'ekwa-bag-minimal-card'
            };
            const cardClass = cardClassMap[this.cardDesign] || 'ekwa-bag-stacked-card';

            if (this.cases.length === 0) {
                // No cases at all - show "add from admin" message
                this.$cardGrid.html(`
                    <div class="ekwa-bag-empty-state">
                        <i class="fas fa-images"></i>
                        <h3>No Cases Found</h3>
                        <p>Add your first before/after case from the admin panel.</p>
                    </div>
                `);
                return;
            }

            if (this.filtered.length === 0) {
                // Cases exist but none match filters
                this.$cardGrid.html(`
                    <div class="ekwa-bag-empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No Results Found</h3>
                        <p>Try selecting a different category</p>
                    </div>
                `);
                return;
            }

            let html = '';

            this.filtered.forEach(function(c, idx) {
                const set = c.sets[0];
                
                // Debug log
                console.log('Case set data:', set);
                
                const beforeAttrs = `src="${set.before}" alt="${set.beforeAlt || 'Before'}"${set.beforeWidth ? ` width="${set.beforeWidth}"` : ''}${set.beforeHeight ? ` height="${set.beforeHeight}"` : ''}`;
                const afterAttrs = `src="${set.after}" alt="${set.afterAlt || 'After'}"${set.afterWidth ? ` width="${set.afterWidth}"` : ''}${set.afterHeight ? ` height="${set.afterHeight}"` : ''}`;
                
                html += `
                    <article class="${cardClass}" data-id="${c.id}" style="animation-delay: ${idx * 0.08}s">
                        <div class="ekwa-bag-card-images">
                            <div class="ekwa-bag-card-img-wrapper">
                                <img ${beforeAttrs}>
                                <span class="ekwa-bag-card-img-label">Before</span>
                            </div>
                            <div class="ekwa-bag-card-separator"></div>
                            <div class="ekwa-bag-card-img-wrapper after">
                                <img ${afterAttrs}>
                                <span class="ekwa-bag-card-img-label">After</span>
                            </div>
                        </div>
                        <div class="ekwa-bag-card-content">
                            <h3 class="ekwa-bag-card-title">${c.title}</h3>
                            <p class="ekwa-bag-card-desc">${c.desc}</p>
                            <div class="ekwa-bag-card-footer">
                                <div class="ekwa-bag-card-views">
                                    <i class="fas fa-layer-group"></i>
                                    ${c.sets.length} views
                                </div>
                                <span class="ekwa-bag-card-action">
                                    View All <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </article>
                `;
            });

            this.$cardGrid.html(html);

            // Bind click events
            this.$cardGrid.find('.' + cardClass).on('click', function() {
                const id = parseInt($(this).data('id'));
                self.openModal(id);
            });
        }

        openModal(id) {
            this.currentCase = this.cases.find(c => c.id === id);
            this.currentCaseIdx = this.filtered.findIndex(c => c.id === id);
            this.currentViewIdx = 0;
            
            this.$modal.addClass('active');
            $('body').css('overflow', 'hidden');
            
            this.updateModal();
        }

        closeModal() {
            this.$modal.removeClass('active');
            $('body').css('overflow', '');
        }

        updateModal() {
            const self = this;
            const view = this.currentCase.sets[this.currentViewIdx];
            const mainCatLabel = this.categoryTree[this.currentCase.mainCat]?.label || '';
            const subCatObj = this.categoryTree[this.currentCase.mainCat]?.subCats?.find(s => s.key === this.currentCase.subCat);
            const subCatLabel = subCatObj?.label || '';

            this.$modalBreadcrumb.text(`${mainCatLabel} › ${subCatLabel}`);
            this.$modalTitle.text(this.currentCase.title);
            this.$modalDesc.html(`<p>${this.currentCase.desc}</p>`);
            
            // Update modal images with proper alt text and dimensions
            this.$modalBefore.attr('src', view.before)
                              .attr('alt', view.beforeAlt || 'Before');
            if (view.beforeWidth) this.$modalBefore.attr('width', view.beforeWidth);
            if (view.beforeHeight) this.$modalBefore.attr('height', view.beforeHeight);
            
            this.$modalAfter.attr('src', view.after)
                             .attr('alt', view.afterAlt || 'After');
            if (view.afterWidth) this.$modalAfter.attr('width', view.afterWidth);
            if (view.afterHeight) this.$modalAfter.attr('height', view.afterHeight);
            
            this.$modalViewCount.text(this.currentCase.sets.length);
            this.$modalCurrentNum.text(this.currentCaseIdx + 1);
            this.$modalTotalNum.text(this.filtered.length);

            // Thumbnails
            let thumbsHtml = '';
            this.currentCase.sets.forEach(function(s, i) {
                thumbsHtml += `
                    <div class="ekwa-bag-modal-thumb ${i === self.currentViewIdx ? 'active' : ''}" data-idx="${i}">
                        <img src="${s.before}" alt="${s.beforeAlt || 'Before'}">
                        <img src="${s.after}" alt="${s.afterAlt || 'After'}">
                    </div>
                `;
            });
            this.$modalThumbs.html(thumbsHtml);

            // Bind thumbnail click
            this.$modalThumbs.find('.ekwa-bag-modal-thumb').on('click', function() {
                self.currentViewIdx = parseInt($(this).data('idx'));
                self.updateModal();
            });

            // Nav buttons
            this.$modalPrev.prop('disabled', this.currentCaseIdx === 0);
            this.$modalNext.prop('disabled', this.currentCaseIdx === this.filtered.length - 1);
        }

        navCase(dir) {
            if (dir === -1 && this.currentCaseIdx > 0) {
                this.currentCaseIdx--;
            } else if (dir === 1 && this.currentCaseIdx < this.filtered.length - 1) {
                this.currentCaseIdx++;
            }
            
            this.currentCase = this.filtered[this.currentCaseIdx];
            this.currentViewIdx = 0;
            this.updateModal();
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.ekwa-bag-wrapper').length) {
            new EKWABeforeAfterGallery();
        }
    });

})(jQuery);
