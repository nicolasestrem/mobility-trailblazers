/**
 * Elementor Compatibility Script
 *
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';
    
    // Wait for Elementor to be ready
    $(window).on('elementor/frontend/init', function() {
        
        // Re-initialize MT scripts after Elementor renders widgets
        elementorFrontend.hooks.addAction('frontend/element_ready/mt-candidates-grid.default', function($scope) {
            initCandidatesGrid($scope);
        });
        
        elementorFrontend.hooks.addAction('frontend/element_ready/mt-voting-form.default', function($scope) {
            initVotingForm($scope);
        });
        
        elementorFrontend.hooks.addAction('frontend/element_ready/mt-jury-dashboard.default', function($scope) {
            initJuryDashboard($scope);
        });
        
        elementorFrontend.hooks.addAction('frontend/element_ready/mt-registration-form.default', function($scope) {
            initRegistrationForm($scope);
        });
        
        elementorFrontend.hooks.addAction('frontend/element_ready/mt-evaluation-stats.default', function($scope) {
            initEvaluationStats($scope);
        });
    });
    
    /**
     * Initialize Candidates Grid functionality
     */
    function initCandidatesGrid($scope) {
        var $grid = $scope.find('.mt-candidates-grid');
        
        if (!$grid.length) return;
        
        // Filter functionality
        $scope.find('.mt-filter-button').on('click', function() {
            var filter = $(this).data('filter');
            
            $(this).addClass('active').siblings().removeClass('active');
            
            if (filter === 'all') {
                $grid.find('.mt-candidate-card').show();
            } else {
                $grid.find('.mt-candidate-card').each(function() {
                    var categories = $(this).data('categories');
                    if (categories && categories.indexOf(filter) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        
        // Search functionality
        $scope.find('#mt-candidate-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            $grid.find('.mt-candidate-card').each(function() {
                var $card = $(this);
                var text = $card.text().toLowerCase();
                
                if (text.indexOf(searchTerm) !== -1) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
        });
        
        // Vote button functionality
        $scope.find('.mt-vote-button').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var candidateId = $button.data('candidate-id');
            
            if (!candidateId) return;
            
            $button.prop('disabled', true).text(mt_elementor.voting_text || 'Voting...');
            
            $.ajax({
                url: mt_elementor.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_submit_vote',
                    candidate_id: candidateId,
                    nonce: mt_elementor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.text(mt_elementor.voted_text || 'Voted!');
                        
                        // Update vote count if displayed
                        var $voteCount = $scope.find('.mt-vote-count[data-candidate-id="' + candidateId + '"]');
                        if ($voteCount.length && response.data.vote_count) {
                            $voteCount.text(response.data.vote_count);
                        }
                    } else {
                        alert(response.data.message || 'Voting failed. Please try again.');
                        $button.prop('disabled', false).text(mt_elementor.vote_text || 'Vote');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text(mt_elementor.vote_text || 'Vote');
                }
            });
        });
    }
    
    /**
     * Initialize Voting Form functionality
     */
    function initVotingForm($scope) {
        // Category filter
        $scope.find('.mt-category-filter').on('click', function() {
            var category = $(this).data('category');
            
            $(this).addClass('active').siblings().removeClass('active');
            
            if (category === 'all') {
                $scope.find('.mt-voting-candidate').show();
            } else {
                $scope.find('.mt-voting-candidate').each(function() {
                    var categories = $(this).data('categories');
                    if (categories && categories.indexOf(category) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        
        // Search functionality
        $scope.find('#mt-voting-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            $scope.find('.mt-voting-candidate').each(function() {
                var $candidate = $(this);
                var text = $candidate.text().toLowerCase();
                
                if (text.indexOf(searchTerm) !== -1) {
                    $candidate.show();
                } else {
                    $candidate.hide();
                }
            });
        });
        
        // Load more functionality
        $scope.find('#mt-load-more-voting').on('click', function() {
            var $button = $(this);
            var page = parseInt($button.data('page')) + 1;
            
            $button.prop('disabled', true).text('Loading...');
            
            $.ajax({
                url: mt_elementor.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_load_more_candidates',
                    page: page,
                    nonce: mt_elementor.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $scope.find('#mt-voting-candidates').append(response.data.html);
                        $button.data('page', page);
                        
                        if (!response.data.has_more) {
                            $button.hide();
                        } else {
                            $button.prop('disabled', false).text('Load More Candidates');
                        }
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Load More Candidates');
                }
            });
        });
    }
    
    /**
     * Initialize Jury Dashboard functionality
     */
    function initJuryDashboard($scope) {
        if (typeof MTJuryDashboard !== 'undefined') {
            MTJuryDashboard.init();
        }
    }
    
    /**
     * Initialize Registration Form functionality
     */
    function initRegistrationForm($scope) {
        var $form = $scope.find('#mt-registration-form');
        
        if (!$form.length) return;
        
        $form.on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'mt_submit_registration');
            formData.append('nonce', mt_elementor.nonce);
            
            var $submitButton = $form.find('button[type="submit"]');
            $submitButton.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: mt_elementor.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $form.hide();
                        $scope.find('#registration-success').show();
                    } else {
                        alert(response.data.message || 'Registration failed. Please try again.');
                        $submitButton.prop('disabled', false).text('Submit Registration');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $submitButton.prop('disabled', false).text('Submit Registration');
                }
            });
        });
    }
    
    /**
     * Initialize Evaluation Stats functionality
     */
    function initEvaluationStats($scope) {
        // Initialize Chart.js charts if present
        if (typeof Chart !== 'undefined') {
            $scope.find('canvas').each(function() {
                var ctx = this.getContext('2d');
                var chartId = $(this).attr('id');
                
                // Charts are initialized inline in the template
                // This is just a placeholder for any additional functionality
            });
        }
    }
    
})(jQuery); 