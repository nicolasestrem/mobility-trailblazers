<?php
/**
 * Debug script to create Elementor templates
 */

// Bootstrap WordPress
require_once('/var/www/html/wp-load.php');

// Check if Elementor is active
if (!defined('ELEMENTOR_VERSION')) {
    echo "Elementor is not active.\n";
    exit;
}

// Define shortcodes
$shortcodes = [
    'mt_candidate_grid' => 'MT Candidate Grid',
    'mt_voting_interface' => 'MT Voting Interface',
    'mt_jury_dashboard' => 'MT Jury Dashboard',
    'mt_voting_progress' => 'MT Voting Progress'
];

// Check container mode
$container_mode = get_option('elementor_experiment-container', 'default') === 'active';
$template_type = $container_mode ? 'container' : 'section';

echo "Creating Elementor templates...\n";
echo "Container mode: " . ($container_mode ? 'Yes' : 'No') . "\n";
echo "Template type: $template_type\n\n";

foreach ($shortcodes as $shortcode => $title) {
    echo "Creating template: $title\n";
    
    // Check if template exists
    $existing = get_posts([
        'post_type' => 'elementor_library',
        'title' => $title,
        'posts_per_page' => 1
    ]);
    
    // Build elementor data
    $shortcode_string = '[' . $shortcode . ']';
    $wrapper_class = 'mt_template_wrapper mt_template_' . str_replace('mt_', '', $shortcode);
    
    if ($template_type === 'container') {
        // Container mode structure
        $data = [
            [
                'id' => wp_generate_uuid4(),
                'elType' => 'container',
                'settings' => [
                    'css_classes' => $wrapper_class
                ],
                'elements' => [
                    [
                        'id' => wp_generate_uuid4(),
                        'elType' => 'widget',
                        'widgetType' => 'shortcode',
                        'settings' => [
                            'shortcode' => $shortcode_string
                        ]
                    ]
                ]
            ]
        ];
    } else {
        // Section mode structure
        $data = [
            [
                'id' => wp_generate_uuid4(),
                'elType' => 'section',
                'settings' => [
                    'css_classes' => $wrapper_class
                ],
                'elements' => [
                    [
                        'id' => wp_generate_uuid4(),
                        'elType' => 'column',
                        'settings' => [
                            '_column_size' => 100
                        ],
                        'elements' => [
                            [
                                'id' => wp_generate_uuid4(),
                                'elType' => 'widget',
                                'widgetType' => 'shortcode',
                                'settings' => [
                                    'shortcode' => $shortcode_string
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    $elementor_data = wp_json_encode($data);
    
    // Prepare post data
    $post_data = [
        'post_title' => $title,
        'post_status' => 'publish',
        'post_type' => 'elementor_library'
    ];
    
    // Create or update post
    if (!empty($existing)) {
        $post_data['ID'] = $existing[0]->ID;
        $post_id = wp_update_post($post_data);
        echo "  - Updated existing template (ID: {$existing[0]->ID})\n";
    } else {
        $post_id = wp_insert_post($post_data);
        echo "  - Created new template (ID: $post_id)\n";
    }
    
    if (is_wp_error($post_id)) {
        echo "  - Error: " . $post_id->get_error_message() . "\n";
        continue;
    }
    
    // Update post meta
    update_post_meta($post_id, '_elementor_edit_mode', 'builder');
    update_post_meta($post_id, '_elementor_template_type', $template_type);
    update_post_meta($post_id, '_elementor_data', $elementor_data);
    update_post_meta($post_id, '_elementor_version', ELEMENTOR_VERSION);
    update_post_meta($post_id, '_wp_page_template', 'default');
    
    echo "  - Shortcode: $shortcode_string\n";
    echo "  - Success!\n\n";
}

echo "All templates created successfully!\n";