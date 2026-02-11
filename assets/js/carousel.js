/**
 * EKWA Before After Gallery - Category Carousel JavaScript
 */

(function() {
    'use strict';

    function qs(selector, parent) {
        return (parent || document).querySelector(selector);
    }
    function qsa(selector, parent) {
        return (parent || document).querySelectorAll(selector);
    }

    class EKWACategoryCarousel {
        constructor(wrapper) {
            this.wrapper = wrapper;
            this.instanceId = wrapper.dataset.instanceId || '0';
            this.cases = [];
            this.currentSlide = 0;
            this.slidesToShow = 3;
            this.slidesDesktop = 3;
            this.slidesTablet = 2;
            this.slidesMobile = 1;
            this.showArrows = true;
            this.showDots = true;
            this.autoplay = false;
            this.autoplaySpeed = 5000;
            this.showLabels = 1;
            this.autoplayTimer = null;
            this.slideWidth = 0;
            this.gap = 20;

            // Modal state
            this.currentCase = null;
            this.currentCaseIdx = 0;
            this.currentViewIdx = 0;
            this.categoryTree = {};

            this.init();
        }

        init() {
            // Get data from the global carousel data object
            var dataKey = 'ekwaBagCarousel_' + this.instanceId;
            var data = window[dataKey];

            if (!data) {
                console.warn('EKWA Carousel: data not found for instance', this.instanceId);
                return;
            }

            this.cases = data.cases || [];
            this.categoryTree = data.categories || {};
            this.slidesDesktop = parseInt(data.slidesToShow) || 3;
            this.slidesTablet = parseInt(data.slidesTablet) || 2;
            this.slidesMobile = parseInt(data.slidesMobile) || 1;
            this.showArrows = data.showArrows !== '0' && data.showArrows !== false;
            this.showDots = data.showDots !== '0' && data.showDots !== false;
            this.autoplay = data.autoplay === '1' || data.autoplay === true;
            this.autoplaySpeed = parseInt(data.autoplaySpeed) || 5000;
            this.showLabels = data.showLabels !== undefined ? parseInt(data.showLabels) : 1;

            // Set initial responsive slides count
            this.updateSlidesToShow();

            this.cacheElements();
            this.render();
            this.bindEvents();

            if (this.autoplay && this.cases.length > this.slidesToShow) {
                this.startAutoplay();
            }
        }

        /**
         * Determine how many slides to show based on viewport width
         */
        updateSlidesToShow() {
            var w = window.innerWidth;
            if (w <= 600) {
                this.slidesToShow = this.slidesMobile;
            } else if (w <= 1024) {
                this.slidesToShow = this.slidesTablet;
            } else {
                this.slidesToShow = this.slidesDesktop;
            }
            // Never show more slides than we have cases
            if (this.slidesToShow > this.cases.length) {
                this.slidesToShow = this.cases.length;
            }
        }

        cacheElements() {
            this.elTrack = qs('.ekwa-bag-carousel-track', this.wrapper);
            this.elViewport = qs('.ekwa-bag-carousel-viewport', this.wrapper);
            this.elContainer = qs('.ekwa-bag-carousel-container', this.wrapper);
            this.elPrev = qs('.ekwa-bag-carousel-arrow.prev', this.wrapper);
            this.elNext = qs('.ekwa-bag-carousel-arrow.next', this.wrapper);
            this.elDots = qs('.ekwa-bag-carousel-dots', this.wrapper);
            this.elModal = qs('.ekwa-bag-carousel-modal', this.wrapper);
        }

        render() {
            if (this.cases.length === 0) return;

            this.renderSlides();
            this.updateSlideWidths();
            this.renderDots();
            this.updateArrows();
        }

        renderSlides() {
            var self = this;
            var html = '';

            this.cases.forEach(function(c, idx) {
                var set = c.sets[0];
                var isCombined = set.isCombined || false;

                var slideImagesHtml;
                if (isCombined) {
                    var beforeLabel = self.showLabels ? '<span class="ekwa-bag-carousel-slide-label left">Before</span>' : '';
                    var afterLabel = self.showLabels ? '<span class="ekwa-bag-carousel-slide-label right">After</span>' : '';
                    slideImagesHtml = 
                        '<div class="ekwa-bag-carousel-slide-images ekwa-bag-carousel-combined">' +
                            '<div class="ekwa-bag-carousel-slide-img combined">' +
                                '<img src="' + set.before + '" alt="' + (set.beforeAlt || 'Before & After') + '">' +
                                beforeLabel + afterLabel +
                            '</div>' +
                        '</div>';
                } else {
                    var bLabel = self.showLabels ? '<span class="ekwa-bag-carousel-slide-label">Before</span>' : '';
                    var aLabel = self.showLabels ? '<span class="ekwa-bag-carousel-slide-label">After</span>' : '';
                    slideImagesHtml = 
                        '<div class="ekwa-bag-carousel-slide-images">' +
                            '<div class="ekwa-bag-carousel-slide-img">' +
                                '<img src="' + set.before + '" alt="' + (set.beforeAlt || 'Before') + '">' +
                                bLabel +
                            '</div>' +
                            '<div class="ekwa-bag-carousel-separator"></div>' +
                            '<div class="ekwa-bag-carousel-slide-img after">' +
                                '<img src="' + set.after + '" alt="' + (set.afterAlt || 'After') + '">' +
                                aLabel +
                            '</div>' +
                        '</div>';
                }

                html += 
                    '<div class="ekwa-bag-carousel-slide" data-id="' + c.id + '" data-index="' + idx + '">' +
                        slideImagesHtml +
                        '<div class="ekwa-bag-carousel-slide-content">' +
                            '<h3 class="ekwa-bag-carousel-slide-title">' + c.title + '</h3>' +
                            '<div class="ekwa-bag-carousel-slide-meta">' +
                                '<span class="ekwa-bag-carousel-slide-views"><i class="fas fa-layer-group"></i> ' + c.sets.length + ' views</span>' +
                                '<span class="ekwa-bag-carousel-slide-action">View <i class="fas fa-arrow-right"></i></span>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            });

            this.elTrack.innerHTML = html;
        }

        updateSlideWidths() {
            var slides = qsa('.ekwa-bag-carousel-slide', this.elTrack);
            if (!slides.length || !this.elViewport) return;

            // Viewport is the actual visible area (inside container padding)
            var viewportWidth = this.elViewport.offsetWidth;

            var gap = this.gap;
            var slidesToShow = this.slidesToShow;
            var totalGap = gap * (slidesToShow - 1);
            // Use Math.floor to prevent fractional pixels causing overflow
            var slideWidth = Math.floor((viewportWidth - totalGap) / slidesToShow);

            slides.forEach(function(slide) {
                slide.style.width = slideWidth + 'px';
                slide.style.minWidth = slideWidth + 'px';
                slide.style.maxWidth = slideWidth + 'px';
            });

            this.slideWidth = slideWidth;

            // Clamp currentSlide to valid range after breakpoint change
            var maxSlide = Math.max(0, this.cases.length - this.slidesToShow);
            if (this.currentSlide > maxSlide) {
                this.currentSlide = maxSlide;
            }

            this.goToSlide(this.currentSlide, false);
        }

        renderDots() {
            if (!this.showDots || !this.elDots) return;

            var totalDots = Math.max(1, this.cases.length - this.slidesToShow + 1);
            var html = '';

            for (var i = 0; i < totalDots; i++) {
                html += '<button class="ekwa-bag-carousel-dot' + (i === this.currentSlide ? ' active' : '') + '" data-dot="' + i + '"></button>';
            }

            this.elDots.innerHTML = html;
        }

        updateArrows() {
            if (!this.showArrows) return;

            var maxSlide = Math.max(0, this.cases.length - this.slidesToShow);
            if (this.elPrev) this.elPrev.disabled = this.currentSlide <= 0;
            if (this.elNext) this.elNext.disabled = this.currentSlide >= maxSlide;
        }

        updateDots() {
            if (!this.showDots || !this.elDots) return;

            qsa('.ekwa-bag-carousel-dot', this.elDots).forEach(function(dot, i) {
                dot.classList.toggle('active', i === this.currentSlide);
            }.bind(this));
        }

        goToSlide(index, animate) {
            var maxSlide = Math.max(0, this.cases.length - this.slidesToShow);
            this.currentSlide = Math.max(0, Math.min(index, maxSlide));

            var offset = this.currentSlide * (this.slideWidth + this.gap);

            if (animate === false) {
                this.elTrack.style.transition = 'none';
            } else {
                this.elTrack.style.transition = 'transform 0.4s ease';
            }

            this.elTrack.style.transform = 'translateX(-' + offset + 'px)';

            this.updateArrows();
            this.updateDots();
        }

        nextSlide() {
            var maxSlide = Math.max(0, this.cases.length - this.slidesToShow);
            if (this.currentSlide < maxSlide) {
                this.goToSlide(this.currentSlide + 1);
            } else if (this.autoplay) {
                this.goToSlide(0);
            }
        }

        prevSlide() {
            if (this.currentSlide > 0) {
                this.goToSlide(this.currentSlide - 1);
            }
        }

        startAutoplay() {
            var self = this;
            this.autoplayTimer = setInterval(function() {
                self.nextSlide();
            }, this.autoplaySpeed);
        }

        stopAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        }

        bindEvents() {
            var self = this;

            // Arrow clicks
            if (this.elPrev) {
                this.elPrev.addEventListener('click', function() {
                    self.prevSlide();
                    self.stopAutoplay();
                });
            }

            if (this.elNext) {
                this.elNext.addEventListener('click', function() {
                    self.nextSlide();
                    self.stopAutoplay();
                });
            }

            // Dot clicks
            if (this.elDots) {
                this.elDots.addEventListener('click', function(e) {
                    var dot = e.target.closest('.ekwa-bag-carousel-dot');
                    if (dot) {
                        self.goToSlide(parseInt(dot.dataset.dot));
                        self.stopAutoplay();
                    }
                });
            }

            // Slide clicks -> open modal
            this.elTrack.addEventListener('click', function(e) {
                var slide = e.target.closest('.ekwa-bag-carousel-slide');
                if (slide) {
                    var id = parseInt(slide.dataset.id);
                    self.openModal(id);
                }
            });

            // Modal events
            if (this.elModal) {
                var closeBtn = qs('.ekwa-bag-carousel-modal-close', this.elModal);
                var backdrop = qs('.ekwa-bag-carousel-modal-backdrop', this.elModal);
                var prevBtn = qs('.ekwa-bag-carousel-modal-nav-btn.prev', this.elModal);
                var nextBtn = qs('.ekwa-bag-carousel-modal-nav-btn.next', this.elModal);

                if (closeBtn) closeBtn.addEventListener('click', function() { self.closeModal(); });
                if (backdrop) backdrop.addEventListener('click', function() { self.closeModal(); });
                if (prevBtn) prevBtn.addEventListener('click', function() { self.navCase(-1); });
                if (nextBtn) nextBtn.addEventListener('click', function() { self.navCase(1); });
            }

            // Keyboard events
            document.addEventListener('keydown', function(e) {
                if (!self.elModal || !self.elModal.classList.contains('active')) return;
                if (e.key === 'Escape') self.closeModal();
                if (e.key === 'ArrowLeft') self.navCase(-1);
                if (e.key === 'ArrowRight') self.navCase(1);
            });

            // Resize handler - recalculate responsive breakpoints
            var resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    var prevSlidesToShow = self.slidesToShow;
                    self.updateSlidesToShow();

                    // If breakpoint changed, re-render dots
                    if (prevSlidesToShow !== self.slidesToShow) {
                        self.renderDots();
                    }

                    self.updateSlideWidths();
                }, 150);
            });

            // Touch/swipe support
            var startX = 0, moveX = 0, isDragging = false;
            this.elTrack.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isDragging = true;
                self.stopAutoplay();
            }, { passive: true });

            this.elTrack.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                moveX = e.touches[0].clientX - startX;
            }, { passive: true });

            this.elTrack.addEventListener('touchend', function() {
                if (!isDragging) return;
                isDragging = false;

                if (Math.abs(moveX) > 50) {
                    if (moveX < 0) {
                        self.nextSlide();
                    } else {
                        self.prevSlide();
                    }
                }
                moveX = 0;
            });
        }

        // ===== Modal Methods =====

        openModal(id) {
            this.currentCase = this.cases.find(function(c) { return c.id === id; });
            this.currentCaseIdx = this.cases.findIndex(function(c) { return c.id === id; });
            this.currentViewIdx = 0;

            if (!this.currentCase || !this.elModal) return;

            this.elModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            this.updateModal();
        }

        closeModal() {
            if (this.elModal) {
                this.elModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        updateModal() {
            var self = this;
            var c = this.currentCase;
            if (!c) return;

            var view = c.sets[this.currentViewIdx];
            var isCombined = view.isCombined || false;

            // Breadcrumb
            var mainCatLabel = '';
            var subCatLabel = '';
            if (this.categoryTree[c.mainCat]) {
                mainCatLabel = this.categoryTree[c.mainCat].label || '';
                var subCats = this.categoryTree[c.mainCat].subCats || [];
                var subObj = subCats.find(function(s) { return s.key === c.subCat; });
                subCatLabel = subObj ? subObj.label : '';
            }

            var breadcrumb = qs('.ekwa-bag-carousel-modal-breadcrumb', this.elModal);
            var title = qs('.ekwa-bag-carousel-modal-title', this.elModal);
            var desc = qs('.ekwa-bag-carousel-modal-desc', this.elModal);
            var images = qs('.ekwa-bag-carousel-modal-images', this.elModal);
            var beforeImg = qs('.ekwa-bag-carousel-modal-before', this.elModal);
            var afterImg = qs('.ekwa-bag-carousel-modal-after', this.elModal);
            var viewCount = qs('.ekwa-bag-carousel-modal-view-count', this.elModal);
            var currentNum = qs('.ekwa-bag-carousel-modal-current-num', this.elModal);
            var totalNum = qs('.ekwa-bag-carousel-modal-total-num', this.elModal);
            var thumbsEl = qs('.ekwa-bag-carousel-modal-thumbs', this.elModal);
            var prevBtn = qs('.ekwa-bag-carousel-modal-nav-btn.prev', this.elModal);
            var nextBtn = qs('.ekwa-bag-carousel-modal-nav-btn.next', this.elModal);

            if (breadcrumb) breadcrumb.textContent = mainCatLabel + (subCatLabel ? ' \u203A ' + subCatLabel : '');
            if (title) title.textContent = c.title;
            if (desc) desc.innerHTML = '<p>' + c.desc + '</p>';

            // Handle combined vs separate images
            if (isCombined) {
                images.classList.add('ekwa-bag-carousel-combined-modal');
                if (!this.showLabels) {
                    images.classList.add('ekwa-bag-carousel-no-labels');
                } else {
                    images.classList.remove('ekwa-bag-carousel-no-labels');
                }
                if (beforeImg) {
                    beforeImg.src = view.before;
                    beforeImg.alt = view.beforeAlt || 'Combined Before/After';
                }
            } else {
                images.classList.remove('ekwa-bag-carousel-combined-modal');
                if (!this.showLabels) {
                    images.classList.add('ekwa-bag-carousel-no-labels');
                } else {
                    images.classList.remove('ekwa-bag-carousel-no-labels');
                }
                if (beforeImg) {
                    beforeImg.src = view.before;
                    beforeImg.alt = view.beforeAlt || 'Before';
                }
                if (afterImg) {
                    afterImg.src = view.after;
                    afterImg.alt = view.afterAlt || 'After';
                }
            }

            if (viewCount) viewCount.textContent = c.sets.length;
            if (currentNum) currentNum.textContent = this.currentCaseIdx + 1;
            if (totalNum) totalNum.textContent = this.cases.length;

            // Thumbnails
            if (thumbsEl) {
                var thumbsHtml = '';
                c.sets.forEach(function(s, i) {
                    var thumbCombined = s.isCombined || false;
                    if (thumbCombined) {
                        thumbsHtml += '<div class="ekwa-bag-carousel-modal-thumb ekwa-bag-carousel-modal-combined-thumb' + (i === self.currentViewIdx ? ' active' : '') + '" data-idx="' + i + '">' +
                            '<img src="' + s.before + '" alt="' + (s.beforeAlt || 'View') + '">' +
                            '</div>';
                    } else {
                        thumbsHtml += '<div class="ekwa-bag-carousel-modal-thumb' + (i === self.currentViewIdx ? ' active' : '') + '" data-idx="' + i + '">' +
                            '<img src="' + s.before + '" alt="' + (s.beforeAlt || 'Before') + '">' +
                            '<img src="' + s.after + '" alt="' + (s.afterAlt || 'After') + '">' +
                            '</div>';
                    }
                });
                thumbsEl.innerHTML = thumbsHtml;

                // Bind thumbnail clicks
                qsa('.ekwa-bag-carousel-modal-thumb', thumbsEl).forEach(function(thumb) {
                    thumb.addEventListener('click', function() {
                        self.currentViewIdx = parseInt(this.dataset.idx);
                        self.updateModal();
                    });
                });
            }

            // Nav buttons
            if (prevBtn) prevBtn.disabled = this.currentCaseIdx === 0;
            if (nextBtn) nextBtn.disabled = this.currentCaseIdx === this.cases.length - 1;
        }

        navCase(dir) {
            if (dir === -1 && this.currentCaseIdx > 0) {
                this.currentCaseIdx--;
            } else if (dir === 1 && this.currentCaseIdx < this.cases.length - 1) {
                this.currentCaseIdx++;
            }

            this.currentCase = this.cases[this.currentCaseIdx];
            this.currentViewIdx = 0;
            this.updateModal();
        }
    }

    // Initialize all carousel instances when DOM is ready
    function initCarousels() {
        var wrappers = qsa('.ekwa-bag-carousel-wrapper');
        wrappers.forEach(function(wrapper) {
            new EKWACategoryCarousel(wrapper);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCarousels);
    } else {
        initCarousels();
    }

})();
