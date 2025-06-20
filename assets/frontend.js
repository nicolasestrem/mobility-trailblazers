/**
 * Frontend JavaScript
 * 
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';

    // Frontend Manager Object
    window.MTFrontend = {
        // Initialize
        init: function() {
            this.bindEvents();
            this.initializeGrids();
            this.initializeVoting();
            this.initializeRegistration();
        },
        
        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Candidate grid filtering
            $('.mt-filter-button').on('click', function() {
                var filter = $(this).data('filter');
                self.filterCandidates(filter);
                
                // Update active state
                $('.mt-filter-button').removeClass('active');
                $(this).addClass('active');
            });
            
            // Search functionality
            $('#mt-candidate-search').on('input', function() {
                self.searchCandidates($(this).val());
            });
            
            // Load more candidates
            $('#mt-load-more').on('click', function() {
                self.loadMoreCandidates();
            });
            
            // Candidate modal
            $(document).on('click', '.mt-candidate-card', function() {
                var candidateId = $(this).data('candidate-id');
                self.showCandidateModal(candidateId);
            });
            
            // Close modal
            $('.mt-modal-close, .mt-modal-overlay').on('click', function() {
                self.closeModal();
            });
            
            // Public voting
            $(document).on('click', '.mt-vote-button', function() {
                var candidateId = $(this).data('candidate-id');
                self.submitVote(candidateId);
            });
            
            // Registration form
            $('#mt-registration-form').on('submit', function(e) {
                e.preventDefault();
                self.submitRegistration();
            });
            
            // Newsletter signup
            $('#mt-newsletter-form').on('submit', function(e) {
                e.preventDefault();
                self.submitNewsletter();
            });
        },
        
        // Initialize grids
        initializeGrids: function() {
            // Masonry layout for candidate grid
            if ($('.mt-candidates-grid').length) {
                $('.mt-candidates-grid').masonry({
                    itemSelector: '.mt-candidate-card',
                    columnWidth: '.grid-sizer',
                    percentPosition: true,
                    gutter: 20
                });
            }
            
            // Lazy loading for images
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var image = entry.target;
                            image.src = image.dataset.src;
                            image.classList.add('loaded');
                            imageObserver.unobserve(image);
                        }
                    });
                });
                
                document.querySelectorAll('.lazy-load').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },
        
        // Initialize voting
        initializeVoting: function() {
            // Check if user has already voted
            var votedCandidates = this.getVotedCandidates();
            votedCandidates.forEach(function(candidateId) {
                $('.mt-vote-button[data-candidate-id="' + candidateId + '"]')
                    .prop('disabled', true)
                    .text(mt_frontend.i18n.already_voted);
            });
            
            // Update vote counts
            this.updateVoteCounts();
        },
        
        // Initialize registration
        initializeRegistration: function() {
            // Form validation
            this.setupFormValidation();
            
            // File upload preview
            $('#mt-innovation-file').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $('#file-name').text(fileName || mt_frontend.i18n.no_file_selected);
            });
        },
        
        // Filter candidates
        filterCandidates: function(filter) {
            var $grid = $('.mt-candidates-grid');
            
            if (filter === 'all') {
                $('.mt-candidate-card').show();
            } else {
                $('.mt-candidate-card').each(function() {
                    var categories = $(this).data('categories') || '';
                    if (categories.includes(filter)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            // Re-layout masonry
            if ($grid.data('masonry')) {
                $grid.masonry('layout');
            }
        },
        
        // Search candidates
        searchCandidates: function(searchTerm) {
            var term = searchTerm.toLowerCase();
            var $grid = $('.mt-candidates-grid');
            
            $('.mt-candidate-card').each(function() {
                var name = $(this).find('.mt-candidate-name').text().toLowerCase();
                var company = $(this).find('.mt-candidate-company').text().toLowerCase();
                var innovation = $(this).find('.mt-candidate-innovation').text().toLowerCase();
                
                if (name.includes(term) || company.includes(term) || innovation.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Re-layout masonry
            if ($grid.data('masonry')) {
                $grid.masonry('layout');
            }
        },
        
        // Load more candidates
        loadMoreCandidates: function() {
            var self = this;
            var $button = $('#mt-load-more');
            var page = parseInt($button.data('page')) || 1;
            
            $.ajax({
                url: mt_frontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_load_more_candidates',
                    page: page + 1,
                    nonce: mt_frontend.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text(mt_frontend.i18n.loading);
                },
                success: function(response) {
                    if (response.success) {
                        var $grid = $('.mt-candidates-grid');
                        var $newItems = $(response.data.html);
                        
                        $grid.append($newItems);
                        
                        // Re-initialize masonry
                        if ($grid.data('masonry')) {
                            $grid.masonry('appended', $newItems);
                        }
                        
                        // Update page number
                        $button.data('page', page + 1);
                        
                        // Hide button if no more candidates
                        if (!response.data.has_more) {
                            $button.hide();
                        }
                    }
                },
                complete: function() {
                    $button.prop('disabled', false).text(mt_frontend.i18n.load_more);
                }
            });
        },
        
        // Show candidate modal
        showCandidateModal: function(candidateId) {
            var self = this;
            
            $.ajax({
                url: mt_frontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_get_candidate_details',
                    candidateId: candidateId,
                    nonce: mt_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#mt-modal-content').html(response.data.html);
                        $('#mt-candidate-modal').addClass('show');
                        $('body').addClass('modal-open');
                    }
                }
            });
        },
        
        // Close modal
        closeModal: function() {
            $('.mt-modal').removeClass('show');
            $('body').removeClass('modal-open');
        },
        
        // Submit vote
        submitVote: function(candidateId) {
            var self = this;
            var $button = $('.mt-vote-button[data-candidate-id="' + candidateId + '"]');
            
            $.ajax({
                url: mt_frontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_submit_public_vote',
                    candidateId: candidateId,
                    nonce: mt_frontend.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text(mt_frontend.i18n.voting);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(mt_frontend.i18n.vote_submitted, 'success');
                        
                        // Update button
                        $button.text(mt_frontend.i18n.already_voted);
                        
                        // Save to local storage
                        self.saveVotedCandidate(candidateId);
                        
                        // Update vote count
                        var $voteCount = $button.siblings('.mt-vote-count');
                        var currentCount = parseInt($voteCount.text()) || 0;
                        $voteCount.text(currentCount + 1);
                    } else {
                        self.showNotification(response.data.message || mt_frontend.i18n.vote_error, 'error');
                        $button.prop('disabled', false).text(mt_frontend.i18n.vote);
                    }
                }
            });
        },
        
        // Submit registration
        submitRegistration: function() {
            var self = this;
            var $form = $('#mt-registration-form');
            
            if (!this.validateRegistrationForm()) {
                return;
            }
            
            var formData = new FormData($form[0]);
            formData.append('action', 'mt_submit_registration');
            formData.append('nonce', mt_frontend.nonce);
            
            $.ajax({
                url: mt_frontend.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $form.find('button[type="submit"]').prop('disabled', true).text(mt_frontend.i18n.submitting);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(mt_frontend.i18n.registration_success, 'success');
                        $form[0].reset();
                        
                        // Show success message
                        $('#registration-success').show();
                        $form.hide();
                    } else {
                        self.showNotification(response.data.message || mt_frontend.i18n.registration_error, 'error');
                    }
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).text(mt_frontend.i18n.submit);
                }
            });
        },
        
        // Submit newsletter
        submitNewsletter: function() {
            var self = this;
            var $form = $('#mt-newsletter-form');
            var email = $form.find('input[type="email"]').val();
            
            $.ajax({
                url: mt_frontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_subscribe_newsletter',
                    email: email,
                    nonce: mt_frontend.nonce
                },
                beforeSend: function() {
                    $form.find('button').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(mt_frontend.i18n.newsletter_success, 'success');
                        $form[0].reset();
                    } else {
                        self.showNotification(response.data.message || mt_frontend.i18n.newsletter_error, 'error');
                    }
                },
                complete: function() {
                    $form.find('button').prop('disabled', false);
                }
            });
        },
        
        // Validate registration form
        validateRegistrationForm: function() {
            var isValid = true;
            var $form = $('#mt-registration-form');
            
            // Required fields
            $form.find('[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    isValid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            // Email validation
            var email = $form.find('input[type="email"]').val();
            if (email && !this.isValidEmail(email)) {
                $form.find('input[type="email"]').addClass('error');
                isValid = false;
            }
            
            // File size validation
            var fileInput = $form.find('input[type="file"]')[0];
            if (fileInput && fileInput.files[0]) {
                var fileSize = fileInput.files[0].size / 1024 / 1024; // MB
                if (fileSize > 10) {
                    this.showNotification(mt_frontend.i18n.file_too_large, 'error');
                    isValid = false;
                }
            }
            
            return isValid;
        },
        
        // Setup form validation
        setupFormValidation: function() {
            // Real-time validation
            $('input[required], textarea[required]').on('blur', function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            $('input[type="email"]').on('blur', function() {
                if ($(this).val() && !MTFrontend.isValidEmail($(this).val())) {
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
        },
        
        // Email validation
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        // Get voted candidates from local storage
        getVotedCandidates: function() {
            var voted = localStorage.getItem('mt_voted_candidates');
            return voted ? JSON.parse(voted) : [];
        },
        
        // Save voted candidate to local storage
        saveVotedCandidate: function(candidateId) {
            var voted = this.getVotedCandidates();
            if (!voted.includes(candidateId)) {
                voted.push(candidateId);
                localStorage.setItem('mt_voted_candidates', JSON.stringify(voted));
            }
        },
        
        // Update vote counts
        updateVoteCounts: function() {
            $('.mt-vote-count').each(function() {
                var candidateId = $(this).data('candidate-id');
                var self = this;
                
                $.ajax({
                    url: mt_frontend.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mt_get_vote_count',
                        candidateId: candidateId,
                        nonce: mt_frontend.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $(self).text(response.data.count);
                        }
                    }
                });
            });
        },
        
        // Show notification
        showNotification: function(message, type) {
            var notification = $('<div class="mt-notification ' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MTFrontend.init();
    });
    
})(jQuery); 