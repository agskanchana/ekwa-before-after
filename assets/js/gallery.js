/**
 * EKWA Before After Gallery - Frontend JavaScript (Vanilla JS)
 */

(function() {
    'use strict';

    // Helper functions
    function qs(selector, parent) {
        return (parent || document).querySelector(selector);
    }
    function qsa(selector, parent) {
        return (parent || document).querySelectorAll(selector);
    }

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
                this.showLabels = ekwaBagFrontend.showLabels !== undefined ? ekwaBagFrontend.showLabels : 1;
                this.filtered = [...this.cases];
                
                // Debug logging
                console.log('EKWA Gallery initialized with', this.cases.length, 'cases');
                console.log('Card design:', this.cardDesign);
                console.log('Show labels:', this.showLabels);
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
            this.elWrapper = qs('.ekwa-bag-wrapper');
            this.elMainTags = qs('#ekwa-bag-mainTags');
            this.elSubTags = qs('#ekwa-bag-subTags');
            this.elCardGrid = qs('#ekwa-bag-cardGrid');
            this.elResultsCount = qs('#ekwa-bag-resultsCount');
            this.elClearBtn = qs('#ekwa-bag-clearBtn');
            this.elModal = qs('#ekwa-bag-modal');
            this.elModalBackdrop = qs('#ekwa-bag-modalBackdrop');
            this.elModalClose = qs('#ekwa-bag-modalClose');
            this.elModalBreadcrumb = qs('#ekwa-bag-modalBreadcrumb');
            this.elModalTitle = qs('#ekwa-bag-modalTitle');
            this.elModalDesc = qs('#ekwa-bag-modalDesc');
            this.elModalBefore = qs('#ekwa-bag-modalBefore');
            this.elModalAfter = qs('#ekwa-bag-modalAfter');
            this.elModalThumbs = qs('#ekwa-bag-modalThumbs');
            this.elModalViewCount = qs('#ekwa-bag-modalViewCount');
            this.elModalCurrentNum = qs('#ekwa-bag-modalCurrentNum');
            this.elModalTotalNum = qs('#ekwa-bag-modalTotalNum');
            this.elModalPrev = qs('#ekwa-bag-modalPrev');
            this.elModalNext = qs('#ekwa-bag-modalNext');
        }

        bindEvents() {
            const self = this;

            // Clear button
            if (this.elClearBtn) {
                this.elClearBtn.addEventListener('click', function() {
                    self.activeMainCat = 'all';
                    self.activeSubCat = null;
                    self.renderMainTags();
                    self.renderSubTags();
                    self.filterCases();
                });
            }

            // Modal events
            if (this.elModalClose) {
                this.elModalClose.addEventListener('click', function() {
                    self.closeModal();
                });
            }

            if (this.elModalBackdrop) {
                this.elModalBackdrop.addEventListener('click', function() {
                    self.closeModal();
                });
            }

            if (this.elModalPrev) {
                this.elModalPrev.addEventListener('click', function() {
                    self.navCase(-1);
                });
            }

            if (this.elModalNext) {
                this.elModalNext.addEventListener('click', function() {
                    self.navCase(1);
                });
            }

            // Keyboard events
            document.addEventListener('keydown', function(e) {
                if (!self.elModal || !self.elModal.classList.contains('active')) return;
                
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

            this.elMainTags.innerHTML = html;

            // Bind click events
            qsa('.ekwa-bag-tag-btn', this.elMainTags).forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.activeMainCat = this.dataset.cat;
                    self.activeSubCat = null;
                    self.renderMainTags();
                    self.renderSubTags();
                    self.filterCases();
                });
            });
        }

        renderSubTags() {
            const self = this;
            const cat = this.categoryTree[this.activeMainCat];

            if (!cat || !cat.subCats || cat.subCats.length === 0) {
                this.elSubTags.classList.remove('visible');
                this.elSubTags.innerHTML = '';
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

            this.elSubTags.innerHTML = html;
            this.elSubTags.classList.add('visible');

            // Bind click events
            qsa('.ekwa-bag-sub-tag-btn', this.elSubTags).forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const sub = this.dataset.sub;
                    self.activeSubCat = sub === 'all' ? null : sub;
                    self.renderSubTags();
                    self.filterCases();
                });
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

            this.elResultsCount.textContent = this.filtered.length;
            this.elClearBtn.classList.toggle('visible', this.activeMainCat !== 'all');
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
                this.elCardGrid.innerHTML = `
                    <div class="ekwa-bag-empty-state">
                        <i class="fas fa-images"></i>
                        <h3>No Cases Found</h3>
                        <p>Add your first before/after case from the admin panel.</p>
                    </div>
                `;
                return;
            }

            if (this.filtered.length === 0) {
                // Cases exist but none match filters
                this.elCardGrid.innerHTML = `
                    <div class="ekwa-bag-empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No Results Found</h3>
                        <p>Try selecting a different category</p>
                    </div>
                `;
                return;
            }

            let html = '';

            this.filtered.forEach(function(c, idx) {
                const set = c.sets[0];
                const isCombined = set.isCombined || false;
                
                // Debug log
                console.log('Case set data:', set, 'isCombined:', isCombined);
                
                const beforeAttrs = `src="${set.before}" alt="${set.beforeAlt || 'Before'}"${set.beforeWidth ? ` width="${set.beforeWidth}"` : ''}${set.beforeHeight ? ` height="${set.beforeHeight}"` : ''}`;
                const afterAttrs = `src="${set.after}" alt="${set.afterAlt || 'After'}"${set.afterWidth ? ` width="${set.afterWidth}"` : ''}${set.afterHeight ? ` height="${set.afterHeight}"` : ''}`;
                
                // Generate card images HTML based on combined mode
                let cardImagesHtml;
                if (isCombined) {
                    // Single combined image - show labels on left and right if enabled
                    const beforeLabel = self.showLabels ? '<span class="ekwa-bag-card-img-label left">Before</span>' : '';
                    const afterLabel = self.showLabels ? '<span class="ekwa-bag-card-img-label right">After</span>' : '';
                    cardImagesHtml = `
                        <div class="ekwa-bag-card-images ekwa-bag-combined-image">
                            <div class="ekwa-bag-card-img-wrapper combined">
                                <img ${beforeAttrs}>
                                ${beforeLabel}
                                ${afterLabel}
                            </div>
                        </div>`;
                } else {
                    // Separate before/after images
                    const beforeLabel = self.showLabels ? '<span class="ekwa-bag-card-img-label">Before</span>' : '';
                    const afterLabel = self.showLabels ? '<span class="ekwa-bag-card-img-label">After</span>' : '';
                    cardImagesHtml = `
                        <div class="ekwa-bag-card-images">
                            <div class="ekwa-bag-card-img-wrapper">
                                <img ${beforeAttrs}>
                                ${beforeLabel}
                            </div>
                            <div class="ekwa-bag-card-separator"></div>
                            <div class="ekwa-bag-card-img-wrapper after">
                                <img ${afterAttrs}>
                                ${afterLabel}
                            </div>
                        </div>`;
                }
                
                html += `
                    <article class="${cardClass}${isCombined ? ' ekwa-bag-combined-card' : ''}" data-id="${c.id}" style="animation-delay: ${idx * 0.08}s">
                        ${cardImagesHtml}
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

            this.elCardGrid.innerHTML = html;

            // Bind click events
            qsa('.' + cardClass, this.elCardGrid).forEach(function(card) {
                card.addEventListener('click', function() {
                    const id = parseInt(this.dataset.id);
                    self.openModal(id);
                });
            });
        }

        openModal(id) {
            this.currentCase = this.cases.find(c => c.id === id);
            this.currentCaseIdx = this.filtered.findIndex(c => c.id === id);
            this.currentViewIdx = 0;
            
            this.elModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            this.updateModal();
        }

        closeModal() {
            this.elModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        updateModal() {
            const self = this;
            const view = this.currentCase.sets[this.currentViewIdx];
            const isCombined = view.isCombined || false;
            const mainCatLabel = this.categoryTree[this.currentCase.mainCat]?.label || '';
            const subCatObj = this.categoryTree[this.currentCase.mainCat]?.subCats?.find(s => s.key === this.currentCase.subCat);
            const subCatLabel = subCatObj?.label || '';

            this.elModalBreadcrumb.textContent = `${mainCatLabel} › ${subCatLabel}`;
            this.elModalTitle.textContent = this.currentCase.title;
            this.elModalDesc.innerHTML = `<p>${this.currentCase.desc}</p>`;
            
            // Handle combined vs separate image display
            const elModalImages = qs('#ekwa-bag-modalImages');
            if (isCombined) {
                // Single combined image - hide after image box and expand before to full width
                elModalImages.classList.add('ekwa-bag-combined-modal');
                if (!this.showLabels) {
                    elModalImages.classList.add('ekwa-bag-no-labels');
                } else {
                    elModalImages.classList.remove('ekwa-bag-no-labels');
                }
                
                this.elModalBefore.setAttribute('src', view.before);
                this.elModalBefore.setAttribute('alt', view.beforeAlt || 'Combined Before/After');
                if (view.beforeWidth) this.elModalBefore.setAttribute('width', view.beforeWidth);
                if (view.beforeHeight) this.elModalBefore.setAttribute('height', view.beforeHeight);
            } else {
                // Separate before/after images
                elModalImages.classList.remove('ekwa-bag-combined-modal');
                if (!this.showLabels) {
                    elModalImages.classList.add('ekwa-bag-no-labels');
                } else {
                    elModalImages.classList.remove('ekwa-bag-no-labels');
                }
                
                // Update modal images with proper alt text and dimensions
                this.elModalBefore.setAttribute('src', view.before);
                this.elModalBefore.setAttribute('alt', view.beforeAlt || 'Before');
                if (view.beforeWidth) this.elModalBefore.setAttribute('width', view.beforeWidth);
                if (view.beforeHeight) this.elModalBefore.setAttribute('height', view.beforeHeight);
                
                this.elModalAfter.setAttribute('src', view.after);
                this.elModalAfter.setAttribute('alt', view.afterAlt || 'After');
                if (view.afterWidth) this.elModalAfter.setAttribute('width', view.afterWidth);
                if (view.afterHeight) this.elModalAfter.setAttribute('height', view.afterHeight);
            }
            
            this.elModalViewCount.textContent = this.currentCase.sets.length;
            this.elModalCurrentNum.textContent = this.currentCaseIdx + 1;
            this.elModalTotalNum.textContent = this.filtered.length;

            // Thumbnails
            let thumbsHtml = '';
            this.currentCase.sets.forEach(function(s, i) {
                const thumbCombined = s.isCombined || false;
                if (thumbCombined) {
                    // Combined image - show single thumbnail
                    thumbsHtml += `
                        <div class="ekwa-bag-modal-thumb ekwa-bag-combined-thumb ${i === self.currentViewIdx ? 'active' : ''}" data-idx="${i}">
                            <img src="${s.before}" alt="${s.beforeAlt || 'Combined Before/After'}">
                        </div>
                    `;
                } else {
                    // Separate images - show both thumbnails
                    thumbsHtml += `
                        <div class="ekwa-bag-modal-thumb ${i === self.currentViewIdx ? 'active' : ''}" data-idx="${i}">
                            <img src="${s.before}" alt="${s.beforeAlt || 'Before'}">
                            <img src="${s.after}" alt="${s.afterAlt || 'After'}">
                        </div>
                    `;
                }
            });
            this.elModalThumbs.innerHTML = thumbsHtml;

            // Bind thumbnail click
            qsa('.ekwa-bag-modal-thumb', this.elModalThumbs).forEach(function(thumb) {
                thumb.addEventListener('click', function() {
                    self.currentViewIdx = parseInt(this.dataset.idx);
                    self.updateModal();
                });
            });

            // Nav buttons
            this.elModalPrev.disabled = this.currentCaseIdx === 0;
            this.elModalNext.disabled = this.currentCaseIdx === this.filtered.length - 1;
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
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGallery);
    } else {
        initGallery();
    }

    function initGallery() {
        if (document.querySelector('.ekwa-bag-wrapper')) {
            new EKWABeforeAfterGallery();
        }
    }

})();
