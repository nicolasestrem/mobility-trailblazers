/**
 * Jury Management Admin JavaScript
 * File: /wp-content/plugins/mobility-trailblazers/assets/js/jury-management-admin.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize
    loadJuryMembers();
    loadJuryStats();
    loadActivityLog();
    
    // Set up auto-refresh
    setInterval(function() {
        loadJuryStats();
        loadActivityLog();
    }, 30000); // Refresh every 30 seconds
    
    // Initialize dialog
    $('#jury-member-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        buttons: {
            'Save': function() {
                saveJuryMember();
            },
            'Cancel': function() {
                $(this).dialog('close');
            }
        }
    });
    
    // Add new jury member
    $('#mt-add-jury-member').on('click', function(e) {
        e.preventDefault();
        $('#jury-member-form')[0].reset();
        $('#jury-member-id').val('');
        $('#jury-member-dialog').dialog('option', 'title', mt_jury_admin.strings.add_new || 'Add New Jury Member');
        $('#jury-member-dialog').dialog('open');
    });
    
    // Edit jury member
    $(document).on('click', '.edit-jury', function(e) {
        e.preventDefault();
        var juryId = $(this).data('id');
        loadJuryMemberData(juryId);
    });
    
    // Delete jury member
    $(document).on('click', '.delete-jury', function(e) {
        e.preventDefault();
        var juryId = $(this).data('id');
        var juryName = $(this).closest('tr').find('td:nth-child(2)').text();
        
        if (confirm(mt_jury_admin.strings.confirm_delete)) {
            deleteJuryMember(juryId);
        }
    });
    
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('.jury-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Bulk actions
    $('#doaction').on('click', function(e) {
        e.preventDefault();
        var action = $('#bulk-action-selector').val();
        
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        var selectedIds = [];
        $('.jury-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('Please select at least one jury member');
            return;
        }
        
        if (action === 'delete' && !confirm(mt_jury_admin.strings.confirm_bulk_delete)) {
            return;
        }
        
        performBulkAction(action, selectedIds);
    });
    
    // Filter
    $('#filter-button').on('click', function(e) {
        e.preventDefault();
        loadJuryMembers();
    });
    
    // Export data
    $('#export-jury-data').on('click', function(e) {
        e.preventDefault();
        exportJuryData();
    });
    
    // View jury member details
    $(document).on('click', '.view-jury', function(e) {
        e.preventDefault();
        var juryId = $(this).data('id');
        window.open(mt_jury_admin.view_url + '&jury_id=' + juryId, '_blank');
    });
    
    // Send invitation
    $(document).on('click', '.send-invitation', function(e) {
        e.preventDefault();
        var juryId = $(this).data('id');
        sendInvitation(juryId);
    });
    
    /**
     * Load jury members list
     */
    function loadJuryMembers() {
        var data = {
            action: 'mt_get_jury_list',
            nonce: mt_jury_admin.nonce,
            status: $('#filter-status').val(),
            category: $('#filter-category').val()
        };
        
        $('#jury-members-list').html('<tr><td colspan="9" class="text-center"><span class="spinner is-active"></span> Loading...</td></tr>');
        
        $.post(mt_jury_admin.ajax_url, data, function(response) {
            if (response.success) {
                displayJuryMembers(response.data);
            } else {
                $('#jury-members-list').html('<tr><td colspan="9" class="text-center">Error loading jury members</td></tr>');
            }
        });
    }
    
    /**
     * Display jury members in table
     */
    function displayJuryMembers(members) {
        var html = '';
        
        if (members.length === 0) {
            html = '<tr><td colspan="9" class="text-center">No jury members found</td></tr>';
        } else {
            $.each(members, function(index, member) {
                html += '<tr>';
                html += '<th scope="row" class="check-column"><input type="checkbox" class="jury-checkbox" value="' + member.id + '"></th>';
                html += '<td><strong>' + escapeHtml(member.name) + '</strong></td>';
                html += '<td>' + escapeHtml(member.email) + '</td>';
                html += '<td>' + getCategoryLabel(member.category) + '</td>';
                html += '<td>' + getStatusBadge(member.status) + '</td>';
                html += '<td>' + member.assigned + '</td>';
                html += '<td>' + member.completed + ' (' + member.completion_rate + '%)</td>';
                html += '<td>' + member.last_activity + '</td>';
                html += '<td class="jury-actions">';
                html += '<a href="#" class="edit-jury" data-id="' + member.id + '">Edit</a> | ';
                html += '<a href="#" class="delete-jury" data-id="' + member.id + '">Delete</a> | ';
                html += '<a href="#" class="view-jury" data-id="' + member.id + '">View</a>';
                if (member.status !== 'inactive') {
                    html += ' | <a href="#" class="send-invitation" data-id="' + member.id + '">Send Reminder</a>';
                }
                html += '</td>';
                html += '</tr>';
            });
        }
        
        $('#jury-members-list').html(html);
    }
    
    /**
     * Load jury statistics
     */
    function loadJuryStats() {
        $.post(mt_jury_admin.ajax_url, {
            action: 'mt_get_jury_stats',
            nonce: mt_jury_admin.nonce
        }, function(response) {
            if (response.success) {
                $('#mt-jury-stats .stat-box:eq(0) .stat-number').text(response.data.total_jury);
                $('#mt-jury-stats .stat-box:eq(1) .stat-number').text(response.data.active_jury);
                $('#mt-jury-stats .stat-box:eq(2) .stat-number').text(response.data.total_evaluations);
                $('#mt-jury-stats .stat-box:eq(3) .stat-number').text(response.data.avg_completion_rate);
            }
        });
    }
    
    /**
     * Load activity log
     */
    function loadActivityLog() {
        $.post(mt_jury_admin.ajax_url, {
            action: 'mt_get_jury_activity',
            nonce: mt_jury_admin.nonce
        }, function(response) {
            if (response.success) {
                displayActivityLog(response.data);
            }
        });
    }
    
    /**
     * Display activity log
     */
    function displayActivityLog(activities) {
        var html = '';
        
        if (activities.length === 0) {
            html = '<p>No recent activity</p>';
        } else {
            html = '<ul class="activity-list">';
            $.each(activities, function(index, activity) {
                html += '<li class="activity-' + activity.type + '">';
                html += '<span class="activity-time">' + activity.time + '</span> - ';
                html += '<span class="activity-message">' + activity.message + '</span>';
                html += '</li>';
            });
            html += '</ul>';
        }
        
        $('#jury-activity-log').html(html);
    }
    
    /**
     * Load jury member data for editing
     */
    function loadJuryMemberData(juryId) {
        // Find member in current data
        var member = null;
        $('#jury-members-list tr').each(function() {
            if ($(this).find('.jury-checkbox').val() == juryId) {
                member = {
                    id: juryId,
                    name: $(this).find('td:eq(1)').text(),
                    email: $(this).find('td:eq(2)').text(),
                    category: $(this).find('td:eq(3)').data('value') || 'general'
                };
                return false;
            }
        });
        
        if (member) {
            $('#jury-member-id').val(member.id);
            $('#jury-name').val(member.name);
            $('#jury-email').val(member.email);
            $('#jury-category').val(member.category);
            $('#jury-member-dialog').dialog('option', 'title', 'Edit Jury Member');
            $('#jury-member-dialog').dialog('open');
        }
    }
    
    /**
     * Save jury member
     */
    function saveJuryMember() {
        var form = $('#jury-member-form');
        var data = form.serialize();
        var juryId = $('#jury-member-id').val();
        
        data += '&action=' + (juryId ? 'mt_update_jury_member' : 'mt_create_jury_member');
        data += '&nonce=' + mt_jury_admin.nonce;
        
        // Show saving message
        var buttons = $('#jury-member-dialog').dialog('widget').find('.ui-dialog-buttonset button');
        buttons.first().text(mt_jury_admin.strings.saving).prop('disabled', true);
        
        $.post(mt_jury_admin.ajax_url, data, function(response) {
            if (response.success) {
                $('#jury-member-dialog').dialog('close');
                loadJuryMembers();
                loadJuryStats();
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data || mt_jury_admin.strings.error, 'error');
            }
            
            buttons.first().text('Save').prop('disabled', false);
        }).fail(function() {
            showNotice(mt_jury_admin.strings.error, 'error');
            buttons.first().text('Save').prop('disabled', false);
        });
    }
    
    /**
     * Delete jury member
     */
    function deleteJuryMember(juryId) {
        $.post(mt_jury_admin.ajax_url, {
            action: 'mt_delete_jury_member',
            nonce: mt_jury_admin.nonce,
            jury_id: juryId
        }, function(response) {
            if (response.success) {
                loadJuryMembers();
                loadJuryStats();
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data || mt_jury_admin.strings.error, 'error');
            }
        });
    }
    
    /**
     * Perform bulk action
     */
    function performBulkAction(action, juryIds) {
        $.post(mt_jury_admin.ajax_url, {
            action: 'mt_bulk_jury_action',
            nonce: mt_jury_admin.nonce,
            action_type: action,
            jury_ids: juryIds
        }, function(response) {
            if (response.success) {
                loadJuryMembers();
                loadJuryStats();
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data || mt_jury_admin.strings.error, 'error');
            }
        });
    }
    
    /**
     * Export jury data
     */
    function exportJuryData() {
        $.post(mt_jury_admin.ajax_url, {
            action: 'mt_export_jury_data',
            nonce: mt_jury_admin.nonce
        }, function(response) {
            if (response.success) {
                // Create download link
                var blob = new Blob([atob(response.data.content)], {type: 'text/csv;charset=utf-8;'});
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = response.data.filename;
                link.click();
                
                showNotice('Export completed successfully', 'success');
            } else {
                showNotice('Export failed', 'error');
            }
        });
    }
    
    /**
     * Send invitation/reminder email
     */
    function sendInvitation(juryId) {
        $.post(mt_jury_admin.ajax_url, {
            action: 'mt_send_jury_invitation',
            nonce: mt_jury_admin.nonce,
            jury_id: juryId
        }, function(response) {
            if (response.success) {
                showNotice('Invitation sent successfully', 'success');
            } else {
                showNotice('Failed to send invitation', 'error');
            }
        });
    }
    
    /**
     * Show notice
     */
    function showNotice(message, type) {
        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Get category label
     */
    function getCategoryLabel(category) {
        var labels = {
            'infrastructure': 'Infrastructure/Politics',
            'startups': 'Startups/New Makers',
            'established': 'Established Companies',
            'general': 'General'
        };
        
        return labels[category] || category;
    }
    
    /**
     * Get status badge
     */
    function getStatusBadge(status) {
        var classes = {
            'active': 'status-active',
            'inactive': 'status-inactive',
            'pending': 'status-pending'
        };
        
        return '<span class="status-badge ' + (classes[status] || '') + '">' + status + '</span>';
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});