/**
 * Emergency Fix for Evaluation Details
 * This script fixes the non-working "View Details" buttons on the evaluations page
 * 
 * To deploy:
 * 1. Upload this file to /wp-content/plugins/mobility-trailblazers/assets/js/
 * 2. Add the enqueue code to class-mt-admin.php or include directly in evaluations.php
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Check if we're on the evaluations page
    if (!$('body').hasClass('toplevel_page_mt-evaluations') && !window.location.href.includes('page=mt-evaluations')) {
        // Try to detect by presence of evaluation table
        if (!$('.view-details[data-evaluation-id]').length) {
            return;
        }
    }
    
    // Initialize the View Details functionality
    function initViewDetails() {
        // Remove any existing handlers to prevent duplicates
        $('.view-details').off('click.mtDetails');
        
        // Add click handler for View Details buttons
        $('.view-details').on('click.mtDetails', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $button = $(this);
            var evaluationId = $button.data('evaluation-id');
            
            if (!evaluationId) {
                console.error('No evaluation ID found on button');
                return;
            }
            
            // Get data from the table row
            var $row = $button.closest('tr');
            var juryMember = $row.find('td:eq(2)').text().trim();
            var candidate = $row.find('td:eq(3)').text().trim();
            var totalScore = $row.find('td:eq(4)').text().trim();
            var status = $row.find('td:eq(5)').text().trim();
            var date = $row.find('td:eq(6)').text().trim();
            
            // Get links if available
            var juryLink = $row.find('td:eq(2) a').attr('href') || '#';
            var candidateLink = $row.find('td:eq(3) a').attr('href') || '#';
            
            // Create modal HTML
            var modalHtml = `
                <div id="mt-evaluation-modal-overlay" class="mt-modal-overlay">
                    <div id="mt-evaluation-modal" class="mt-modal">
                        <div class="mt-modal-header">
                            <h2>Evaluation Details #${evaluationId}</h2>
                            <button class="mt-modal-close" aria-label="Close">&times;</button>
                        </div>
                        <div class="mt-modal-body">
                            <table class="widefat striped">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Jury Member:</th>
                                        <td>${juryLink !== '#' ? `<a href="${juryLink}" target="_blank">${juryMember}</a>` : juryMember}</td>
                                    </tr>
                                    <tr>
                                        <th>Candidate:</th>
                                        <td>${candidateLink !== '#' ? `<a href="${candidateLink}" target="_blank">${candidate}</a>` : candidate}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Score:</th>
                                        <td><strong>${totalScore}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><span class="status-${status.toLowerCase()}">${status}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated:</th>
                                        <td>${date}</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <div class="mt-modal-notice">
                                <p><strong>Note:</strong> Full evaluation details with individual criteria scores would normally appear here. This is a temporary fix while the full AJAX functionality is being restored.</p>
                            </div>
                        </div>
                        <div class="mt-modal-footer">
                            <button class="button button-primary mt-modal-close">Close</button>
                            ${candidateLink !== '#' ? `<a href="${candidateLink}" class="button" target="_blank">View Candidate</a>` : ''}
                            <button class="button button-link-delete mt-delete-evaluation" data-id="${evaluationId}">Delete Evaluation</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add styles if not already present
            if (!$('#mt-evaluation-modal-styles').length) {
                var styles = `
                    <style id="mt-evaluation-modal-styles">
                        .mt-modal-overlay {
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.7);
                            z-index: 159900;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            animation: mtFadeIn 0.3s ease;
                        }
                        
                        .mt-modal {
                            background: #fff;
                            max-width: 600px;
                            width: 90%;
                            max-height: 80vh;
                            overflow: auto;
                            border-radius: 4px;
                            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
                            animation: mtSlideIn 0.3s ease;
                        }
                        
                        .mt-modal-header {
                            padding: 15px 20px;
                            border-bottom: 1px solid #ddd;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        }
                        
                        .mt-modal-header h2 {
                            margin: 0;
                            font-size: 1.3em;
                        }
                        
                        .mt-modal-close {
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: #666;
                            padding: 0;
                            width: 30px;
                            height: 30px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        
                        .mt-modal-close:hover {
                            color: #000;
                        }
                        
                        .mt-modal-body {
                            padding: 20px;
                        }
                        
                        .mt-modal-body table {
                            margin-bottom: 20px;
                        }
                        
                        .mt-modal-notice {
                            background: #f0f0f1;
                            border-left: 4px solid #72aee6;
                            padding: 12px;
                            margin: 15px 0;
                        }
                        
                        .mt-modal-notice p {
                            margin: 0;
                        }
                        
                        .mt-modal-footer {
                            padding: 15px 20px;
                            border-top: 1px solid #ddd;
                            background: #f9f9f9;
                            display: flex;
                            gap: 10px;
                            justify-content: flex-end;
                        }
                        
                        .status-completed,
                        .status-submitted {
                            color: #46b450;
                            font-weight: 600;
                        }
                        
                        .status-draft {
                            color: #ffb900;
                            font-weight: 600;
                        }
                        
                        .status-rejected {
                            color: #dc3232;
                            font-weight: 600;
                        }
                        
                        @keyframes mtFadeIn {
                            from { opacity: 0; }
                            to { opacity: 1; }
                        }
                        
                        @keyframes mtSlideIn {
                            from { 
                                transform: translateY(-20px);
                                opacity: 0;
                            }
                            to { 
                                transform: translateY(0);
                                opacity: 1;
                            }
                        }
                    </style>
                `;
                $('head').append(styles);
            }
            
            // Remove any existing modal
            $('#mt-evaluation-modal-overlay').remove();
            
            // Add modal to body
            $('body').append(modalHtml);
            
            // Handle close button clicks
            $('.mt-modal-close, #mt-evaluation-modal-overlay').on('click', function(e) {
                if (e.target === this || $(this).hasClass('mt-modal-close')) {
                    $('#mt-evaluation-modal-overlay').fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Handle delete button
            $('.mt-delete-evaluation').on('click', function() {
                var deleteId = $(this).data('id');
                if (confirm('Are you sure you want to delete this evaluation? This action cannot be undone.')) {
                    // Try to get nonce if available
                    var nonce = '';
                    if (window.MTEvaluations && window.MTEvaluations.nonce) {
                        nonce = window.MTEvaluations.nonce;
                    } else if ($('#_wpnonce').length) {
                        nonce = $('#_wpnonce').val();
                    }
                    
                    if (nonce) {
                        window.location.href = `admin.php?page=mt-evaluations&action=delete&id=${deleteId}&_wpnonce=${nonce}`;
                    } else {
                        alert('Security token not found. Please refresh the page and try again.');
                    }
                }
            });
            
            // Close on ESC key
            $(document).on('keydown.mtModal', function(e) {
                if (e.keyCode === 27) { // ESC key
                    $('#mt-evaluation-modal-overlay').fadeOut(200, function() {
                        $(this).remove();
                    });
                    $(document).off('keydown.mtModal');
                }
            });
        });
    }
    
    // Initialize bulk actions handler
    function initBulkActions() {
        $('#doaction, #doaction2').off('click.mtBulk').on('click.mtBulk', function(e) {
            var $button = $(this);
            var action = $button.prev('select').val();
            
            if (action === 'delete') {
                var selected = [];
                $('input[name="evaluation[]"]:checked').each(function() {
                    selected.push($(this).val());
                });
                
                if (selected.length === 0) {
                    alert('Please select at least one evaluation to delete.');
                    e.preventDefault();
                    return false;
                }
                
                if (!confirm(`Are you sure you want to delete ${selected.length} evaluation(s)? This action cannot be undone.`)) {
                    e.preventDefault();
                    return false;
                }
                
                // Create and submit form
                var nonce = '';
                if (window.MTEvaluations && window.MTEvaluations.nonce) {
                    nonce = window.MTEvaluations.nonce;
                } else if ($('#_wpnonce').length) {
                    nonce = $('#_wpnonce').val();
                }
                
                if (!nonce) {
                    alert('Security token not found. Please refresh the page and try again.');
                    e.preventDefault();
                    return false;
                }
                
                var form = $(`
                    <form method="post" action="admin.php?page=mt-evaluations">
                        <input type="hidden" name="action" value="bulk_delete">
                        <input type="hidden" name="evaluations" value="${selected.join(',')}">
                        <input type="hidden" name="_wpnonce" value="${nonce}">
                    </form>
                `);
                
                $('body').append(form);
                form.submit();
            }
        });
    }
    
    // Initialize everything
    initViewDetails();
    initBulkActions();
    
    // Reinitialize if content is dynamically loaded
    $(document).on('ajaxComplete', function() {
        setTimeout(function() {
            if ($('.view-details').length) {
                initViewDetails();
                initBulkActions();
            }
        }, 100);
    });
    
    console.log('MT Evaluation Details Emergency Fix loaded successfully');
});