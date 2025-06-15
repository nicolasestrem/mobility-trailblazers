// admin/js/vote-reset-admin.js - Fixed Version Without Bootstrap Modals
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
        
        initializeModals: function() {
            // Create modal HTML for history - Simple div overlay instead of Bootstrap modal
            const modalHtml = `
                <div id="reset-history-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; width: 90%; max-width: 1000px; max-height: 80vh; overflow: auto; padding: 20px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0;">Reset History</h2>
                            <button id="close-reset-history" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                        </div>
                        <div id="reset-history-content">
                            <div class="mt-loading"><span class="spinner is-active"></span><p>Loading...</p></div>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            
            // Handle close button
            $(document).on('click', '#close-reset-history, #reset-history-modal', function(e) {
                if (e.target.id === 'close-reset-history' || e.target.id === 'reset-history-modal') {
                    $('#reset-history-modal').fadeOut();
                }
            });
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
                url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/reset-vote',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
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
                url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
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
                url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
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
                    window.location.href = mt_vote_reset_ajax.admin_url + 'admin.php?page=mobility-trailblazers';
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
            
            $('#reset-history-modal').fadeIn();
            
            $.ajax({
                url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/reset-history',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
                }
            })
            .done(function(response) {
                if (response.success) {
                    VoteResetManager.renderResetHistory(response.data);
                }
            });
        },

        renderResetHistory: function(data) {
            let html = '<div class="reset-history-table">';
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>Date</th>';
            html += '<th>Type</th>';
            html += '<th>User</th>';
            html += '<th>Candidate</th>';
            html += '<th>Reason</th>';
            html += '</tr></thead><tbody>';
            
            if (data && data.length > 0) {
                data.forEach(function(reset) {
                    html += '<tr>';
                    html += `<td>${new Date(reset.reset_at).toLocaleString()}</td>`;
                    html += `<td>${reset.reset_type}</td>`;
                    html += `<td>${reset.user_name || 'System'}</td>`;
                    html += `<td>${reset.candidate_name || 'All'}</td>`;
                    html += `<td>${reset.reason || '-'}</td>`;
                    html += '</tr>';
                });
            } else {
                html += '<tr><td colspan="5" style="text-align: center;">No reset history found</td></tr>';
            }
            
            html += '</tbody></table></div>';
            $('#reset-history-content').html(html);
        },

        updateProgressIndicators: function() {
            // Update any progress indicators on the page
            location.reload(); // Simple approach - reload to get updated data
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        VoteResetManager.init();
        
        // Backup button handlers
        $('#mt-create-backup').on('click', handleCreateBackup);
        $('#mt-export-backups').on('click', handleExportBackups);
        $('#mt-view-backups').on('click', handleViewBackups);
    });

    // Export for global access
    window.VoteResetManager = VoteResetManager;

})(jQuery);

// Backup and Recovery Functions
function handleCreateBackup(e) {
    e.preventDefault();
    
    const reason = prompt('Enter reason for backup (optional):') || 'Manual backup';
    performFullBackup(reason);
}

function performFullBackup(reason) {
    const $button = $('#mt-create-backup');
    $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Creating Backup...');
    
    $.ajax({
        url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/admin/create-backup',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
        },
        data: {
            reason: reason,
            type: 'full'
        }
    })
    .done(function(response) {
        if (response.success) {
            alert(`Backup Created Successfully!\n\n${response.data.votes_backed_up} votes backed up\n${response.data.scores_backed_up} evaluation scores backed up\n\nTimestamp: ${response.data.timestamp}`);
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
        action: mt_vote_reset_ajax.ajax_url
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
        value: mt_vote_reset_ajax.nonce
    }));
    
    jQuery('body').append(form);
    form.submit();
    form.remove();
}

function handleViewBackups(e) {
    e.preventDefault();
    
    // Create backup history overlay
    showBackupHistoryOverlay();
    
    jQuery.ajax({
        url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/backup-history',
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
        }
    })
    .done(function(response) {
        if (response.success) {
            showBackupHistoryTable(response);
        }
    })
    .fail(function(xhr) {
        jQuery('#backup-history-content').html('<p class="error">Failed to load backup history</p>');
    });
}

function showBackupHistoryOverlay() {
    const overlayHtml = `
        <div id="backup-history-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9998; display: flex; align-items: center; justify-content: center;">
            <div style="background: white; width: 90%; max-width: 1200px; max-height: 80vh; overflow: auto; padding: 20px; border-radius: 8px; position: relative;">
                <h2 style="margin-top: 0;">Backup History</h2>
                <button id="close-backup-history" style="position: absolute; top: 20px; right: 20px; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
                <div id="backup-history-content">
                    <div class="mt-loading"><span class="spinner is-active"></span><p>Loading backup history...</p></div>
                </div>
            </div>
        </div>
    `;
    
    jQuery('body').append(overlayHtml);
    
    // Handle close button
    jQuery('#close-backup-history, #backup-history-overlay').on('click', function(e) {
        if (e.target.id === 'backup-history-overlay' || e.target.id === 'close-backup-history') {
            jQuery('#backup-history-overlay').remove();
        }
    });
}

function showBackupHistoryTable(data) {
    let html = `
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Created</th>
                    <th>Created By</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>`;
    
    if (data.backups && data.backups.length > 0) {
        data.backups.forEach(backup => {
            const date = new Date(backup.created_at).toLocaleString();
            const statusBadge = backup.restored_at ? 
                '<span style="color: green; font-weight: bold;">Restored</span>' : 
                '<span style="color: blue; font-weight: bold;">Available</span>';
            
            html += `
                <tr>
                    <td>${backup.id}</td>
                    <td>${backup.type === 'vote' ? 'Individual Vote' : 'Candidate Score'}</td>
                    <td>${date}</td>
                    <td>${backup.created_by_name || 'System'}</td>
                    <td>${backup.reason || '-'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        ${!backup.restored_at ? 
                            `<button class="button button-small restore-backup" 
                                data-backup-id="${backup.id}" 
                                data-backup-type="${backup.type}">
                                Restore
                            </button>` : 
                            '-'
                        }
                    </td>
                </tr>`;
        });
    } else {
        html += '<tr><td colspan="7" style="text-align: center;">No backups found</td></tr>';
    }
    
    html += `
            </tbody>
        </table>`;
    
    jQuery('#backup-history-content').html(html);
    
    // Handle restore clicks
    jQuery('.restore-backup').on('click', function() {
        const backupId = jQuery(this).data('backup-id');
        const backupType = jQuery(this).data('backup-type');
        
        if (confirm('Are you sure you want to restore from this backup?\n\nThis will replace current data with the backup data.')) {
            performRestore(backupId, backupType);
        }
    });
}

function performRestore(backupId, backupType) {
    jQuery.ajax({
        url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/admin/restore-backup',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
        },
        data: {
            backup_id: backupId,
            backup_type: backupType
        }
    })
    .done(function(response) {
        if (response.success) {
            alert('Success! The backup has been restored.');
            jQuery('#backup-history-overlay').remove();
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
        url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
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
        url: mt_vote_reset_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
        method: 'POST',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', mt_vote_reset_ajax.nonce);
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