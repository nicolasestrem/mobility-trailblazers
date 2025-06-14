// admin/js/vote-reset-admin.js
(function($) {
    'use strict';
    
    const VoteResetManager = {
        
        init: function() {
            this.bindEvents();
            this.initializeModals();
        },
        
        bindEvents: function() {
            // Individual reset buttons
            $(document).on('click', '.mt-reset-vote-btn', this.handleIndividualReset);
            
            // Bulk reset buttons
            $('#mt-bulk-reset-phase').on('click', this.handlePhaseReset);
            $('#mt-bulk-reset-all').on('click', this.handleFullReset);
            
            // Reset history
            $('#mt-view-reset-history').on('click', this.loadResetHistory);
        },
        
        handleIndividualReset: function(e) {
            e.preventDefault();
            
            const candidateId = $(this).data('candidate-id');
            const candidateName = $(this).data('candidate-name');
            
            Swal.fire({
                title: 'Reset Vote?',
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
                    this.performIndividualReset(candidateId, result.value);
                }
            });
        },
        
        performIndividualReset: function(candidateId, reason) {
            const $button = $(`.mt-reset-vote-btn[data-candidate-id="${candidateId}"]`);
            $button.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: mt_ajax.rest_url + 'mobility-trailblazers/v1/reset-vote',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
                },
                data: {
                    candidate_id: candidateId,
                    reason: reason
                }
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire(
                        'Reset!',
                        'Your vote has been reset.',
                        'success'
                    );
                    
                    // Update UI
                    $(`.mt-vote-display[data-candidate-id="${candidateId}"]`)
                        .removeClass('voted')
                        .find('.scores').html('Not yet voted');
                    
                    // Update progress indicators
                    VoteResetManager.updateProgressIndicators();
                }
            })
            .fail(function(xhr) {
                Swal.fire(
                    'Error!',
                    xhr.responseJSON?.message || 'Failed to reset vote',
                    'error'
                );
            })
            .always(function() {
                $button.prop('disabled', false).removeClass('loading');
            });
        },
        
        handlePhaseReset: function(e) {
            e.preventDefault();
            
            const currentPhase = $('#current-voting-phase').val();
            const nextPhase = $('#next-voting-phase').val();
            
            Swal.fire({
                title: 'Phase Transition Reset',
                html: `<p>This will reset all votes from the <strong>${currentPhase}</strong> phase.</p>
                       <p>Jury members will need to vote again for the <strong>${nextPhase}</strong> phase.</p>
                       <hr>
                       <div class="text-left">
                         <label>
                           <input type="checkbox" id="notify-jury"> 
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
                    this.performPhaseReset(currentPhase, nextPhase, notifyJury);
                }
            });
        },
        
        performPhaseReset: function(fromPhase, toPhase, notifyJury) {
            const $button = $('#mt-bulk-reset-phase');
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            
            $.ajax({
                url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
                },
                data: {
                    reset_scope: 'phase_transition',
                    options: {
                        from_phase: fromPhase,
                        to_phase: toPhase,
                        notify_jury: notifyJury,
                        reason: `Phase transition from ${fromPhase} to ${toPhase}`
                    }
                }
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Phase Reset Complete!',
                        html: `<p><strong>${response.votes_reset}</strong> votes have been reset.</p>
                               <p><strong>${response.backup_count}</strong> votes backed up.</p>`,
                        icon: 'success'
                    }).then(() => {
                        // Reload page to reflect changes
                        window.location.reload();
                    });
                }
            })
            .fail(function(xhr) {
                Swal.fire(
                    'Reset Failed!',
                    xhr.responseJSON?.message || 'Failed to reset votes',
                    'error'
                );
            })
            .always(function() {
                $button.prop('disabled', false).html('Reset Phase Votes');
            });
        },
        
        handleFullReset: function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Full System Reset',
                html: `<div class="text-center text-danger">
                       <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                       <p><strong>WARNING: This action cannot be undone!</strong></p>
                       <p>This will reset ALL votes in the system.</p>
                       <hr>
                       <p>Type <strong>CONFIRM RESET</strong> to proceed:</p>
                       <input id="confirm-text" class="swal2-input" placeholder="Type confirmation text">
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Reset Everything',
                width: '600px',
                preConfirm: () => {
                    const confirmText = document.getElementById('confirm-text').value;
                    if (confirmText !== 'CONFIRM RESET') {
                        Swal.showValidationMessage('Please type the confirmation text exactly');
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
                html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            $.ajax({
                url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
                },
                data: {
                    reset_scope: 'full_reset',
                    options: {
                        confirm: true,
                        reason: 'Full system reset initiated by admin'
                    }
                }
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'System Reset Complete',
                        html: `<p>All votes have been reset.</p>
                               <p><strong>${response.votes_reset}</strong> votes removed.</p>
                               <p><strong>${response.backup_count}</strong> votes backed up.</p>`,
                        icon: 'success'
                    }).then(() => {
                        window.location.href = mt_ajax.admin_url + 'admin.php?page=mobility-trailblazers';
                    });
                }
            })
            .fail(function(xhr) {
                Swal.fire(
                    'Reset Failed!',
                    xhr.responseJSON?.message || 'Failed to reset system',
                    'error'
                );
            });
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
        },
        
        updateProgressIndicators: function() {
            // Update jury member's progress bar
            $.get(mt_ajax.rest_url + 'mobility-trailblazers/v1/jury-progress', function(data) {
                if (data.success) {
                    $('.mt-progress-bar').css('width', data.progress + '%');
                    $('.mt-progress-text').text(data.completed + ' / ' + data.total);
                }
            });
        }
    };
    
    $(document).ready(function() {
        VoteResetManager.init();
    });
    
})(jQuery);