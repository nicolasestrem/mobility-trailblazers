<?php
/**
 * Import/Export Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Handle import
if (isset($_POST['mt_import']) && wp_verify_nonce($_POST['mt_import_nonce'], 'mt_import_data')) {
    if (!empty($_FILES['import_file']['tmp_name'])) {
        $result = mt_import_data($_FILES['import_file']['tmp_name'], $_POST['import_type']);
        if ($result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
}
?>

<div class="wrap">
    <h1><?php _e('Import/Export', 'mobility-trailblazers'); ?></h1>
    
    <div class="mt-import-export-container">
        <!-- Export Section -->
        <div class="mt-section">
            <h2><?php _e('Export Data', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Export your award data in various formats for backup or analysis.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-export-options">
                <h3><?php _e('Quick Export', 'mobility-trailblazers'); ?></h3>
                <div class="mt-export-buttons">
                    <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mt_export_candidates'), 'mt_export_nonce'); ?>" 
                       class="button button-primary">
                        <?php _e('Export All Candidates', 'mobility-trailblazers'); ?>
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mt_export_jury'), 'mt_export_nonce'); ?>" 
                       class="button">
                        <?php _e('Export Jury Members', 'mobility-trailblazers'); ?>
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mt_export_evaluations'), 'mt_export_nonce'); ?>" 
                       class="button">
                        <?php _e('Export All Evaluations', 'mobility-trailblazers'); ?>
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mt_export_assignments'), 'mt_export_nonce'); ?>" 
                       class="button">
                        <?php _e('Export Assignments', 'mobility-trailblazers'); ?>
                    </a>
                </div>
                
                <h3><?php _e('Advanced Export', 'mobility-trailblazers'); ?></h3>
                <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" class="mt-export-form">
                    <input type="hidden" name="action" value="mt_export_custom">
                    <?php wp_nonce_field('mt_export_custom', 'mt_export_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Data Type', 'mobility-trailblazers'); ?></th>
                            <td>
                                <select name="export_type" required>
                                    <option value=""><?php _e('Select data type', 'mobility-trailblazers'); ?></option>
                                    <option value="candidates"><?php _e('Candidates', 'mobility-trailblazers'); ?></option>
                                    <option value="jury"><?php _e('Jury Members', 'mobility-trailblazers'); ?></option>
                                    <option value="evaluations"><?php _e('Evaluations', 'mobility-trailblazers'); ?></option>
                                    <option value="votes"><?php _e('Votes', 'mobility-trailblazers'); ?></option>
                                    <option value="all"><?php _e('All Data', 'mobility-trailblazers'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Format', 'mobility-trailblazers'); ?></th>
                            <td>
                                <select name="export_format">
                                    <option value="csv"><?php _e('CSV', 'mobility-trailblazers'); ?></option>
                                    <option value="json"><?php _e('JSON', 'mobility-trailblazers'); ?></option>
                                    <option value="xml"><?php _e('XML', 'mobility-trailblazers'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Date Range', 'mobility-trailblazers'); ?></th>
                            <td>
                                <input type="date" name="date_from" />
                                <?php _e('to', 'mobility-trailblazers'); ?>
                                <input type="date" name="date_to" />
                                <p class="description"><?php _e('Leave empty to export all data', 'mobility-trailblazers'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Options', 'mobility-trailblazers'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_meta" value="1" checked />
                                    <?php _e('Include metadata', 'mobility-trailblazers'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="include_media" value="1" />
                                    <?php _e('Include media URLs', 'mobility-trailblazers'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="anonymize" value="1" />
                                    <?php _e('Anonymize personal data', 'mobility-trailblazers'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Export Data', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Import Section -->
        <div class="mt-section">
            <h2><?php _e('Import Data', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Import candidates, jury members, or evaluation data from a file.', 'mobility-trailblazers'); ?></p>
            
            <form method="post" enctype="multipart/form-data" class="mt-import-form">
                <?php wp_nonce_field('mt_import_data', 'mt_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Import Type', 'mobility-trailblazers'); ?></th>
                        <td>
                            <select name="import_type" id="import_type" required>
                                <option value=""><?php _e('Select import type', 'mobility-trailblazers'); ?></option>
                                <option value="candidates"><?php _e('Candidates', 'mobility-trailblazers'); ?></option>
                                <option value="jury"><?php _e('Jury Members', 'mobility-trailblazers'); ?></option>
                                <option value="evaluations"><?php _e('Evaluations', 'mobility-trailblazers'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('File', 'mobility-trailblazers'); ?></th>
                        <td>
                            <input type="file" name="import_file" accept=".csv,.json,.xml" required />
                            <p class="description"><?php _e('Supported formats: CSV, JSON, XML', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Options', 'mobility-trailblazers'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="update_existing" value="1" />
                                <?php _e('Update existing records', 'mobility-trailblazers'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="skip_validation" value="1" />
                                <?php _e('Skip validation (not recommended)', 'mobility-trailblazers'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <div class="mt-import-instructions" style="display: none;">
                    <h3><?php _e('Import Instructions', 'mobility-trailblazers'); ?></h3>
                    <div id="candidates-instructions" class="import-type-instructions" style="display: none;">
                        <p><?php _e('CSV format for candidates:', 'mobility-trailblazers'); ?></p>
                        <code>name,company,position,email,location,category,innovation_description</code>
                    </div>
                    <div id="jury-instructions" class="import-type-instructions" style="display: none;">
                        <p><?php _e('CSV format for jury members:', 'mobility-trailblazers'); ?></p>
                        <code>name,email,organization,expertise,bio</code>
                    </div>
                    <div id="evaluations-instructions" class="import-type-instructions" style="display: none;">
                        <p><?php _e('CSV format for evaluations:', 'mobility-trailblazers'); ?></p>
                        <code>candidate_id,jury_member_id,courage,innovation,implementation,relevance,visibility,comments</code>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" name="mt_import" class="button button-primary">
                        <?php _e('Import Data', 'mobility-trailblazers'); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <!-- Templates Section -->
        <div class="mt-section">
            <h2><?php _e('Import Templates', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Download template files to help you format your data correctly for import.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-template-downloads">
                <a href="<?php echo MT_PLUGIN_URL; ?>templates/candidates-import-template.csv" 
                   class="button" download>
                    <?php _e('Download Candidates Template', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo MT_PLUGIN_URL; ?>templates/jury-import-template.csv" 
                   class="button" download>
                    <?php _e('Download Jury Template', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo MT_PLUGIN_URL; ?>templates/evaluations-import-template.csv" 
                   class="button" download>
                    <?php _e('Download Evaluations Template', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.mt-import-export-container {
    max-width: 800px;
}

.mt-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.mt-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.mt-export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.mt-export-form,
.mt-import-form {
    margin-top: 20px;
}

.mt-import-instructions {
    background: #f0f0f1;
    padding: 15px;
    border-radius: 4px;
    margin: 20px 0;
}

.mt-import-instructions code {
    display: block;
    background: #fff;
    padding: 10px;
    margin-top: 10px;
    border: 1px solid #ddd;
    font-size: 13px;
}

.mt-template-downloads {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.form-table th {
    width: 150px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Show import instructions based on type
    $('#import_type').on('change', function() {
        var type = $(this).val();
        $('.import-type-instructions').hide();
        
        if (type) {
            $('.mt-import-instructions').show();
            $('#' + type + '-instructions').show();
        } else {
            $('.mt-import-instructions').hide();
        }
    });
    
    // Handle custom export form
    $('.mt-export-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var data = form.serialize();
        
        // Create a temporary form for download
        var tempForm = $('<form>', {
            method: 'POST',
            action: form.attr('action')
        });
        
        form.find('input, select').each(function() {
            var input = $(this);
            if (input.attr('type') === 'checkbox') {
                if (input.is(':checked')) {
                    tempForm.append($('<input>', {
                        type: 'hidden',
                        name: input.attr('name'),
                        value: input.val()
                    }));
                }
            } else {
                tempForm.append($('<input>', {
                    type: 'hidden',
                    name: input.attr('name'),
                    value: input.val()
                }));
            }
        });
        
        $('body').append(tempForm);
        tempForm.submit();
        tempForm.remove();
    });
});
</script> 