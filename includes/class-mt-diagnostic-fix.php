<?php
/**
 * Fix for the MT Diagnostic Page User-Jury Linker
 * 
 * This file contains the corrected JavaScript code for the user dropdown
 * and improved modal UI for linking users to jury members
 */

class MT_Diagnostic_Fix {
    /**
     * Initialize the fix
     */
    public static function init() {
        add_action('admin_footer', array(__CLASS__, 'add_fixed_js'), 999);
        add_filter('wp_ajax_mt_diagnostic_action', array(__CLASS__, 'fix_user_data_structure'), 5);
    }

    /**
     * Add the fixed JavaScript code
     */
    public static function add_fixed_js() {
        // Only load on the diagnostic page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'mt-award-system_page_mt-diagnostic') {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Override the displayUserJuryLinker function with the fixed version
            window.displayUserJuryLinker = function(data, preselectedJuryId) {
                var modalHtml = '<div class="mt-user-jury-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; display: none;">';
                modalHtml += '<div class="mt-user-jury-modal-content" style="position: relative; background: white; width: 500px; margin: 50px auto; padding: 20px; border-radius: 5px;">';
                modalHtml += '<h3>Link User to Jury Member</h3>';
                modalHtml += '<div class="mt-user-jury-form">';
                
                // Jury member dropdown
                modalHtml += '<p><label for="jury-select">Select Jury Member:</label></p>';
                modalHtml += '<select id="jury-select" style="width: 100%; margin-bottom: 15px;">';
                modalHtml += '<option value="">Choose a jury member...</option>';
                
                if (data.jury_members && data.jury_members.length > 0) {
                    data.jury_members.forEach(function(jury) {
                        var selected = preselectedJuryId && jury.jury_id == preselectedJuryId ? ' selected' : '';
                        var linkedText = jury.linked_user_id ? ' (Currently linked to User ID: ' + jury.linked_user_id + ')' : '';
                        modalHtml += '<option value="' + jury.jury_id + '"' + selected + '>' + jury.jury_name + linkedText + '</option>';
                    });
                }
                modalHtml += '</select>';
                
                // User dropdown
                modalHtml += '<p><label for="user-select">Select User:</label></p>';
                modalHtml += '<select id="user-select" style="width: 100%; margin-bottom: 15px;">';
                modalHtml += '<option value="">Choose a user...</option>';
                
                if (data.users && data.users.length > 0) {
                    data.users.forEach(function(user) {
                        // Check if user object has the required properties
                        var userId = user.ID || user.id || '';
                        var displayName = user.display_name || user.data?.display_name || 'Unknown User';
                        var userLogin = user.user_login || user.data?.user_login || '';
                        var userEmail = user.user_email || user.data?.user_email || '';
                        
                        if (userId) {
                            var optionText = displayName;
                            if (userLogin) optionText += ' (' + userLogin + ')';
                            if (userEmail) optionText += ' - ' + userEmail;
                            
                            modalHtml += '<option value="' + userId + '">' + optionText + '</option>';
                        }
                    });
                }
                modalHtml += '</select>';
                
                // Current linkage info
                modalHtml += '<div id="current-link-info" style="margin: 15px 0; padding: 10px; background: #f0f0f0; border-radius: 3px; display: none;">';
                modalHtml += '<p style="margin: 0;"><strong>Current Linkage:</strong> <span id="current-link-text"></span></p>';
                modalHtml += '</div>';
                
                modalHtml += '<div style="margin-top: 20px;">';
                modalHtml += '<button type="button" class="button button-primary" id="link-user-submit">Link User</button> ';
                modalHtml += '<button type="button" class="button" id="link-user-cancel">Cancel</button>';
                modalHtml += '</div>';
                
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                
                $('body').append(modalHtml);
                $('.mt-user-jury-modal').fadeIn();
                
                // Update current linkage info when jury member is selected
                $('#jury-select').on('change', function() {
                    var selectedJuryId = $(this).val();
                    if (selectedJuryId) {
                        var selectedJury = data.jury_members.find(function(j) { 
                            return j.jury_id == selectedJuryId; 
                        });
                        if (selectedJury && selectedJury.linked_user_id) {
                            var linkedUser = data.users.find(function(u) { 
                                return (u.ID || u.id) == selectedJury.linked_user_id; 
                            });
                            if (linkedUser) {
                                var displayName = linkedUser.display_name || linkedUser.data?.display_name || 'User ID: ' + selectedJury.linked_user_id;
                                $('#current-link-text').text(displayName);
                                $('#current-link-info').show();
                            } else {
                                $('#current-link-text').text('User ID: ' + selectedJury.linked_user_id + ' (user not found)');
                                $('#current-link-info').show();
                            }
                        } else {
                            $('#current-link-info').hide();
                        }
                    } else {
                        $('#current-link-info').hide();
                    }
                });
                
                // Handle modal close
                $('#link-user-cancel, .mt-user-jury-modal').on('click', function(e) {
                    if (e.target === this || $(e.target).attr('id') === 'link-user-cancel') {
                        $('.mt-user-jury-modal').fadeOut(function() {
                            $(this).remove();
                        });
                    }
                });
                
                // Prevent closing when clicking inside modal content
                $('.mt-user-jury-modal-content').on('click', function(e) {
                    e.stopPropagation();
                });
                
                // Handle link submission
                $('#link-user-submit').on('click', function() {
                    var juryId = $('#jury-select').val();
                    var userId = $('#user-select').val();
                    
                    if (!juryId || !userId) {
                        alert('Please select both a jury member and a user.');
                        return;
                    }
                    
                    // Disable button to prevent double submission
                    $(this).prop('disabled', true).text('Linking...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'mt_diagnostic_action',
                            diagnostic_action: 'link_user_to_jury',
                            user_id: userId,
                            jury_id: juryId,
                            nonce: '<?php echo wp_create_nonce('mt_diagnostic_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                $('.mt-user-jury-modal').fadeOut(function() {
                                    $(this).remove();
                                });
                                // Reload the page to see updated linkages
                                location.reload();
                            } else {
                                alert('Error: ' + response.data.message);
                                $('#link-user-submit').prop('disabled', false).text('Link User');
                            }
                        },
                        error: function() {
                            alert('An error occurred while linking the user.');
                            $('#link-user-submit').prop('disabled', false).text('Link User');
                        }
                    });
                });
            };
        });
        </script>
        
        <style>
        /* Additional styles for the modal */
        .mt-user-jury-modal select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .mt-user-jury-modal label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        
        .mt-user-jury-modal-content h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        </style>
        <?php
    }

    /**
     * Fix the user data structure in the AJAX handler
     */
    public static function fix_user_data_structure() {
        // Only process if this is the show_user_jury_linker action
        if (!isset($_POST['diagnostic_action']) || $_POST['diagnostic_action'] !== 'show_user_jury_linker') {
            return;
        }
        
        // Let the original handler run first
        add_action('wp_ajax_mt_diagnostic_action', function() {
            if ($_POST['diagnostic_action'] === 'show_user_jury_linker') {
                // Get the data that would be sent
                $jury_members = get_posts(array(
                    'post_type' => 'mt_jury',
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                $jury_data = array();
                foreach ($jury_members as $jury) {
                    $user_id = get_post_meta($jury->ID, 'user_id', true);
                    $jury_data[] = array(
                        'jury_id' => $jury->ID,
                        'jury_name' => $jury->post_title,
                        'linked_user_id' => $user_id
                    );
                }
                
                // Get all users with proper data structure
                $users = get_users(array(
                    'orderby' => 'display_name'
                ));
                
                $user_data = array();
                foreach ($users as $user) {
                    $user_data[] = array(
                        'ID' => $user->ID,
                        'display_name' => $user->display_name,
                        'user_login' => $user->user_login,
                        'user_email' => $user->user_email
                    );
                }
                
                // Send the properly formatted response
                wp_send_json_success(array(
                    'jury_members' => $jury_data,
                    'users' => $user_data
                ));
                exit;
            }
        }, 9); // Run before the default priority 10
    }
}

// Initialize the fix
MT_Diagnostic_Fix::init(); 