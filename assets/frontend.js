/* Mobility Trailblazers Frontend JavaScript */

jQuery(document).ready(function($) {
    
    // Handle voting form submission
    $('.mt-vote-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $form.find('.mt-vote-message');
        var $submitButton = $form.find('.mt-submit-vote');
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        // Clear previous messages
        $message.empty();
        
        $.ajax({
            url: mt_frontend.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&action=mt_submit_vote',
            success: function(response) {
                if (response.success) {
                    $message.html(
                        '<div class="mt-success">' + response.data.message + '</div>'
                    );
                    // Reset form if needed
                    if (response.data.reset_form) {
                        $form[0].reset();
                    }
                } else {
                    $message.html(
                        '<div class="mt-error">' + response.data.message + '</div>'
                    );
                }
            },
            error: function() {
                $message.html(
                    '<div class="mt-error">' + mt_frontend.strings.error + '</div>'
                );
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
    
    // Public voting form submission
    $(document).on('submit', '#mt-public-vote-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var votingForm = form.closest('.mt-voting-form');
        var candidateId = votingForm.data('candidate-id');
        var voterEmail = form.find('#voter_email').val();
        var submitButton = form.find('button[type="submit"]');
        var messageContainer = votingForm.find('#mt-vote-message');
        
        // Validate email
        if (!isValidEmail(voterEmail)) {
            showMessage(messageContainer, 'Please enter a valid email address.', 'error');
            return;
        }
        
        // Show loading state
        submitButton.prop('disabled', true).text('Submitting...');
        votingForm.addClass('mt-loading');
        
        // Submit vote
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_submit_public_vote',
                candidate_id: candidateId,
                voter_email: voterEmail,
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(messageContainer, mt_ajax.strings.vote_success, 'success');
                    form.hide();
                    
                    // Update vote count if displayed
                    updateVoteCount(candidateId);
                } else {
                    var errorMessage = response.data.message || mt_ajax.strings.vote_error;
                    if (errorMessage.includes('already voted')) {
                        errorMessage = mt_ajax.strings.already_voted;
                    }
                    showMessage(messageContainer, errorMessage, 'error');
                }
            },
            error: function() {
                showMessage(messageContainer, mt_ajax.strings.vote_error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Submit Vote');
                votingForm.removeClass('mt-loading');
            }
        });
    });
    
    // Candidate filtering and search
    if ($('.mt-candidate-grid').length > 0) {
        initializeCandidateFiltering();
    }
    
    // Lazy loading for candidate images
    if ('IntersectionObserver' in window) {
        initializeLazyLoading();
    }
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });
    
    // Vote count animation
    animateNumbers('.mt-result-score');
    
    // Social sharing
    $('.mt-share-button').on('click', function(e) {
        e.preventDefault();
        var platform = $(this).data('platform');
        var url = encodeURIComponent(window.location.href);
        var title = encodeURIComponent(document.title);
        
        var shareUrl = getShareUrl(platform, url, title);
        if (shareUrl) {
            window.open(shareUrl, 'share', 'width=600,height=400');
        }
    });
    
    // Voting results auto-refresh
    if ($('.mt-voting-results').length > 0) {
        setInterval(refreshVotingResults, 60000); // Refresh every minute
    }
    
    // Candidate card interactions
    $('.mt-candidate-card').each(function() {
        addCardInteractions($(this));
    });
    
    // Jury member card interactions
    $('.mt-jury-card').each(function() {
        addCardInteractions($(this));
    });
    
    // Initialize tooltips
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Form validation
    $('.mt-voting-form input[type="email"]').on('blur', function() {
        var email = $(this).val();
        var isValid = isValidEmail(email);
        
        $(this).toggleClass('error', !isValid && email !== '');
        
        if (!isValid && email !== '') {
            $(this).siblings('.mt-error-message').remove();
            $(this).after('<div class="mt-error-message">Please enter a valid email address.</div>');
        } else {
            $(this).siblings('.mt-error-message').remove();
        }
    });
    
    // Keyboard navigation
    $(document).on('keydown', function(e) {
        // Arrow key navigation for candidate grid
        if ($('.mt-candidate-grid .focused').length > 0) {
            handleGridNavigation(e);
        }
    });
    
    // Focus management
    $('.mt-candidate-card, .mt-jury-card').on('focus', function() {
        $(this).addClass('focused');
    }).on('blur', function() {
        $(this).removeClass('focused');
    });
    
    // Initialize animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }
    
    // Mobile menu toggle (if applicable)
    $('.mt-mobile-menu-toggle').on('click', function() {
        $('.mt-mobile-menu').toggleClass('active');
        $(this).toggleClass('active');
    });
    
    // Cookie consent for voting (GDPR compliance)
    if (shouldShowCookieConsent()) {
        showCookieConsent();
    }
    
    // Functions
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showMessage(container, message, type) {
        container.empty();
        var messageDiv = $('<div class="mt-message ' + type + '">' + message + '</div>');
        container.append(messageDiv);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                messageDiv.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 100
        }, 300);
    }
    
    function updateVoteCount(candidateId) {
        // Update vote count display if present
        var voteCountElement = $('.mt-vote-count[data-candidate="' + candidateId + '"]');
        if (voteCountElement.length > 0) {
            var currentCount = parseInt(voteCountElement.text()) || 0;
            voteCountElement.text(currentCount + 1);
            animateNumbers(voteCountElement);
        }
    }
    
    function initializeCandidateFiltering() {
        // Add search input if not present
        if ($('.mt-candidate-search').length === 0) {
            var searchInput = $('<div class="mt-search-container"><input type="text" class="mt-candidate-search" placeholder="Search candidates..."><button class="mt-search-clear">×</button></div>');
            $('.mt-candidate-grid').before(searchInput);
        }
        
        // Add category filter if not present
        if ($('.mt-category-filter').length === 0) {
            var categoryFilter = $('<select class="mt-category-filter"><option value="">All Categories</option></select>');
            $('.mt-search-container').append(categoryFilter);
            
            // Populate categories
            var categories = new Set();
            $('.mt-candidate-card').each(function() {
                var category = $(this).data('category');
                if (category) {
                    categories.add(category);
                }
            });
            
            categories.forEach(function(category) {
                categoryFilter.append('<option value="' + category + '">' + category + '</option>');
            });
        }
        
        // Search functionality
        $('.mt-candidate-search').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            var selectedCategory = $('.mt-category-filter').val();
            filterCandidates(searchTerm, selectedCategory);
            
            // Show/hide clear button
            $('.mt-search-clear').toggle(searchTerm.length > 0);
        });
        
        // Clear search
        $('.mt-search-clear').on('click', function() {
            $('.mt-candidate-search').val('').trigger('input');
        });
        
        // Category filter
        $('.mt-category-filter').on('change', function() {
            var searchTerm = $('.mt-candidate-search').val().toLowerCase();
            var selectedCategory = $(this).val();
            filterCandidates(searchTerm, selectedCategory);
        });
    }
    
    function filterCandidates(searchTerm, selectedCategory) {
        var visibleCount = 0;
        
        $('.mt-candidate-card').each(function() {
            var card = $(this);
            var title = card.find('h3').text().toLowerCase();
            var company = card.find('.mt-company').text().toLowerCase();
            var position = card.find('.mt-position').text().toLowerCase();
            var category = card.data('category');
            
            var matchesSearch = searchTerm === '' || 
                title.includes(searchTerm) || 
                company.includes(searchTerm) || 
                position.includes(searchTerm);
            
            var matchesCategory = selectedCategory === '' || category === selectedCategory;
            
            var shouldShow = matchesSearch && matchesCategory;
            
            card.toggle(shouldShow);
            if (shouldShow) visibleCount++;
        });
        
        // Show no results message
        $('.mt-no-results').remove();
        if (visibleCount === 0) {
            $('.mt-candidate-grid').after('<div class="mt-no-results">No candidates found matching your criteria.</div>');
        }
    }
    
    function initializeLazyLoading() {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    function animateNumbers(selector) {
        $(selector).each(function() {
            var $this = $(this);
            var target = parseInt($this.text()) || 0;
            var current = 0;
            var increment = target / 20;
            
            var timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $this.text(Math.floor(current));
            }, 50);
        });
    }
    
    function getShareUrl(platform, url, title) {
        var shareUrls = {
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + url,
            twitter: 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title,
            linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + url,
            email: 'mailto:?subject=' + title + '&body=' + url
        };
        
        return shareUrls[platform];
    }
    
    function refreshVotingResults() {
        $('.mt-voting-results').each(function() {
            var resultsContainer = $(this);
            var type = resultsContainer.data('type') || 'public';
            var limit = resultsContainer.data('limit') || 10;
            
            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_voting_results',
                    type: type,
                    limit: limit,
                    nonce: mt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateVotingResults(resultsContainer, response.data);
                    }
                }
            });
        });
    }
    
    function updateVotingResults(container, results) {
        var list = container.find('.mt-results-list');
        list.empty();
        
        results.forEach(function(result, index) {
            var listItem = $('<li class="mt-result-item">' +
                '<span class="mt-candidate-name">' + result.candidate_name + '</span>' +
                '<span class="mt-result-score">' + result.score + '</span>' +
                '</li>');
            
            list.append(listItem);
        });
        
        // Re-animate numbers
        animateNumbers('.mt-result-score');
    }
    
    function addCardInteractions(card) {
        // Hover effects
        card.on('mouseenter', function() {
            $(this).addClass('hovered');
        }).on('mouseleave', function() {
            $(this).removeClass('hovered');
        });
        
        // Click to expand
        card.on('click', function(e) {
            if (!$(e.target).is('a, button, input')) {
                var link = $(this).find('.mt-read-more');
                if (link.length > 0) {
                    window.location.href = link.attr('href');
                }
            }
        });
        
        // Keyboard navigation
        card.attr('tabindex', '0').on('keydown', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).click();
            }
        });
    }
    
    function handleGridNavigation(e) {
        var focused = $('.mt-candidate-grid .focused');
        var cards = $('.mt-candidate-card:visible');
        var currentIndex = cards.index(focused);
        var newIndex = currentIndex;
        
        switch(e.which) {
            case 37: // Left arrow
                newIndex = Math.max(0, currentIndex - 1);
                break;
            case 39: // Right arrow
                newIndex = Math.min(cards.length - 1, currentIndex + 1);
                break;
            case 38: // Up arrow
                newIndex = Math.max(0, currentIndex - getColumnsCount());
                break;
            case 40: // Down arrow
                newIndex = Math.min(cards.length - 1, currentIndex + getColumnsCount());
                break;
        }
        
        if (newIndex !== currentIndex) {
            e.preventDefault();
            cards.eq(newIndex).focus();
        }
    }
    
    function getColumnsCount() {
        var gridWidth = $('.mt-candidate-grid').width();
        var cardWidth = $('.mt-candidate-card').outerWidth(true);
        return Math.floor(gridWidth / cardWidth);
    }
    
    function shouldShowCookieConsent() {
        return !localStorage.getItem('mt_cookie_consent') && $('.mt-voting-form').length > 0;
    }
    
    function showCookieConsent() {
        var consent = $('<div class="mt-cookie-consent">' +
            '<div class="mt-cookie-content">' +
            '<p>We use cookies to ensure you get the best experience on our website and to process your votes securely.</p>' +
            '<div class="mt-cookie-actions">' +
            '<button class="mt-cookie-accept">Accept</button>' +
            '<button class="mt-cookie-decline">Decline</button>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append(consent);
        
        $('.mt-cookie-accept').on('click', function() {
            localStorage.setItem('mt_cookie_consent', 'accepted');
            consent.fadeOut();
        });
        
        $('.mt-cookie-decline').on('click', function() {
            localStorage.setItem('mt_cookie_consent', 'declined');
            consent.fadeOut();
            $('.mt-voting-form').hide();
        });
    }
    
});

