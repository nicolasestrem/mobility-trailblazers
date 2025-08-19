<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get debug manager
$debug_manager = new \MobilityTrailblazers\Admin\MT_Debug_Manager();
$environment = $debug_manager->get_environment();
$categories = $debug_manager->get_script_categories();
$audit_log = $debug_manager->get_audit_log(10);
?>

<div class="mt-debug-scripts">
    <div class="mt-debug-header">
        <h2><?php esc_html_e('Debug Scripts', 'mobility-trailblazers'); ?></h2>
        <p class="description">
            <?php esc_html_e('Execute debug and maintenance scripts based on environment restrictions.', 'mobility-trailblazers'); ?>
        </p>
        <div class="mt-environment-notice">
            <span class="dashicons dashicons-info-outline"></span>
            <?php 
            printf(
                esc_html__('Current environment: %s. Scripts are filtered based on environment safety.', 'mobility-trailblazers'),
                '<strong>' . esc_html(ucfirst($environment)) . '</strong>'
            );
            ?>
        </div>
    </div>

    <!-- Script Categories -->
    <?php foreach ($categories as $category_key => $category): ?>
    <div class="mt-debug-section">
        <h3><?php echo esc_html($category['title']); ?></h3>
        <div class="mt-scripts-grid">
            <?php 
            $has_scripts = false;
            foreach ($category['scripts'] as $script_file => $script_info): 
                if (!$debug_manager->is_script_allowed($script_file)) {
                    continue;
                }
                $has_scripts = true;
                $is_dangerous = !empty($script_info['dangerous']);
            ?>
            <div class="mt-script-card <?php echo $is_dangerous ? 'dangerous' : ''; ?>">
                <div class="mt-script-header">
                    <h4><?php echo esc_html($script_info['title']); ?></h4>
                    <?php if ($is_dangerous): ?>
                    <span class="mt-badge mt-badge-danger">
                        <span class="dashicons dashicons-warning"></span>
                        <?php esc_html_e('Dangerous', 'mobility-trailblazers'); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="mt-script-info">
                    <div class="mt-script-meta">
                        <span class="dashicons dashicons-media-code"></span>
                        <code><?php echo esc_html($script_file); ?></code>
                    </div>
                    <?php if (!empty($script_info['description'])): ?>
                    <p class="mt-script-description">
                        <?php echo esc_html($script_info['description']); ?>
                    </p>
                    <?php endif; ?>
                    <div class="mt-script-environments">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                        <?php esc_html_e('Allowed in:', 'mobility-trailblazers'); ?>
                        <?php 
                        $environments = $script_info['environments'] ?? ['development'];
                        echo esc_html(implode(', ', array_map('ucfirst', $environments)));
                        ?>
                    </div>
                </div>
                
                <div class="mt-script-actions">
                    <?php if (!empty($script_info['parameters'])): ?>
                    <div class="mt-script-params">
                        <?php foreach ($script_info['parameters'] as $param_key => $param): ?>
                        <div class="mt-param-field">
                            <label for="param-<?php echo esc_attr($script_file . '-' . $param_key); ?>">
                                <?php echo esc_html($param['label']); ?>
                                <?php if (!empty($param['required'])): ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            <?php if ($param['type'] === 'select'): ?>
                            <select name="<?php echo esc_attr($param_key); ?>" 
                                    id="param-<?php echo esc_attr($script_file . '-' . $param_key); ?>"
                                    class="mt-script-param">
                                <?php foreach ($param['options'] as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>">
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            <input type="<?php echo esc_attr($param['type']); ?>" 
                                   name="<?php echo esc_attr($param_key); ?>"
                                   id="param-<?php echo esc_attr($script_file . '-' . $param_key); ?>"
                                   class="mt-script-param"
                                   placeholder="<?php echo esc_attr($param['placeholder'] ?? ''); ?>"
                                   <?php echo !empty($param['required']) ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <button type="button" 
                            class="button <?php echo $is_dangerous ? 'button-secondary' : 'button-primary'; ?> mt-execute-script"
                            data-script="<?php echo esc_attr($script_file); ?>"
                            data-category="<?php echo esc_attr($category_key); ?>"
                            <?php echo $is_dangerous ? 'data-dangerous="true"' : ''; ?>>
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e('Execute', 'mobility-trailblazers'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (!$has_scripts): ?>
            <div class="mt-no-scripts">
                <span class="dashicons dashicons-lock"></span>
                <p>
                    <?php 
                    printf(
                        esc_html__('No %s scripts available in %s environment.', 'mobility-trailblazers'),
                        esc_html(strtolower($category['title'])),
                        esc_html($environment)
                    );
                    ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Audit Log -->
    <?php if (!empty($audit_log)): ?>
    <div class="mt-debug-section">
        <h3>
            <?php esc_html_e('Recent Script Executions', 'mobility-trailblazers'); ?>
            <button type="button" class="button button-small mt-clear-audit-log">
                <?php esc_html_e('Clear Log', 'mobility-trailblazers'); ?>
            </button>
        </h3>
        <div class="mt-audit-log">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Script', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Category', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('User', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Environment', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Timestamp', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('IP Address', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_log as $entry): ?>
                    <tr>
                        <td><code><?php echo esc_html($entry['script']); ?></code></td>
                        <td><?php echo esc_html($entry['category']); ?></td>
                        <td><?php echo esc_html($entry['user_login']); ?></td>
                        <td>
                            <span class="mt-badge mt-badge-<?php echo esc_attr($entry['environment']); ?>">
                                <?php echo esc_html(ucfirst($entry['environment'])); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($entry['timestamp']); ?></td>
                        <td><code><?php echo esc_html($entry['ip_address']); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Script Output Modal -->
<div id="mt-script-output-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <div class="mt-modal-header">
            <h3><?php esc_html_e('Script Output', 'mobility-trailblazers'); ?></h3>
            <button type="button" class="mt-modal-close">&times;</button>
        </div>
        <div class="mt-modal-body">
            <pre id="mt-script-output"></pre>
        </div>
        <div class="mt-modal-footer">
            <button type="button" class="button mt-modal-close">
                <?php esc_html_e('Close', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </div>
</div>