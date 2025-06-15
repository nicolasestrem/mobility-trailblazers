// admin/js/vote-reset-admin.js - Browser Alerts Version
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
            
            // Browser confirm dialog
            const message = `Are you sure you want to reset your vote for ${candidateName}?\n\nClick OK to reset or Cancel to keep your vote.`;
            
            if (confirm(message)) {
                // Browser prompt for reason
                const reason = prompt('Enter reason for reset (optional):') || '';
                VoteResetManager.performIndividualReset(candidateId, reason);
            }
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
                    alert('Success! Your vote has been reset.');
                    
                    // Update UI
                    $(`.mt-vote-display[data-candidate-id="${candidateId}"]`)
                        .removeClass('voted')
                        .find('.scores').html('Not yet voted');
                    
                    // Update progress indicators
                    VoteResetManager.updateProgressIndicators();
                }
            })
            .fail(function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Failed to reset vote'));
            })
            .always(function() {
                $button.prop('disabled', false).removeClass('loading');
            });
        },
        
        handlePhaseReset: function(e) {
            e.preventDefault();
            
            const currentPhase = $('#current-voting-phase').val();
            const nextPhase = $('#next-voting-phase').val();
            
            let message = `PHASE TRANSITION RESET\n\n`;
            message += `This will reset all votes from the ${currentPhase} phase.\n`;
            message += `Jury members will need to vote again for the ${nextPhase} phase.\n\n`;
            message += `Do you want to proceed?`;
            
            if (confirm(message)) {
                const notifyJury = confirm('Send email notifications to jury members?');
                VoteResetManager.performPhaseReset(currentPhase, nextPhase, notifyJury);
            }
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
                    alert(`Phase Reset Complete!\n\n${response.votes_reset} votes have been reset.\n${response.backup_count} votes backed up.`);
                    // Reload page to reflect changes
                    window.location.reload();
                }
            })
            .fail(function(xhr) {
                alert('Reset Failed: ' + (xhr.responseJSON?.message || 'Failed to reset votes'));
            })
            .always(function() {
                $button.prop('disabled', false).html('Reset Phase Votes');
            });
        },
        
        handleFullReset: function(e) {
            e.preventDefault();
            
            // Triple confirmation for full reset
            if (!confirm('WARNING: Full System Reset\n\nThis will reset ALL votes in the system.\n\nAre you sure you want to continue?')) {
                return;
            }
            
            if (!confirm('This action CANNOT be undone!\n\nAll voting data will be permanently removed.\n\nAre you REALLY sure?')) {
                return;
            }
            
            const confirmText = prompt('Type "CONFIRM RESET" to proceed:');
            if (confirmText !== 'CONFIRM RESET') {
                alert('Reset cancelled. The confirmation text was not entered correctly.');
                return;
            }
            
            VoteResetManager.performFullReset();
        },
        
        performFullReset: function() {
            // Show loading message
            const $button = $('#mt-bulk-reset-all');
            $button.prop('disabled', true).html('Resetting All Votes...');
            
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
                    alert(`System Reset Complete\n\nAll votes have been reset.\n${response.votes_reset} votes removed.\n${response.backup_count} votes backed up.`);
                    window.location.href = mt_ajax.admin_url + 'admin.php?page=mobility-trailblazers';
                }
            })
            .fail(function(xhr) {
                alert('Reset Failed: ' + (xhr.responseJSON?.message || 'Failed to reset system'));
            })
            .always(function() {
                $button.prop('disabled', false).html('Reset All Votes');
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
        },
        
        initializeModals: function() {
            // Initialize any modal-related code here
        }
    };
    
    $(document).ready(function() {
        VoteResetManager.init();
        
        // Enable/disable buttons based on selection
        $('#reset-by-user').on('change', function() {
            $('.mt-reset-user-votes').prop('disabled', !$(this).val());
        });
        
        $('#reset-by-candidate').on('change', function() {
            $('.mt-reset-candidate-votes').prop('disabled', !$(this).val());
        });
        
        // Close modal
        $('.mt-modal-close, .mt-modal-overlay').on('click', function() {
            $('#reset-history-modal').fadeOut();
        });
        
        // Backup button handlers
        $('#mt-create-backup').on('click', handleCreateBackup);
        $('#mt-export-backups').on('click', handleExportBackups);
        $('#mt-view-backups').on('click', handleViewBackups);
        
        // Targeted reset buttons
        $('.mt-reset-user-votes').on('click', function() {
            const userId = $('#reset-by-user').val();
            const userName = $('#reset-by-user option:selected').text();
            
            if (userId && confirm(`Reset all votes for ${userName}?\n\nThis action cannot be undone.`)) {
                performUserReset(userId);
            }
        });
        
        $('.mt-reset-candidate-votes').on('click', function() {
            const candidateId = $('#reset-by-candidate').val();
            const candidateName = $('#reset-by-candidate option:selected').text();
            
            if (candidateId && confirm(`Reset all votes for ${candidateName}?\n\nThis action cannot be undone.`)) {
                performCandidateReset(candidateId);
            }
        });
    });
    
})(jQuery);

// Backup Management Functions using Browser Alerts
function handleCreateBackup(e) {
    e.preventDefault();
    
    if (confirm('Create a full backup of all current voting data?\n\nThis may take a moment.')) {
        const reason = prompt('Enter backup reason (optional):') || 'Manual backup';
        performFullBackup(reason);
    }
}