// Global functions for external use
window.MTFrontend = {
    // Submit vote programmatically
    submitVote: function(candidateId, voterEmail, callback) {
        jQuery.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_submit_public_vote',
                candidate_id: candidateId,
                voter_email: voterEmail,
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (callback) callback(response);
            }
        });
    },
    
    // Load candidates with filters
    loadCandidates: function(filters, callback) {
        jQuery.ajax({
            url: '/wp-json/mobility-trailblazers/v1/candidates',
            type: 'GET',
            data: filters,
            success: function(data) {
                if (callback) callback(data);
            }
        });
    },
    
    // Get voting results
    getResults: function(type, limit, callback) {
        jQuery.ajax({
            url: '/wp-json/mobility-trailblazers/v1/results',
            type: 'GET',
            data: {
                type: type,
                limit: limit
            },
            success: function(data) {
                if (callback) callback(data);
            }
        });
    },
    
    // Show notification
    showNotification: function(message, type) {
        var notification = jQuery('<div class="mt-notification mt-' + type + '">' + message + '</div>');
        jQuery('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 5000);
    }
};

// Service Worker registration for offline support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/wp-content/plugins/mobility-trailblazers/sw.js')
            .then(function(registration) {
                console.log('SW registered: ', registration);
            })
            .catch(function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
    });
}