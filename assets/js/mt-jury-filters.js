/**
 * Jury Dashboard Filters
 * 
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';
    
    console.log('MT Jury Filters Script Loaded');
    
    $(document).ready(function() {
        console.log('MT Jury Filters - jQuery Ready');
        
        // Filter candidates based on search, status, and category
        function filterDashboardCandidates() {
            var searchTerm = $('#mt-candidate-search').val().toLowerCase().trim();
            var statusFilter = $('#mt-status-filter').val();
            var categoryFilter = $('#mt-category-filter').val() || 'all';
            var visibleCount = 0;
            var totalCandidates = $('.mt-candidate-card').length;
            
            console.log('Filter called - Search:', searchTerm, 'Status:', statusFilter, 'Category:', categoryFilter, 'Total cards:', totalCandidates);
            
            $('.mt-candidate-card').each(function() {
                var $card = $(this);
                var name = ($card.data('name') || '').toString().toLowerCase();
                var status = ($card.data('status') || 'draft').toString();
                var category = ($card.data('category') || '').toString();
                
                // Check search match
                var cardTitle = $card.find('.mt-candidate-name').text().toLowerCase();
                var cardOrg = $card.find('.mt-candidate-org').text().toLowerCase();
                var matchesSearch = searchTerm === '' || 
                                  name.indexOf(searchTerm) !== -1 || 
                                  cardTitle.indexOf(searchTerm) !== -1 ||
                                  cardOrg.indexOf(searchTerm) !== -1;
                
                // Check status match
                var normalizedStatus = status.toLowerCase().trim();
                var normalizedFilter = statusFilter.toLowerCase().trim();
                var matchesStatus = statusFilter === '' || normalizedStatus === normalizedFilter;
                
                // Check category match
                var matchesCategory = categoryFilter === 'all' || category === categoryFilter;
                
                if (matchesSearch && matchesStatus && matchesCategory) {
                    $card.show().removeClass('hidden');
                    visibleCount++;
                } else {
                    $card.hide().addClass('hidden');
                }
            });
            
            console.log('Filter complete - Visible:', visibleCount);
            
            // Show/hide no results message
            if (visibleCount === 0 && (searchTerm !== '' || statusFilter !== '' || categoryFilter !== 'all')) {
                showNoResults();
            } else {
                hideNoResults();
            }
        }
        
        // Show no results message
        function showNoResults() {
            if (!$('.mt-no-results-message').length) {
                $('.mt-candidates-list').append(
                    '<div class="mt-no-results-message mt-notice">' +
                    '<p>Keine Kandidaten entsprechen Ihren Suchkriterien.</p>' +
                    '</div>'
                );
            }
            $('.mt-no-results-message').show();
        }
        
        // Hide no results message
        function hideNoResults() {
            $('.mt-no-results-message').hide();
        }
        
        // Search functionality with debounce
        let searchTimer;
        $('#mt-candidate-search').on('input', function() {
            console.log('Search input changed:', $(this).val());
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                filterDashboardCandidates();
            }, 300);
        });
        
        // Status filter
        $('#mt-status-filter').on('change', function(e) {
            e.preventDefault();
            console.log('Status filter changed to:', $(this).val());
            filterDashboardCandidates();
        });
        
        // Category filter dropdown
        $('#mt-category-filter').on('change', function(e) {
            e.preventDefault();
            console.log('Category filter changed to:', $(this).val());
            filterDashboardCandidates();
        });
        
        // Evaluation button click
        $('.mt-evaluate-btn').on('click', function(e) {
            e.preventDefault();
            var candidateId = $(this).data('candidate-id');
            window.location.href = '?evaluate=' + candidateId;
        });
        
        console.log('MT Jury Filters - Event handlers attached');
    });
    
})(jQuery);