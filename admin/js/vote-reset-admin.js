// admin/js/vote-reset-admin.js
(function($) {
    'use strict';
    
    const VoteResetManager = {
        
        init: function() {
            this.bindEvents();
            this.checkDependencies();
        },
        
        checkDependencies: function() {
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is required for vote reset functionality');
                return false;
            }
            
            if (typeof mt_vote_reset_ajax === 'undefined') {
                console.error('Vote reset AJAX configuration is missing');
                return false;
            }
            
            return true;
        },
        
        bindEvents: function() {
            // Individual reset buttons
            $(document).on('click', '.mt-reset-vote-btn', this.handleIndividualReset.bind(this));
            
            // Bulk reset buttons
            $('#mt-bulk-reset-phase').on('click', this.handlePhaseReset.bind(this));
            $('#mt-bulk-reset-all').on('click', this.handleFullReset.bind(this));
            
            // Reset history
            $('#mt-view-reset-history').on('click', this.loadResetHistory.bind(this));
            
            // Refresh stats button
            $('#mt-refresh-stats').on('click', this.refreshStats.bind(this));
        },
        
        handleIndividualReset: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const candidateId = $button.data('candidate-id');
            const candidateName = $button.data('candidate-name') || 'this candidate';
            
            Swal.fire({
                title: mt_vote_reset_ajax.strings.confirm_reset_individual,
                html: `Are you sure you want to reset your vote for <strong>${candidateName}</strong>?<br><br>
                       <input id="reset-reason" class="swal2-input" placeholder="Reason for reset (optional)">`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reset it!',
                preConfirm: () => {
                    return document.getElementById('reset-reason').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.performIndividualReset(candidateId, result.value, $button);
                }
            });
        },
        
        performIndividualReset: function(candidateId, reason, $button) {
            $button.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: mt_vote_reset_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'mt_reset_individual_vote',
                    nonce: mt_vote_reset_ajax.nonce,
                    candidate_id: candidateId,
                    reason: reason
                }
            })
            .done((response) => {
                if (response.success) {
                    Swal.fire(
                        'Reset!',
                        response.data.message || mt_vote_reset_ajax.strings.reset_success,
                        'success'
                    );
                    
                    // Update UI
                    this.updateCandidateVoteDisplay(candidateId);
                    this.updateProgressIndicators();
                    
                    // Hide the reset button since vote is now reset
                    $button.closest('.mt-vote-reset-container').hide();
                    
                } else {
                    Swal.fire(
                        'Error!',
                        response.data || mt_vote_reset_ajax.strings.reset_error,
                        'error'
                    );
                }
            })
            .fail((xhr) => {
                Swal.fire(
                    'Error!',
                    xhr.responseJSON?.data || mt_vote_reset_ajax.strings.reset_error,
                    'error'
                );
            })
            .always(() => {
                $button.prop('disabled', false).removeClass('loading');
            });
        },
        
        handlePhaseReset: function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: mt_vote_reset_ajax.strings.confirm_reset_phase,
                html: `<p>This will reset all votes for the current phase.</p>
                       <p>Jury members will need to vote again.</p>
                       <hr>
                       <div class="text-left">
                         <label>
                           <input type="checkbox" id="notify-jury" checked> 
                           Send email notifications to jury members
                         </label>
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Proceed with Reset',
                width: '600px'
            }).then((result) => {
                if (result.isConfirmed) {
                    const notifyJury = document.getElementById('notify-jury').checked;
                    this.performPhaseReset(notifyJury);
                }
            });
        },
        
        performPhaseReset: function(notifyJury) {
            const $button = $('#mt-bulk-reset-phase');
            $button.prop('disabled', true).html('<i class="dashicons dashicons-update-alt"></i> Processing...');
            
            $.ajax({
                url: mt_vote_reset_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'mt_reset_phase_votes',
                    nonce: mt_vote_reset_ajax.nonce,
                    notify_jury: notifyJury ? 1 : 0
                }
            })
            .done((response) => {
                if (response.success) {
                    Swal.fire({
                        title: 'Phase Reset Complete!',
                        html: `<p><strong>${response.data.votes_reset}</strong> votes have been reset.</p>
                               ${response.data.notifications_sent ? `<p><strong>${response.data.notifications_sent}</strong> notifications sent.</p>` : ''}`,
                        icon: 'success'
                    }).then(() => {
                        // Reload page to reflect changes
                        window.location.reload();
                    });
                } else {
                    Swal.fire(
                        'Reset Failed!',
                        response.data || 'Failed to reset votes',
                        'error'
                    );
                }
            })
            .fail((xhr) => {
                Swal.fire(
                    'Reset Failed!',
                    xhr.responseJSON?.data || 'Failed to reset votes',
                    'error'
                );
            })
            .always(() => {
                $button.prop('disabled', false).html('Reset for Next Phase');
            });
        },
        
        handleFullReset: function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Full System Reset',
                html: `<div class="text-center" style="color: #dc3545;">
                       <p><strong>WARNING: This action cannot be undone!</strong></p>
                       <p>This will reset ALL votes and evaluations in the system.</p>
                       <hr>
                       <p>Type <strong>DELETE ALL</strong> to proceed:</p>
                       <input id="confirm-text" class="swal2-input" placeholder="Type DELETE ALL">
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Reset Everything',
                width: '600px',
                preConfirm: () => {
                    const confirmText = document.getElementById('confirm-text').value;
                    if (confirmText !== 'DELETE ALL') {
                        Swal.showValidationMessage('Please type "DELETE ALL" exactly');
                        return false;
                    }
                    return true;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.performFullReset();
                }
            });
        },
        
        performFullReset: function() {
            // Show progress modal
            Swal.fire({
                title: 'Resetting All Votes',
                html: '<div style="text-align: center;"><i class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite; font-size: 2em;"></i><br>Please wait...</div>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            $.ajax({
                url: mt_vote_reset_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'mt_reset_all_votes',
                    nonce: mt_vote_reset_ajax.nonce,
                    confirm: 'DELETE ALL'
                }
            })
            .done((response) => {
                if (response.success) {
                    Swal.fire({
                        title: 'System Reset Complete',
                        html: `<p>All votes have been reset.</p>
                               <p><strong>${response.data.votes_reset}</strong> votes removed.</p>
                               <p><strong>${response.data.evaluations_reset}</strong> evaluations removed.</p>`,
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire(
                        'Reset Failed!',
                        response.data || 'Failed to reset system',
                        'error'
                    );
                }
            })
            .fail((xhr) => {
                Swal.fire(
                    'Reset Failed!',
                    xhr.responseJSON?.data || 'Failed to reset system',
                    'error'
                );
            });
        },
        
        refreshStats: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            $button.prop('disabled', true);
            
            $.ajax({
                url: mt_vote_reset_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'mt_get_vote_stats',
                    nonce: mt_vote_reset_ajax.nonce
                }
            })
            .done((response) => {
                if (response.success) {
                    // Update stats display
                    $('.mt-total-votes').text(response.data.total_votes);
                    $('.mt-total-evaluations').text(response.data.total_evaluations);
                    $('.mt-active-jury').text(response.data.active_jury);
                }
            })
            .always(() => {
                $button.prop('disabled', false);
            });
        },
        
        updateCandidateVoteDisplay: function(candidateId) {
            // Update the candidate card to show vote has been reset
            const $candidateCard = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            $candidateCard.removeClass('evaluated').addClass('pending');
            $candidateCard.find('.status-badge').removeClass('evaluated').addClass('pending').text('Pending');
            $candidateCard.find('.mt-evaluation-score').text('Not evaluated');
        },
        
        updateProgressIndicators: function() {
            // Update progress indicators if they exist
            $.ajax({
                url: mt_vote_reset_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'mt_get_jury_progress',
                    nonce: mt_vote_reset_ajax.nonce
                }
            })
            .done((response) => {
                if (response.success) {
                    $('.mt-progress-bar').css('width', response.data.progress + '%');
                    $('.mt-progress-text').text(response.data.completed + ' / ' + response.data.total);
                    $('.mt-completion-percentage').text(response.data.progress + '%');
                }
            });
        },
        
        // Utility function to show loading state
        showLoading: function($element, text = 'Loading...') {
            $element.prop('disabled', true).data('original-text', $element.text()).text(text);
        },
        
        // Utility function to hide loading state
        hideLoading: function($element) {
            const originalText = $element.data('original-text') || 'Button';
            $element.prop('disabled', false).text(originalText);
        },
        
        loadResetHistory: function(e) {
            e.preventDefault();
            
            $('#reset-history-modal').modal('show');
            
            $.ajax({
                url: mt_ajax.rest_url + 'mobility-trailblazers/v1/reset-history',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
                }
            })
            .done(function(response) {
                if (response.success) {
                    VoteResetManager.renderResetHistory(response.data);
                }
            });
        },
        
        renderResetHistory: function(data) {
            let html = '<table class="table table-striped">';
            html += '<thead><tr>';
            html += '<th>Date/Time</th>';
            html += '<th>Type</th>';
            html += '<th>Initiated By</th>';
            html += '<th>Affected</th>';
            html += '<th>Votes</th>';
            html += '<th>Reason</th>';
            html += '</tr></thead><tbody>';
            
            data.forEach(function(log) {
                html += '<tr>';
                html += `<td>${new Date(log.reset_timestamp).toLocaleString()}</td>`;
                html += `<td><span class="badge badge-${VoteResetManager.getResetTypeBadge(log.reset_type)}">${log.reset_type}</span></td>`;
                html += `<td>${log.initiated_by_name || 'System'}</td>`;
                html += `<td>${VoteResetManager.getAffectedDescription(log)}</td>`;
                html += `<td>${log.votes_affected}</td>`;
                html += `<td>${log.reset_reason || '-'}</td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            $('#reset-history-content').html(html);
        },
        
        getResetTypeBadge: function(type) {
            const badges = {
                'individual': 'info',
                'bulk_phase_transition': 'warning',
                'bulk_all_user_votes': 'primary',
                'bulk_all_candidate_votes': 'secondary',
                'bulk_full_reset': 'danger'
            };
            return badges[type] || 'light';
        },
        
        getAffectedDescription: function(log) {
            if (log.affected_user_name && log.candidate_name) {
                return `${log.affected_user_name} - ${log.candidate_name}`;
            } else if (log.affected_user_name) {
                return `User: ${log.affected_user_name}`;
            } else if (log.candidate_name) {
                return `Candidate: ${log.candidate_name}`;
            } else if (log.voting_phase) {
                return `Phase: ${log.voting_phase}`;
            }
            return 'All votes';
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        VoteResetManager.init();
    });
    
    // Add CSS for loading animation
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .mt-reset-vote-btn.loading {
                opacity: 0.6;
                pointer-events: none;
            }
            .mt-vote-reset-container {
                margin-top: 10px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 4px;
                border-left: 3px solid #dc3545;
            }
            .mt-reset-vote-btn {
                background: #dc3545;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            }
            .mt-reset-vote-btn:hover {
                background: #c82333;
            }
        `)
        .appendTo('head');
    
})(jQuery);