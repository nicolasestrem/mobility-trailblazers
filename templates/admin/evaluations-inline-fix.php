<?php
/**
 * Inline JavaScript Fix for Evaluation Details
 * 
 * Add this code to the END of templates/admin/evaluations.php (before the closing </div>)
 * OR create this as a separate file and include it at the bottom of evaluations.php
 * 
 * This provides an immediate fix for the View Details buttons without needing to modify
 * the plugin's enqueue scripts.
 */
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    'use strict';
    
    // Emergency fix for View Details buttons
    function initEvaluationDetails() {
        $('.view-details').off('click.mtFix').on('click.mtFix', function(e) {
            e.preventDefault();
            
            var evaluationId = $(this).data('evaluation-id');
            var $row = $(this).closest('tr');
            
            // Extract data from row
            var data = {
                id: evaluationId,
                jury: $row.find('td:eq(2)').text().trim(),
                juryLink: $row.find('td:eq(2) a').attr('href') || '',
                candidate: $row.find('td:eq(3)').text().trim(),
                candidateLink: $row.find('td:eq(3) a').attr('href') || '',
                score: $row.find('td:eq(4)').text().trim(),
                status: $row.find('td:eq(5)').text().trim(),
                date: $row.find('td:eq(6)').text().trim()
            };
            
            // Create modal
            var modal = $('<div/>', {
                id: 'mt-eval-modal',
                css: {
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(0,0,0,0.6)',
                    zIndex: 99999,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                }
            });
            
            var content = $('<div/>', {
                css: {
                    background: 'white',
                    padding: '30px',
                    maxWidth: '600px',
                    width: '90%',
                    borderRadius: '4px',
                    boxShadow: '0 5px 20px rgba(0,0,0,0.3)'
                },
                html: `
                    <h2 style="margin-top:0;">Evaluation Details #${data.id}</h2>
                    <table class="widefat striped">
                        <tr>
                            <th style="width:30%;">Jury Member:</th>
                            <td>${data.juryLink ? '<a href="'+data.juryLink+'" target="_blank">'+data.jury+'</a>' : data.jury}</td>
                        </tr>
                        <tr>
                            <th>Candidate:</th>
                            <td>${data.candidateLink ? '<a href="'+data.candidateLink+'" target="_blank">'+data.candidate+'</a>' : data.candidate}</td>
                        </tr>
                        <tr>
                            <th>Total Score:</th>
                            <td><strong>${data.score}</strong></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>${data.status}</td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td>${data.date}</td>
                        </tr>
                    </table>
                    <div style="margin-top:20px;text-align:right;">
                        <button class="button button-primary mt-close-modal">Close</button>
                        ${data.candidateLink ? '<a href="'+data.candidateLink+'" class="button" target="_blank" style="margin-left:10px;">View Candidate</a>' : ''}
                        <button class="button button-link-delete mt-delete-eval" data-id="${data.id}" style="margin-left:10px;">Delete</button>
                    </div>
                `
            });
            
            modal.append(content);
            $('body').append(modal);
            
            // Close handlers
            modal.on('click', function(e) {
                if (e.target === this) $(this).remove();
            });
            
            $('.mt-close-modal').on('click', function() {
                modal.remove();
            });
            
            // Delete handler
            $('.mt-delete-eval').on('click', function() {
                if (confirm('Delete this evaluation?')) {
                    // Try to get nonce
                    var nonce = '';
                    if (window.ajaxurl && $('#_wpnonce').length) {
                        nonce = $('#_wpnonce').val();
                    }
                    
                    if (nonce) {
                        window.location.href = 'admin.php?page=mt-evaluations&action=delete&id=' + $(this).data('id') + '&_wpnonce=' + nonce;
                    } else {
                        // Direct database approach via AJAX
                        alert('To delete this evaluation, use phpMyAdmin with this query:\n\nDELETE FROM wp_mt_evaluations WHERE id = ' + $(this).data('id'));
                    }
                }
            });
            
            // ESC to close
            $(document).on('keydown.mtModal', function(e) {
                if (e.keyCode === 27) {
                    modal.remove();
                    $(document).off('keydown.mtModal');
                }
            });
        });
    }
    
    // Initialize
    initEvaluationDetails();
    
    console.log('Evaluation Details Fix Active');
});
</script>