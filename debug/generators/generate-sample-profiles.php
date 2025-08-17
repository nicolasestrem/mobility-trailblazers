<?php
/**
 * Generate Sample Profiles
 *
 * @package MobilityTrailblazers
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle generation
$generation_result = null;
if (isset($_POST['generate_samples']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_generate_samples')) {
    $count = intval($_POST['sample_count']);
    $category = sanitize_text_field($_POST['category']);
    
    $generated = 0;
    $errors = [];
    
    // Sample data templates
    $first_names = ['Anna', 'Peter', 'Maria', 'Thomas', 'Julia', 'Michael', 'Sandra', 'Stefan', 'Lisa', 'Christian'];
    $last_names = ['Schmidt', 'Weber', 'Fischer', 'Klein', 'Wagner', 'Müller', 'Braun', 'Wolf', 'Meyer', 'Hoffmann'];
    $positions = ['CEO', 'CTO', 'Director', 'Head of Innovation', 'Founder', 'Co-Founder', 'Manager', 'Lead', 'Consultant', 'Expert'];
    $organizations = ['Mobility Solutions GmbH', 'Future Transport AG', 'Smart City Innovations', 'Urban Mobility Hub', 'Green Transit Systems'];
    
    for ($i = 0; $i < $count; $i++) {
        $first = $first_names[array_rand($first_names)];
        $last = $last_names[array_rand($last_names)];
        $name = $first . ' ' . $last;
        
        // Check if already exists
        $existing = get_page_by_title($name, OBJECT, 'mt_candidate');
        if ($existing) {
            $name .= ' ' . uniqid();
        }
        
        $post_data = [
            'post_title' => $name,
            'post_type' => 'mt_candidate',
            'post_status' => 'draft',
            'post_content' => sprintf(
                'Mut & Pioniergeist: %s hat innovative Ansätze in der Mobilität entwickelt. ' .
                'Innovationsgrad: Entwicklung neuer Technologien für nachhaltige Mobilität. ' .
                'Umsetzungskraft & Wirkung: Erfolgreiche Implementierung in mehreren Städten. ' .
                'Relevanz für die Mobilitätswende: Direkte Auswirkung auf CO2-Reduktion. ' .
                'Vorbildfunktion & Sichtbarkeit: Regelmäßige Präsenz auf Fachkonferenzen.',
                $name
            )
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Add meta data
            update_post_meta($post_id, '_mt_display_name', $name);
            update_post_meta($post_id, '_mt_organization', $organizations[array_rand($organizations)]);
            update_post_meta($post_id, '_mt_position', $positions[array_rand($positions)]);
            update_post_meta($post_id, '_mt_linkedin', 'https://linkedin.com/in/' . strtolower(str_replace(' ', '', $name)));
            update_post_meta($post_id, '_mt_website', 'https://example-' . strtolower($last) . '.com');
            update_post_meta($post_id, '_mt_email', strtolower($first) . '.' . strtolower($last) . '@example.com');
            update_post_meta($post_id, '_mt_top50', rand(0, 1) ? 'yes' : 'no');
            update_post_meta($post_id, '_mt_nominator', $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)]);
            
            // Add evaluation criteria scores (sample)
            update_post_meta($post_id, '_mt_courage', 'Zeigt außergewöhnlichen Mut bei der Umsetzung neuer Mobilitätskonzepte.');
            update_post_meta($post_id, '_mt_innovation', 'Innovative Ansätze zur Lösung von Mobilitätsproblemen.');
            update_post_meta($post_id, '_mt_implementation', 'Erfolgreiche Umsetzung mit messbaren Ergebnissen.');
            update_post_meta($post_id, '_mt_relevance', 'Hohe Relevanz für die nachhaltige Mobilitätswende.');
            update_post_meta($post_id, '_mt_visibility', 'Sichtbare Vorbildfunktion in der Branche.');
            
            // Add to category
            if (!empty($category)) {
                wp_set_post_terms($post_id, [$category], 'mt_award_category');
            }
            
            $generated++;
        } else {
            $errors[] = sprintf(__('Failed to create candidate: %s', 'mobility-trailblazers'), $name);
        }
    }
    
    $generation_result = [
        'success' => true,
        'generated' => $generated,
        'requested' => $count,
        'errors' => $errors
    ];
}

// Get categories
$categories = get_terms([
    'taxonomy' => 'mt_award_category',
    'hide_empty' => false
]);

// If no categories exist, create defaults
if (empty($categories)) {
    $default_categories = [
        'Start-ups & Scale-ups',
        'Etablierte Unternehmen',
        'Governance & Verwaltungen'
    ];
    
    foreach ($default_categories as $cat_name) {
        wp_insert_term($cat_name, 'mt_award_category');
    }
    
    $categories = get_terms([
        'taxonomy' => 'mt_award_category',
        'hide_empty' => false
    ]);
}
?>

<div class="wrap">
    <h1><?php _e('Generate Sample Profiles', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($generation_result): ?>
        <div class="notice notice-<?php echo empty($generation_result['errors']) ? 'success' : 'warning'; ?>">
            <p>
                <?php printf(
                    __('Generated %d of %d sample profiles.', 'mobility-trailblazers'),
                    $generation_result['generated'],
                    $generation_result['requested']
                ); ?>
            </p>
            <?php if (!empty($generation_result['errors'])): ?>
                <ul>
                    <?php foreach ($generation_result['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Sample Profile Generator', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Generate sample candidate profiles for testing purposes. These will be created as drafts.', 'mobility-trailblazers'); ?></p>
        
        <form method="post">
            <?php wp_nonce_field('mt_generate_samples'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sample_count"><?php _e('Number of Profiles', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="sample_count" id="sample_count" value="5" min="1" max="50" />
                        <p class="description"><?php _e('How many sample profiles to generate (max 50).', 'mobility-trailblazers'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="category"><?php _e('Category', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <select name="category" id="category">
                            <option value=""><?php _e('-- Random --', 'mobility-trailblazers'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->name); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Assign all generated profiles to this category.', 'mobility-trailblazers'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="generate_samples" class="button button-primary">
                    <?php _e('Generate Sample Profiles', 'mobility-trailblazers'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2><?php _e('Sample Data Information', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Generated profiles will include:', 'mobility-trailblazers'); ?></p>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php _e('Random German names', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Sample organizations and positions', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Placeholder LinkedIn and website URLs', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Sample evaluation criteria text', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Random Top 50 status', 'mobility-trailblazers'); ?></li>
        </ul>
        <p><strong><?php _e('Note:', 'mobility-trailblazers'); ?></strong> <?php _e('All profiles are created as drafts to prevent them from appearing publicly.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <div class="card">
        <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=mt_candidate&post_status=draft'); ?>" class="button">
                <?php _e('View Draft Candidates', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=mt_candidate'); ?>" class="button">
                <?php _e('View All Candidates', 'mobility-trailblazers'); ?>
            </a>
        </p>
    </div>
</div>
