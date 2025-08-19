/**
 * Candidate Interactions JavaScript
 * Enhanced interactive features for candidate profiles and grids
 * 
 * @package MobilityTrailblazers
 * @version 2.4.0
 */
(function($) {
    'use strict';
    // Initialize when DOM is ready
    $(document).ready(function() {
        initCandidateInteractions();
        initGridEnhancements();
        initProfileAnimations();
        initLazyLoading();
        initQuickView();
        initSmoothScroll();
    });
    /**
     * Initialize main candidate interactions
     */
    function initCandidateInteractions() {
        // Category filter with smooth transitions
        $('.mt-filter-btn').on('click', function(e) {
            e.preventDefault();
            const $this = $(this);
            const filter = $this.data('filter');
            // Update active state with animation
            $('.mt-filter-btn').removeClass('active');
            $this.addClass('active');
            // Filter cards with stagger animation
            filterCandidates(filter);
        });
        // Live search with debounce
        let searchTimer;
        $('#mt-candidate-search').on('input', function() {
            clearTimeout(searchTimer);
            const searchTerm = $(this).val().toLowerCase();
            searchTimer = setTimeout(function() {
                searchCandidates(searchTerm);
            }, 300);
        });
        // Card hover effects
        $('.mt-candidate-card').hover(
            function() {
                $(this).find('.mt-card-image').css('transform', 'scale(1.1)');
            },
            function() {
                $(this).find('.mt-card-image').css('transform', 'scale(1)');
            }
        );
    }
    /**
     * Filter candidates with animation
     */
    function filterCandidates(filter) {
        const $cards = $('.mt-candidate-card');
        let visibleCount = 0;
        $cards.each(function(index) {
            const $card = $(this);
            const shouldShow = filter === 'all' || $card.hasClass('category-' + filter);
            if (shouldShow) {
                setTimeout(function() {
                    $card.removeClass('hidden').fadeIn(300);
                }, visibleCount * 50);
                visibleCount++;
            } else {
                $card.fadeOut(300, function() {
                    $(this).addClass('hidden');
                });
            }
        });
        // Show no results message if needed
        if (visibleCount === 0) {
            showNoResults();
        } else {
            hideNoResults();
        }
    }
    /**
     * Search candidates with highlighting
     */
    function searchCandidates(searchTerm) {
        const $cards = $('.mt-candidate-card');
        let visibleCount = 0;
        $cards.each(function() {
            const $card = $(this);
            const name = $card.data('name') || '';
            const org = $card.data('org') || '';
            const position = $card.data('position') || '';
            if (name.includes(searchTerm) || 
                org.includes(searchTerm) || 
                position.includes(searchTerm)) {
                $card.removeClass('hidden').fadeIn(300);
                highlightSearchTerm($card, searchTerm);
                visibleCount++;
            } else {
                $card.fadeOut(300, function() {
                    $(this).addClass('hidden');
                });
            }
        });
        // Update results count
        updateResultsCount(visibleCount);
    }
    /**
     * Highlight search term in cards
     */
    function highlightSearchTerm($card, term) {
        if (!term) return;
        const $elements = $card.find('.mt-card-name, .mt-card-title');
        $elements.each(function() {
            const $el = $(this);
            const text = $el.text();
            const regex = new RegExp('(' + term + ')', 'gi');
            const highlighted = text.replace(regex, '<mark>$1</mark>');
            $el.html(highlighted);
        });
    }
    /**
     * Initialize grid enhancements
     */
    function initGridEnhancements() {
        // Load more functionality
        let page = 1;
        const perPage = 12;
        $('#mt-load-more').on('click', function() {
            const $button = $(this);
            $button.addClass('loading');
            // Simulate AJAX load (replace with actual AJAX)
            setTimeout(function() {
                loadMoreCandidates(page, perPage);
                page++;
                $button.removeClass('loading');
            }, 800);
        });
        // Grid/List view toggle
        $('.mt-view-toggle').on('click', function() {
            const view = $(this).data('view');
            $('.mt-view-toggle').removeClass('active');
            $(this).addClass('active');
            if (view === 'grid') {
                $('.mt-candidates-enhanced-grid').removeClass('list-view');
            } else {
                $('.mt-candidates-enhanced-grid').addClass('list-view');
            }
        });
        // Sort functionality
        $('#mt-sort-select').on('change', function() {
            const sortBy = $(this).val();
            sortCandidates(sortBy);
        });
    }
    /**
     * Initialize profile animations
     */
    function initProfileAnimations() {
        // Parallax effect on hero section
        $(window).on('scroll', function() {
            const scrollTop = $(window).scrollTop();
            $('.mt-hero-pattern').css('transform', 'translateY(' + (scrollTop * 0.5) + 'px)');
            $('.mt-profile-header-enhanced').css('transform', 'translateY(' + (scrollTop * 0.2) + 'px)');
        });
        // Animate criteria cards on scroll
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    $(entry.target).addClass('animated');
                }
            });
        }, { threshold: 0.1 });
        $('.mt-criterion-card').each(function() {
            observer.observe(this);
        });
        // Progress bars animation
        $('.mt-progress-bar').each(function() {
            const $bar = $(this);
            const value = $bar.data('value');
            const progressObserver = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    $bar.css('width', value + '%');
                    progressObserver.unobserve($bar[0]);
                }
            });
            progressObserver.observe(this);
        });
    }
    /**
     * Initialize lazy loading for images
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const $img = $(entry.target);
                        const src = $img.data('src');
                        if (src) {
                            $img.attr('src', src).removeAttr('data-src');
                            $img.on('load', function() {
                                $(this).addClass('loaded');
                            });
                            imageObserver.unobserve(entry.target);
                        }
                    }
                });
            });
            $('img[data-src]').each(function() {
                imageObserver.observe(this);
            });
        }
    }
    /**
     * Initialize quick view modal
     */
    function initQuickView() {
        // Create modal structure if not exists
        if (!$('#mt-quick-view-modal').length) {
            $('body').append(`
                <div id="mt-quick-view-modal" class="mt-modal">
                    <div class="mt-modal-overlay"></div>
                    <div class="mt-modal-content">
                        <button class="mt-modal-close">&times;</button>
                        <div class="mt-modal-body"></div>
                    </div>
                </div>
            `);
        }
        // Quick view button click
        $('.mt-quick-view-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const candidateId = $(this).data('candidate-id');
            openQuickView(candidateId);
        });
        // Close modal
        $('.mt-modal-close, .mt-modal-overlay').on('click', function() {
            closeQuickView();
        });
        // ESC key to close
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape') {
                closeQuickView();
            }
        });
    }
    /**
     * Open quick view modal
     */
    function openQuickView(candidateId) {
        const $modal = $('#mt-quick-view-modal');
        const $modalBody = $modal.find('.mt-modal-body');
        // Show loading
        $modalBody.html('<div class="mt-loading-spinner"></div>');
        $modal.addClass('active');
        // Load candidate data via AJAX
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_get_candidate_quick_view',
                candidate_id: candidateId,
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $modalBody.html(response.data.html);
                }
            },
            error: function() {
                $modalBody.html('<p>' + (mt_ajax.i18n.load_error || 'Error loading candidate information.') + '</p>');
            }
        });
    }
    /**
     * Close quick view modal
     */
    function closeQuickView() {
        const $modal = $('#mt-quick-view-modal');
        $modal.removeClass('active');
        setTimeout(function() {
            $modal.find('.mt-modal-body').empty();
        }, 300);
    }
    /**
     * Initialize smooth scroll
     */
    function initSmoothScroll() {
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });
    }
    /**
     * Sort candidates
     */
    function sortCandidates(sortBy) {
        const $container = $('.mt-candidates-enhanced-grid');
        const $cards = $container.find('.mt-candidate-card').toArray();
        $cards.sort(function(a, b) {
            const aValue = $(a).data(sortBy) || '';
            const bValue = $(b).data(sortBy) || '';
            if (sortBy === 'name') {
                return aValue.localeCompare(bValue);
            } else if (sortBy === 'date') {
                return new Date(bValue) - new Date(aValue);
            }
            return 0;
        });
        // Animate reordering
        $cards.forEach(function(card, index) {
            setTimeout(function() {
                $(card).css('order', index);
            }, index * 30);
        });
    }
    /**
     * Load more candidates (placeholder)
     */
    function loadMoreCandidates(page, perPage) {
        // This would typically be an AJAX call
        // Pagination parameters: page and perPage
    }
    /**
     * Show no results message
     */
    function showNoResults() {
        if (!$('.mt-no-results').length) {
            $('.mt-candidates-enhanced-grid').append(`
                <div class="mt-no-results">
                    <div class="mt-no-results-icon">🔍</div>
                    <div class="mt-no-results-text">Keine Kandidaten gefunden</div>
                    <div class="mt-no-results-hint">Versuchen Sie, Ihre Filterkriterien anzupassen</div>
                </div>
            `);
        }
        $('.mt-no-results').fadeIn();
    }
    /**
     * Hide no results message
     */
    function hideNoResults() {
        $('.mt-no-results').fadeOut(function() {
            $(this).remove();
        });
    }
    /**
     * Update results count
     */
    function updateResultsCount(count) {
        if (!$('.mt-results-count').length) {
            $('.mt-grid-controls').append('<div class="mt-results-count"></div>');
        }
        $('.mt-results-count').text(count + ' Kandidaten gefunden');
    }
    /**
     * Keyboard navigation
     */
    $(document).on('keydown', function(e) {
        // Arrow key navigation for grid
        if ($('.mt-candidates-enhanced-grid').length) {
            const $focusedCard = $('.mt-candidate-card:focus');
            if ($focusedCard.length) {
                let $nextCard;
                switch(e.key) {
                    case 'ArrowRight':
                        $nextCard = $focusedCard.next('.mt-candidate-card:not(.hidden)');
                        break;
                    case 'ArrowLeft':
                        $nextCard = $focusedCard.prev('.mt-candidate-card:not(.hidden)');
                        break;
                    case 'Enter':
                        window.location.href = $focusedCard.find('.mt-card-link').attr('href');
                        break;
                }
                if ($nextCard && $nextCard.length) {
                    e.preventDefault();
                    $nextCard.focus();
                }
            }
        }
    });
})(jQuery);
