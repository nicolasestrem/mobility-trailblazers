<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get system info utility
$system_info = new \MobilityTrailblazers\Utilities\MT_System_Info();
$info = $system_info->get_system_info();
?>

<div class="mt-debug-info">
    <div class="mt-debug-header">
        <h2><?php esc_html_e('System Information', 'mobility-trailblazers'); ?></h2>
        <p class="description">
            <?php esc_html_e('Comprehensive system information for debugging and support.', 'mobility-trailblazers'); ?>
        </p>
        <div class="mt-info-actions">
            <button type="button" class="button mt-copy-sysinfo">
                <span class="dashicons dashicons-clipboard"></span>
                <?php esc_html_e('Copy to Clipboard', 'mobility-trailblazers'); ?>
            </button>
            <button type="button" class="button mt-export-sysinfo">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export as Text', 'mobility-trailblazers'); ?>
            </button>
            <button type="button" class="button mt-email-sysinfo">
                <span class="dashicons dashicons-email"></span>
                <?php esc_html_e('Email to Support', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </div>

    <!-- PHP Information -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('PHP Configuration', 'mobility-trailblazers'); ?></h3>
        <div class="mt-info-table">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('PHP Version', 'mobility-trailblazers'); ?></th>
                        <td>
                            <?php echo esc_html($info['php']['version']); ?>
                            <?php if (version_compare($info['php']['version'], '7.4', '<')): ?>
                            <span class="mt-badge mt-badge-warning">
                                <?php esc_html_e('Update recommended', 'mobility-trailblazers'); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('PHP SAPI', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['php']['sapi']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Memory Limit', 'mobility-trailblazers'); ?></th>
                        <td>
                            <?php echo esc_html($info['php']['memory_limit']); ?>
                            <?php 
                            $memory_bytes = wp_convert_hr_to_bytes($info['php']['memory_limit']);
                            if ($memory_bytes < 134217728): // Less than 128MB
                            ?>
                            <span class="mt-badge mt-badge-warning">
                                <?php esc_html_e('Low - consider increasing', 'mobility-trailblazers'); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Max Execution Time', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['php']['max_execution_time']); ?> seconds</td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Max Input Vars', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['php']['max_input_vars']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Post Max Size', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['php']['post_max_size']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Upload Max Filesize', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['php']['upload_max_filesize']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Extensions', 'mobility-trailblazers'); ?></th>
                        <td>
                            <div class="mt-extensions-list">
                                <?php 
                                $important_extensions = ['mysqli', 'curl', 'json', 'mbstring', 'zip', 'gd', 'xml'];
                                $php_extensions = [];
                                if (isset($info['php']) && is_array($info['php']) && isset($info['php']['extensions'])) {
                                    if (is_array($info['php']['extensions'])) {
                                        $php_extensions = $info['php']['extensions'];
                                    }
                                }
                                foreach ($important_extensions as $ext):
                                    $loaded = in_array($ext, $php_extensions);
                                ?>
                                <span class="mt-badge mt-badge-<?php echo $loaded ? 'success' : 'error'; ?>">
                                    <?php echo esc_html($ext); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <details class="mt-all-extensions">
                                <summary><?php esc_html_e('Show all extensions', 'mobility-trailblazers'); ?></summary>
                                <code><?php 
                                    $extensions = isset($info['php']['extensions']) && is_array($info['php']['extensions']) 
                                        ? $info['php']['extensions'] 
                                        : [];
                                    // Ensure all array elements are strings
                                    $extensions = array_filter($extensions, 'is_string');
                                    echo esc_html(implode(', ', $extensions)); 
                                ?></code>
                            </details>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- WordPress Information -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('WordPress Configuration', 'mobility-trailblazers'); ?></h3>
        <div class="mt-info-table">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('WordPress Version', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['wordpress']['version']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Site URL', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['wordpress']['site_url']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Home URL', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['wordpress']['home_url']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Multisite', 'mobility-trailblazers'); ?></th>
                        <td>
                            <?php if ($info['wordpress']['multisite']): ?>
                            <span class="mt-badge mt-badge-info"><?php esc_html_e('Yes', 'mobility-trailblazers'); ?></span>
                            <?php else: ?>
                            <?php esc_html_e('No', 'mobility-trailblazers'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Language', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['wordpress']['language']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Timezone', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['wordpress']['timezone']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Debug Mode', 'mobility-trailblazers'); ?></th>
                        <td>
                            <?php if (isset($info['wordpress']['debug']) && $info['wordpress']['debug']): ?>
                            <span class="mt-badge mt-badge-warning"><?php esc_html_e('Enabled', 'mobility-trailblazers'); ?></span>
                            <?php else: ?>
                            <span class="mt-badge mt-badge-success"><?php esc_html_e('Disabled', 'mobility-trailblazers'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Memory Limit', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['wordpress']['memory_limit']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Server Information -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Server Information', 'mobility-trailblazers'); ?></h3>
        <div class="mt-info-table">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Server Software', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['server']['software']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Server IP', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html(isset($info['server']['ip']) ? $info['server']['ip'] : 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Server Port', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['server']['port']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Document Root', 'mobility-trailblazers'); ?></th>
                        <td><code><?php echo esc_html($info['server']['document_root']); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('HTTPS', 'mobility-trailblazers'); ?></th>
                        <td>
                            <?php if ($info['server']['https']): ?>
                            <span class="mt-badge mt-badge-success"><?php esc_html_e('Enabled', 'mobility-trailblazers'); ?></span>
                            <?php else: ?>
                            <span class="mt-badge mt-badge-warning"><?php esc_html_e('Disabled', 'mobility-trailblazers'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Database Information -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Database Information', 'mobility-trailblazers'); ?></h3>
        <div class="mt-info-table">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Database Version', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html(isset($info['database']['version']) ? $info['database']['version'] : 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Database Host', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html(isset($info['database']['host']) ? $info['database']['host'] : 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Database Name', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html(isset($info['database']['name']) ? $info['database']['name'] : 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Table Prefix', 'mobility-trailblazers'); ?></th>
                        <td><code><?php echo esc_html(isset($info['database']['prefix']) ? $info['database']['prefix'] : 'Unknown'); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Charset', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html(isset($info['database']['charset']) ? $info['database']['charset'] : 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Collation', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html(isset($info['database']['collation']) ? $info['database']['collation'] : 'Unknown'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Plugins -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Active Plugins', 'mobility-trailblazers'); ?></h3>
        <div class="mt-plugins-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Plugin', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Version', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Author', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($info['plugins']) && is_array($info['plugins'])): ?>
                        <?php foreach ($info['plugins'] as $plugin): 
                            if (!is_array($plugin)) continue;
                            $name = isset($plugin['name']) ? $plugin['name'] : 'Unknown';
                            $version = isset($plugin['version']) ? $plugin['version'] : 'Unknown';
                            $author = isset($plugin['author']) ? $plugin['author'] : 'Unknown';
                            $network = isset($plugin['network']) ? $plugin['network'] : false;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($name); ?></strong>
                                <?php if ($network): ?>
                                <span class="mt-badge mt-badge-info"><?php esc_html_e('Network', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($version); ?></td>
                            <td><?php echo esc_html($author); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Theme Information -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Theme Information', 'mobility-trailblazers'); ?></h3>
        <div class="mt-info-table">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Active Theme', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['theme']['name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Version', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['theme']['version']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Author', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['theme']['author']); ?></td>
                    </tr>
                    <?php if ($info['theme']['parent_theme']): ?>
                    <tr>
                        <th><?php esc_html_e('Parent Theme', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($info['theme']['parent_theme']); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Constants -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Important Constants', 'mobility-trailblazers'); ?></h3>
        <div class="mt-info-table">
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php 
                    $constants = isset($info['constants']) && is_array($info['constants']) ? $info['constants'] : [];
                    foreach ($constants as $constant => $value): 
                    ?>
                    <tr>
                        <th><code><?php echo esc_html($constant); ?></code></th>
                        <td>
                            <?php if (is_bool($value)): ?>
                                <?php echo $value ? 'true' : 'false'; ?>
                            <?php else: ?>
                                <code><?php echo esc_html($value); ?></code>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hidden textarea for copy functionality -->
    <textarea id="mt-sysinfo-text" style="display: none;"><?php echo esc_textarea($system_info->export_as_text()); ?></textarea>
</div>