function performFullBackup(reason) {
    const $button = jQuery('#mt-create-backup');
    $button.prop('disabled', true).html('<span class="spinner is-active" style="float: none;"></span> Creating backup...');
    
    jQuery.ajax({
        url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/create-backup',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
        },
        data: {
            reason: reason,
            type: 'full'
        }
    })
    .done(function(response) {
        if (response.success) {
            alert(`Backup Created Successfully!\n\n${response.votes_backed_up} votes backed up\n${response.scores_backed_up} evaluation scores backed up\n\nTotal storage size: ${response.storage_size}`);
            location.reload(); // Refresh to show updated stats
        }
    })
    .fail(function(xhr) {
        alert('Backup Failed: ' + (xhr.responseJSON?.message || 'Failed to create backup'));
    })
    .always(function() {
        $button.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Create Full Backup Now');
    });
}

function handleExportBackups(e) {
    e.preventDefault();
    
    const format = prompt('Enter export format (json or csv):', 'json');
    
    if (format && (format === 'json' || format === 'csv')) {
        exportBackupHistory(format);
    } else if (format) {
        alert('Invalid format. Please enter either "json" or "csv".');
    }
}

function exportBackupHistory(format) {
    // Create a form and submit it to trigger download
    const form = jQuery('<form>', {
        method: 'POST',
        action: mt_ajax.ajax_url
    });
    
    form.append(jQuery('<input>', {
        type: 'hidden',
        name: 'action',
        value: 'mt_export_backup_history'
    }));
    
    form.append(jQuery('<input>', {
        type: 'hidden',
        name: 'format',
        value: format
    }));
    
    form.append(jQuery('<input>', {
        type: 'hidden',
        name: 'nonce',
        value: mt_ajax.nonce
    }));
    
    jQuery('body').append(form);
    form.submit();
    form.remove();
}

function handleViewBackups(e) {
    e.preventDefault();
    
    jQuery('#reset-history-content').html('<div class="mt-loading"><span class="spinner is-active"></span><p>Loading backup history...</p></div>');
    jQuery('#reset-history-modal').fadeIn();
    
    jQuery.ajax({
        url: mt_ajax.rest_url + 'mobility-trailblazers/v1/backup-history',
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
        }
    })
    .done(function(response) {
        if (response.success) {
            showBackupHistoryTable(response.data);
        }
    })
    .fail(function(xhr) {
        jQuery('#reset-history-content').html('<p class="error">Failed to load backup history</p>');
    });
}

function showBackupHistoryTable(data) {
    let html = '<h3>Backup History</h3>';
    html += '<table class="wp-list-table widefat fixed striped">';
    html += '<thead><tr>';
    html += '<th>Date</th>';
    html += '<th>Type</th>';
    html += '<th>Items</th>';
    html += '<th>Reason</th>';
    html += '<th>Action</th>';
    html += '</tr></thead><tbody>';
    
    if (data.backups && data.backups.length > 0) {
        data.backups.forEach(function(backup) {
            html += '<tr>';
            html += `<td>${new Date(backup.backed_up_at).toLocaleString()}</td>`;
            html += `<td>${backup.type}</td>`;
            html += `<td>${backup.items_count}</td>`;
            html += `<td>${backup.backup_reason || '-'}</td>`;
            html += `<td>`;
            if (!backup.restored_at) {
                html += `<button class="button button-small restore-backup" data-id="${backup.history_id}" data-type="${backup.type}">Restore</button>`;
            } else {
                html += `<span class="restored">Restored ${new Date(backup.restored_at).toLocaleDateString()}</span>`;
            }
            html += `</td>`;
            html += '</tr>';
        });
    } else {
        html += '<tr><td colspan="5" style="text-align: center;">No backups found</td></tr>';
    }
    
    html += '</tbody></table>';
    
    jQuery('#reset-history-content').html(html);
    
    // Add restore handlers
    jQuery('.restore-backup').on('click', function() {
        const backupId = jQuery(this).data('id');
        const backupType = jQuery(this).data('type');
        
        if (confirm(`Restore this backup?\n\nWarning: Current data for the affected vote(s) will be replaced.\n\nContinue?`)) {
            performRestore(backupId, backupType);
        }
    });
}

function performRestore(backupId, backupType) {
    jQuery.ajax({
        url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/restore-backup',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
        },
        data: {
            backup_id: backupId,
            type: backupType
        }
    })
    .done(function(response) {
        if (response.success) {
            alert('Success! The backup has been restored.');
            location.reload();
        }
    })
    .fail(function(xhr) {
        alert('Restore Failed: ' + (xhr.responseJSON?.message || 'Failed to restore backup'));
    });
}

// Targeted reset functions
function performUserReset(userId) {
    jQuery.ajax({
        url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
        },
        data: {
            reset_scope: 'all_user_votes',
            options: {
                user_id: userId,
                reason: 'Admin reset all votes for user'
            }
        }
    })
    .done(function(response) {
        if (response.success) {
            alert(`User votes reset successfully!\n\n${response.votes_reset} votes have been reset.`);
            location.reload();
        }
    })
    .fail(function(xhr) {
        alert('Reset Failed: ' + (xhr.responseJSON?.message || 'Failed to reset user votes'));
    });
}

function performCandidateReset(candidateId) {
    jQuery.ajax({
        url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
        },
        data: {
            reset_scope: 'all_candidate_votes',
            options: {
                candidate_id: candidateId,
                reason: 'Admin reset all votes for candidate'
            }
        }
    })
    .done(function(response) {
        if (response.success) {
            alert(`Candidate votes reset successfully!\n\n${response.votes_reset} votes have been reset.`);
            location.reload();
        }
    })
    .fail(function(xhr) {
        alert('Reset Failed: ' + (xhr.responseJSON?.message || 'Failed to reset candidate votes'));
    });
}