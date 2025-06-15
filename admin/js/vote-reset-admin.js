// admin/js/vote-reset-admin.js - Fixed with Consistent Variable Names and Debugging
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
                url: mt_ajax.rest_url + 'mobility-trailblazers/v1/reset-vote',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
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
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
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
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Resetting All Votes...');
            
            // Prepare the data payload
            const requestData = {
                reset_scope: 'full_reset',
                options: {
                    confirm: true,  // THIS IS REQUIRED
                    reason: 'Full system reset initiated by admin'
                }
            };
            
            console.log('Sending full reset request with data:', requestData);
            
            $.ajax({
                url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
                method: 'POST',
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
                    console.log('Setting nonce header:', mt_ajax.rest_nonce || mt_ajax.nonce);
                },
                data: JSON.stringify(requestData)
            })
            .done(function(response) {
                console.log('Full reset response:', response);
                if (response.success) {
                    alert(`System Reset Complete\n\nAll votes have been reset.\n${response.votes_reset} votes removed.\n${response.backup_count} votes backed up.`);
                    window.location.href = mt_ajax.admin_url + 'admin.php?page=mobility-trailblazers';
                } else {
                    alert('Reset Failed: ' + (response.message || 'Unknown error'));
                }
            })
            .fail(function(xhr) {
                console.error('Full reset error:', xhr.responseJSON || xhr.statusText);
                console.error('Full XHR object:', xhr);
                alert('Reset Failed: ' + (xhr.responseJSON?.message || 'Failed to reset system'));
            })
            .always(function() {
                $button.prop('disabled', false).html(originalText);
            });
        },
        
        loadResetHistory: function(e) {
            e.preventDefault();
            
            $('#reset-history-modal').fadeIn();
            
            $.ajax({
                url: mt_ajax.rest_url + 'mobility-trailblazers/v1/reset-history',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
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

    // Backup and Recovery Functions - ALL INSIDE JQUERY SCOPE
    function handleCreateBackup(e) {
        e.preventDefault();
        
        const reason = prompt('Enter reason for backup (optional):') || 'Manual backup';
        performFullBackup(reason);
    }

    function performFullBackup(reason) {
        console.log('performFullBackup called with reason:', reason);
        console.log('mt_ajax object:', mt_ajax);
        
        // Check if rest_url is defined
        if (!mt_ajax || !mt_ajax.rest_url) {
            console.error('REST API URL not configured:', mt_ajax);
            alert('REST API URL not configured. Please check plugin settings.');
            return;
        }
        
        const $button = $('#mt-create-backup');
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Creating Backup...');
        
        const requestUrl = mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/create-backup';
        console.log('Making backup request to:', requestUrl);
        
        $.ajax({
            url: requestUrl,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
                console.log('Setting nonce header:', mt_ajax.rest_nonce || mt_ajax.nonce);
            },
            data: {
                reason: reason,
                type: 'full'
            }
        })
        .done(function(response) {
            console.log('Backup response:', response);
            if (response.success) {
                alert(`Backup Created Successfully!\n\n${response.data.votes_backed_up} votes backed up\n${response.data.scores_backed_up} evaluation scores backed up\n\nTimestamp: ${response.data.timestamp}`);
                location.reload(); // Refresh to show updated stats
            } else {
                alert('Failed to create backup: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function(xhr) {
            console.error('Backup error:', xhr.responseJSON || xhr.statusText);
            console.error('Full XHR object:', xhr);
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
        console.log('Exporting backup history in format:', format);
        
        // Create a form and submit it to trigger download
        const form = $('<form>', {
            method: 'GET',
            action: mt_ajax.ajax_url
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'mt_export_backup_history'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'format',
            value: format
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: mt_ajax.nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }

    function handleViewBackups(e) {
        e.preventDefault();
        
        console.log('handleViewBackups called');
        console.log('mt_ajax object:', mt_ajax);
        
        // Create backup history overlay
        showBackupHistoryOverlay();
        
        const requestUrl = mt_ajax.rest_url + 'mobility-trailblazers/v1/backup-history';
        console.log('Making backup history request to:', requestUrl);
        
        $.ajax({
            url: requestUrl,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
                console.log('Setting nonce header:', mt_ajax.rest_nonce || mt_ajax.nonce);
            }
        })
        .done(function(response) {
            console.log('Backup history response:', response);
            if (response.success) {
                showBackupHistoryTable(response);
            } else {
                $('#backup-history-content').html('<p class="error">Failed to load backup history: ' + (response.message || 'Unknown error') + '</p>');
            }
        })
        .fail(function(xhr) {
            console.error('View backups error:', xhr.responseJSON || xhr.statusText);
            console.error('Full XHR object:', xhr);
            $('#backup-history-content').html('<p class="error">Failed to load backup history</p>');
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
        
        $('body').append(overlayHtml);
        
        // Handle close button
        $('#close-backup-history, #backup-history-overlay').on('click', function(e) {
            if (e.target.id === 'backup-history-overlay' || e.target.id === 'close-backup-history') {
                $('#backup-history-overlay').remove();
            }
        });
    }

    function showBackupHistoryTable(data) {
        console.log('showBackupHistoryTable called with data:', data);
        
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
        
        $('#backup-history-content').html(html);
        
        // Handle restore clicks
        $('.restore-backup').on('click', function() {
            const backupId = $(this).data('backup-id');
            const backupType = $(this).data('backup-type');
            
            if (confirm('Are you sure you want to restore from this backup?\n\nThis will replace current data with the backup data.')) {
                performRestore(backupId, backupType);
            }
        });
    }

    function performRestore(backupId, backupType) {
        console.log('performRestore called with:', backupId, backupType);
        
        if (!mt_ajax || !mt_ajax.rest_url) {
            console.error('REST API URL not configured:', mt_ajax);
            alert('REST API URL not configured. Please check plugin settings.');
            return;
        }
        
        const $button = $(`.restore-backup[data-backup-id="${backupId}"]`);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Restoring...');
        
        const requestUrl = mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/restore-backup';
        console.log('Making restore request to:', requestUrl);
        
        $.ajax({
            url: requestUrl,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
                console.log('Setting nonce header:', mt_ajax.rest_nonce || mt_ajax.nonce);
            },
            data: {
                backup_id: backupId,
                backup_type: backupType
            }
        })
        .done(function(response) {
            console.log('Restore response:', response);
            if (response.success) {
                alert('Success! The backup has been restored.');
                $('#backup-history-overlay').remove();
                location.reload();
            } else {
                alert('Failed to restore backup: ' + (response.message || 'Unknown error'));
            }
        })
        .fail(function(xhr) {
            console.error('Restore error:', xhr.responseJSON || xhr.statusText);
            console.error('Full XHR object:', xhr);
            alert('Restore Failed: ' + (xhr.responseJSON?.message || 'Failed to restore backup'));
        })
        .always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    }

    // Targeted reset functions
    function performUserReset(userId) {
        $.ajax({
            url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
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
        $.ajax({
            url: mt_ajax.rest_url + 'mobility-trailblazers/v1/admin/bulk-reset',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mt_ajax.rest_nonce || mt_ajax.nonce);
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

    // Document ready - Initialize everything
    $(document).ready(function() {
        console.log('Vote Reset Admin JS loaded');
        console.log('mt_ajax object:', mt_ajax);
        
        VoteResetManager.init();
        
        // Backup button handlers
        console.log('Binding backup handlers...');
        console.log('Create backup button found:', $('#mt-create-backup').length);
        console.log('Export backups button found:', $('#mt-export-backups').length);
        console.log('View backups button found:', $('#mt-view-backups').length);
        
        $('#mt-create-backup').on('click', handleCreateBackup);
        $('#mt-export-backups').on('click', handleExportBackups);
        $('#mt-view-backups').on('click', handleViewBackups);
        
        // Auto-refresh statistics every 30 seconds if stats elements exist
        if ($('.stat-number').length > 0) {
            setInterval(function() {
                // Load updated stats via AJAX if needed
                console.log('Auto-refresh triggered');
            }, 30000);
        }
    });

    // Export for global access
    window.VoteResetManager = VoteResetManager;
    window.performUserReset = performUserReset;
    window.performCandidateReset = performCandidateReset;

})(jQuery);