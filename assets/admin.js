/* Mobility Trailblazers Admin JavaScript - FIXED VERSION */

jQuery(document).ready(function($) {
    
    // Evaluation form submission
    $('#mt-evaluation-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        
        // Collect form data
        var formData = {
            action: 'mt_submit_vote',
            nonce: mt_ajax.nonce || $('#mt_nonce').val(),
            candidate_id: form.find('input[name="candidate_id"]').val(),
            courage_score: form.find('input[name="courage_score"]:checked').val(),
            innovation_score: form.find('input[name="innovation_score"]:checked').val(),
            implementation_score: form.find('input[name="implementation_score"]:checked').val(),
            relevance_score: form.find('input[name="relevance_score"]:checked').val() || form.find('input[name="mobility_relevance_score"]:checked').val(),
            visibility_score: form.find('input[name="visibility_score"]:checked').val(),
            comments: form.find('textarea[name="comments"]').val()
        };
        
        // Disable button during submission
        submitBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: mt_ajax.ajax_url || ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload to show updated dashboard
                    window.location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Submit Evaluation');
            }
        });
    });
    
    // Real-time score calculation
    $('#mt-evaluation-form select').on('change', function() {
        calculateTotalScore();
    });
    
    function calculateTotalScore() {
        var total = 0;
        $('#mt-evaluation-form select').each(function() {
            total += parseInt($(this).val()) || 0;
        });
        
        $('.mt-total-score').text(total + '/50');
    }
    
    function updateTotalScore(score) {
        if ($('.mt-total-score').length === 0) {
            $('#mt-evaluation-form').append('<p><strong>Total Score: <span class="mt-total-score">' + score + '/50</span></strong></p>');
        } else {
            $('.mt-total-score').text(score + '/50');
        }
    }
    
    function showMessage(message, type) {
        var messageDiv = $('<div class="mt-message ' + type + '">' + message + '</div>');
        $('#mt-evaluation-form').prepend(messageDiv);
        
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Bulk actions for candidates
    $('.bulkactions select').on('change', function() {
        var action = $(this).val();
        if (action.startsWith('mt_')) {
            handleBulkAction(action);
        }
    });
    
    function handleBulkAction(action) {
        var checkedItems = $('.check-column input[type="checkbox"]:checked');
        if (checkedItems.length === 0) {
            alert('Please select at least one candidate.');
            return;
        }
        
        var candidateIds = [];
        checkedItems.each(function() {
            if ($(this).val() !== 'on') {
                candidateIds.push($(this).val());
            }
        });
        
        if (candidateIds.length === 0) {
            alert('Please select at least one candidate.');
            return;
        }
        
        var confirmMessage = '';
        switch(action) {
            case 'mt_move_to_shortlist':
                confirmMessage = 'Move selected candidates to shortlist?';
                break;
            case 'mt_move_to_finalist':
                confirmMessage = 'Move selected candidates to finalist status?';
                break;
            case 'mt_mark_winner':
                confirmMessage = 'Mark selected candidates as winners?';
                break;
            default:
                return;
        }
        
        if (confirm(confirmMessage)) {
            performBulkAction(action, candidateIds);
        }
    }
    
    function performBulkAction(action, candidateIds) {
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_bulk_action',
                bulk_action: action,
                candidate_ids: candidateIds,
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error performing bulk action: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error performing bulk action.');
            }
        });
    }
    
    // Dashboard statistics auto-refresh
    if ($('.mt-dashboard-stats').length > 0) {
        setInterval(refreshDashboardStats, 300000); // Refresh every 5 minutes
    }
    
    function refreshDashboardStats() {
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_get_stats',
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStats(response.data);
                }
            }
        });
    }
    
    function updateStats(stats) {
        $('.mt-stat-box').each(function() {
            var statType = $(this).data('stat-type');
            if (stats[statType]) {
                $(this).find('.mt-stat-number').text(stats[statType]);
            }
        });
    }
    
    // Export functionality
    $('.mt-export-btn').on('click', function(e) {
        e.preventDefault();
        
        var exportType = $(this).data('export-type');
        var url = ajaxurl + '?action=mt_export_' + exportType + '&nonce=' + mt_ajax.nonce;
        
        // Create hidden link and trigger download
        var link = $('<a>');
        link.attr('href', url);
        link.attr('download', '');
        link[0].click();
    });
    
    // Candidate search and filtering
    $('#mt-candidate-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterCandidates(searchTerm);
    });
    
    $('#mt-category-filter, #mt-status-filter').on('change', function() {
        var searchTerm = $('#mt-candidate-search').val().toLowerCase();
        filterCandidates(searchTerm);
    });
    
    function filterCandidates(searchTerm) {
        var categoryFilter = $('#mt-category-filter').val();
        var statusFilter = $('#mt-status-filter').val();
        
        $('.mt-candidate-evaluation-card').each(function() {
            var card = $(this);
            var title = card.find('h3').text().toLowerCase();
            var company = card.find('p').first().text().toLowerCase();
            
            var matchesSearch = searchTerm === '' || title.includes(searchTerm) || company.includes(searchTerm);
            var matchesCategory = categoryFilter === '' || card.data('category') === categoryFilter;
            var matchesStatus = statusFilter === '' || card.data('status') === statusFilter;
            
            if (matchesSearch && matchesCategory && matchesStatus) {
                card.show();
            } else {
                card.hide();
            }
        });
    }
    
    // Jury member management
    $('#mt_jury_is_president').on('change', function() {
        if ($(this).is(':checked')) {
            $('#mt_jury_is_vice_president').prop('checked', false);
        }
    });
    
    $('#mt_jury_is_vice_president').on('change', function() {
        if ($(this).is(':checked')) {
            $('#mt_jury_is_president').prop('checked', false);
        }
    });
    
    // Image upload for candidates and jury
    $('.mt-upload-image').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var input = button.siblings('input');
        var preview = button.siblings('.mt-image-preview');
        
        // Check if wp.media is available
        if (typeof wp !== 'undefined' && wp.media) {
            var mediaUploader = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                input.val(attachment.url);
                preview.html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto;">');
            });
            
            mediaUploader.open();
        } else {
            alert('WordPress media uploader not available. Please upload images through Media Library.');
        }
    });
    
    // Voting phase management
    $('#mt-current-phase').on('change', function() {
        var phase = $(this).val();
        updatePhaseSettings(phase);
    });
    
    function updatePhaseSettings(phase) {
        var votingEnabled = $('#voting_enabled');
        var publicVotingEnabled = $('#public_voting_enabled');
        
        switch(phase) {
            case 'jury_evaluation':
                votingEnabled.prop('checked', true);
                publicVotingEnabled.prop('checked', false);
                break;
            case 'public_voting':
                votingEnabled.prop('checked', true);
                publicVotingEnabled.prop('checked', true);
                break;
            case 'final_selection':
            case 'award_ceremony':
            case 'post_award':
                votingEnabled.prop('checked', false);
                publicVotingEnabled.prop('checked', false);
                break;
        }
    }
    
    // Auto-save evaluations
    var autoSaveTimer;
    $('#mt-evaluation-form select').on('change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            if (confirm('Auto-save evaluation?')) {
                $('#submit-evaluation').click();
            }
        }, 30000); // Auto-save after 30 seconds of inactivity
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save evaluation
        if (e.ctrlKey && e.which === 83) {
            e.preventDefault();
            $('#submit-evaluation').click();
        }
        
        // Ctrl+E to go to evaluation page
        if (e.ctrlKey && e.which === 69) {
            e.preventDefault();
            window.location.href = adminPageUrl('mt-jury-evaluation');
        }
    });
    
    function adminPageUrl(page) {
        return ajaxurl.replace('admin-ajax.php', 'admin.php?page=' + page);
    }
    
    // Initialize tooltips - FIXED VERSION
    // Check if jQuery UI tooltip is available before trying to use it
    if ($.fn.tooltip && typeof $.fn.tooltip === 'function') {
        $('.mt-tooltip').tooltip();
    } else {
        // Fallback: Use native browser tooltips via title attribute
        $('.mt-tooltip').each(function() {
            var tooltipText = $(this).data('tooltip') || $(this).attr('data-tooltip');
            if (tooltipText) {
                $(this).attr('title', tooltipText);
            }
        });
    }
    
    // Form validation
    $('form').on('submit', function(e) {
        var form = $(this);
        var requiredFields = form.find('[required]');
        var isValid = true;
        
        requiredFields.each(function() {
            var field = $(this);
            if (field.val().trim() === '') {
                field.addClass('error');
                isValid = false;
            } else {
                field.removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Dynamic form fields
    $('.mt-add-field').on('click', function() {
        var template = $(this).data('template');
        var container = $(this).siblings('.mt-dynamic-fields');
        var newField = $(template).clone();
        
        // Update field names and IDs
        var index = container.children().length;
        newField.find('input, select, textarea').each(function() {
            var field = $(this);
            var name = field.attr('name');
            var id = field.attr('id');
            
            if (name) {
                field.attr('name', name.replace('[0]', '[' + index + ']'));
            }
            if (id) {
                field.attr('id', id.replace('_0', '_' + index));
            }
        });
        
        container.append(newField);
    });
    
    $(document).on('click', '.mt-remove-field', function() {
        $(this).closest('.mt-field-group').remove();
    });
    
});

// Global functions
window.MTAdmin = {
    showNotification: function(message, type) {
        var notification = jQuery('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        jQuery('.wrap h1').after(notification);
        
        setTimeout(function() {
            notification.fadeOut();
        }, 5000);
    },
    
    confirmAction: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
    loadCandidates: function(filters, callback) {
        jQuery.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_get_candidates',
                filters: filters,
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (response.success && callback) {
                    callback(response.data);
                }
            }
        });
    }
};