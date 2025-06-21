<?php
/**
 * Meta boxes handler class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Meta_Boxes
 * Handles custom meta boxes for candidates and jury members
 */
class MT_Meta_Boxes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save meta boxes
        add_action('save_post_mt_candidate', array($this, 'save_candidate_meta'), 10, 3);
        add_action('save_post_mt_jury', array($this, 'save_jury_meta'), 10, 3);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Candidate meta boxes
        add_meta_box(
            'mt_candidate_details',
            __('Candidate Details', 'mobility-trailblazers'),
            array($this, 'render_candidate_details_meta_box'),
            'mt_candidate',
            'normal',
            'high'
        );
        
        add_meta_box(
            'mt_candidate_innovation',
            __('Innovation Details', 'mobility-trailblazers'),
            array($this, 'render_candidate_innovation_meta_box'),
            'mt_candidate',
            'normal',
            'high'
        );
        
        add_meta_box(
            'mt_candidate_impact',
            __('Impact & Metrics', 'mobility-trailblazers'),
            array($this, 'render_candidate_impact_meta_box'),
            'mt_candidate',
            'normal',
            'default'
        );
        
        add_meta_box(
            'mt_candidate_media',
            __('Media & Documents', 'mobility-trailblazers'),
            array($this, 'render_candidate_media_meta_box'),
            'mt_candidate',
            'side',
            'default'
        );
        
        add_meta_box(
            'mt_candidate_evaluation',
            __('Evaluation Summary', 'mobility-trailblazers'),
            array($this, 'render_candidate_evaluation_meta_box'),
            'mt_candidate',
            'side',
            'default'
        );
        
        // Jury member meta boxes
        add_meta_box(
            'mt_jury_details',
            __('Jury Member Details', 'mobility-trailblazers'),
            array($this, 'render_jury_details_meta_box'),
            'mt_jury',
            'normal',
            'high'
        );
        
        add_meta_box(
            'mt_jury_expertise',
            __('Expertise & Background', 'mobility-trailblazers'),
            array($this, 'render_jury_expertise_meta_box'),
            'mt_jury',
            'normal',
            'default'
        );
        
        add_meta_box(
            'mt_jury_account',
            __('User Account', 'mobility-trailblazers'),
            array($this, 'render_jury_account_meta_box'),
            'mt_jury',
            'side',
            'default'
        );
        
        add_meta_box(
            'mt_jury_statistics',
            __('Evaluation Statistics', 'mobility-trailblazers'),
            array($this, 'render_jury_statistics_meta_box'),
            'mt_jury',
            'side',
            'default'
        );
    }
    
    /**
     * Render candidate details meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_candidate_details_meta_box($post) {
        // Add nonce field
        wp_nonce_field('mt_save_candidate_meta', 'mt_candidate_meta_nonce');
        
        // Get meta values
        $company = get_post_meta($post->ID, '_mt_company', true);
        $position = get_post_meta($post->ID, '_mt_position', true);
        $location = get_post_meta($post->ID, '_mt_location', true);
        $email = get_post_meta($post->ID, '_mt_email', true);
        $phone = get_post_meta($post->ID, '_mt_phone', true);
        $website = get_post_meta($post->ID, '_mt_website', true);
        $linkedin = get_post_meta($post->ID, '_mt_linkedin', true);
        $founded_year = get_post_meta($post->ID, '_mt_founded_year', true);
        $employees = get_post_meta($post->ID, '_mt_employees', true);
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-field-group">
                <label for="mt_company"><?php _e('Company/Organization', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_company" name="mt_company" value="<?php echo esc_attr($company); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-group">
                <label for="mt_position"><?php _e('Position/Title', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_position" name="mt_position" value="<?php echo esc_attr($position); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-group">
                <label for="mt_location"><?php _e('Location', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_location" name="mt_location" value="<?php echo esc_attr($location); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-row">
                <div class="mt-field-group">
                    <label for="mt_email"><?php _e('Email', 'mobility-trailblazers'); ?></label>
                    <input type="email" id="mt_email" name="mt_email" value="<?php echo esc_attr($email); ?>" class="regular-text" />
                </div>
                
                <div class="mt-field-group">
                    <label for="mt_phone"><?php _e('Phone', 'mobility-trailblazers'); ?></label>
                    <input type="tel" id="mt_phone" name="mt_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" />
                </div>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_website"><?php _e('Website', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt_website" name="mt_website" value="<?php echo esc_url($website); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-group">
                <label for="mt_linkedin"><?php _e('LinkedIn Profile', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt_linkedin" name="mt_linkedin" value="<?php echo esc_url($linkedin); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-row">
                <div class="mt-field-group">
                    <label for="mt_founded_year"><?php _e('Founded Year', 'mobility-trailblazers'); ?></label>
                    <input type="number" id="mt_founded_year" name="mt_founded_year" value="<?php echo esc_attr($founded_year); ?>" min="1900" max="<?php echo date('Y'); ?>" />
                </div>
                
                <div class="mt-field-group">
                    <label for="mt_employees"><?php _e('Number of Employees', 'mobility-trailblazers'); ?></label>
                    <select id="mt_employees" name="mt_employees">
                        <option value=""><?php _e('Select...', 'mobility-trailblazers'); ?></option>
                        <option value="1-10" <?php selected($employees, '1-10'); ?>>1-10</option>
                        <option value="11-50" <?php selected($employees, '11-50'); ?>>11-50</option>
                        <option value="51-200" <?php selected($employees, '51-200'); ?>>51-200</option>
                        <option value="201-500" <?php selected($employees, '201-500'); ?>>201-500</option>
                        <option value="501-1000" <?php selected($employees, '501-1000'); ?>>501-1000</option>
                        <option value="1000+" <?php selected($employees, '1000+'); ?>>1000+</option>
                    </select>
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render candidate innovation meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_candidate_innovation_meta_box($post) {
        // Get meta values
        $innovation_title = get_post_meta($post->ID, '_mt_innovation_title', true);
        $innovation_description = get_post_meta($post->ID, '_mt_innovation_description', true);
        $innovation_stage = get_post_meta($post->ID, '_mt_innovation_stage', true);
        $target_market = get_post_meta($post->ID, '_mt_target_market', true);
        $unique_selling_points = get_post_meta($post->ID, '_mt_unique_selling_points', true);
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-field-group">
                <label for="mt_innovation_title"><?php _e('Innovation Title', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_innovation_title" name="mt_innovation_title" value="<?php echo esc_attr($innovation_title); ?>" class="large-text" />
                <p class="description"><?php _e('Brief title of the innovation or solution', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_innovation_description"><?php _e('Innovation Description', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_innovation_description" name="mt_innovation_description" rows="5" class="large-text"><?php echo esc_textarea($innovation_description); ?></textarea>
                <p class="description"><?php _e('Detailed description of the innovation and how it works', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_innovation_stage"><?php _e('Innovation Stage', 'mobility-trailblazers'); ?></label>
                <select id="mt_innovation_stage" name="mt_innovation_stage">
                    <option value=""><?php _e('Select...', 'mobility-trailblazers'); ?></option>
                    <option value="concept" <?php selected($innovation_stage, 'concept'); ?>><?php _e('Concept/Idea', 'mobility-trailblazers'); ?></option>
                    <option value="prototype" <?php selected($innovation_stage, 'prototype'); ?>><?php _e('Prototype', 'mobility-trailblazers'); ?></option>
                    <option value="pilot" <?php selected($innovation_stage, 'pilot'); ?>><?php _e('Pilot/Testing', 'mobility-trailblazers'); ?></option>
                    <option value="market" <?php selected($innovation_stage, 'market'); ?>><?php _e('In Market', 'mobility-trailblazers'); ?></option>
                    <option value="scaling" <?php selected($innovation_stage, 'scaling'); ?>><?php _e('Scaling', 'mobility-trailblazers'); ?></option>
                </select>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_target_market"><?php _e('Target Market', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_target_market" name="mt_target_market" value="<?php echo esc_attr($target_market); ?>" class="large-text" />
                <p class="description"><?php _e('Primary target market or customer segment', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_unique_selling_points"><?php _e('Unique Selling Points', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_unique_selling_points" name="mt_unique_selling_points" rows="3" class="large-text"><?php echo esc_textarea($unique_selling_points); ?></textarea>
                <p class="description"><?php _e('Key differentiators and competitive advantages', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render candidate impact meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_candidate_impact_meta_box($post) {
        // Get meta values
        $users_reached = get_post_meta($post->ID, '_mt_users_reached', true);
        $revenue = get_post_meta($post->ID, '_mt_revenue', true);
        $funding_raised = get_post_meta($post->ID, '_mt_funding_raised', true);
        $co2_saved = get_post_meta($post->ID, '_mt_co2_saved', true);
        $awards_recognition = get_post_meta($post->ID, '_mt_awards_recognition', true);
        $key_partnerships = get_post_meta($post->ID, '_mt_key_partnerships', true);
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-field-row">
                <div class="mt-field-group">
                    <label for="mt_users_reached"><?php _e('Users/Customers Reached', 'mobility-trailblazers'); ?></label>
                    <input type="text" id="mt_users_reached" name="mt_users_reached" value="<?php echo esc_attr($users_reached); ?>" />
                </div>
                
                <div class="mt-field-group">
                    <label for="mt_revenue"><?php _e('Annual Revenue (€)', 'mobility-trailblazers'); ?></label>
                    <input type="text" id="mt_revenue" name="mt_revenue" value="<?php echo esc_attr($revenue); ?>" />
                </div>
            </div>
            
            <div class="mt-field-row">
                <div class="mt-field-group">
                    <label for="mt_funding_raised"><?php _e('Funding Raised (€)', 'mobility-trailblazers'); ?></label>
                    <input type="text" id="mt_funding_raised" name="mt_funding_raised" value="<?php echo esc_attr($funding_raised); ?>" />
                </div>
                
                <div class="mt-field-group">
                    <label for="mt_co2_saved"><?php _e('CO2 Saved (tons/year)', 'mobility-trailblazers'); ?></label>
                    <input type="text" id="mt_co2_saved" name="mt_co2_saved" value="<?php echo esc_attr($co2_saved); ?>" />
                </div>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_awards_recognition"><?php _e('Awards & Recognition', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_awards_recognition" name="mt_awards_recognition" rows="3" class="large-text"><?php echo esc_textarea($awards_recognition); ?></textarea>
                <p class="description"><?php _e('Previous awards, certifications, or recognition received', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_key_partnerships"><?php _e('Key Partnerships', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_key_partnerships" name="mt_key_partnerships" rows="3" class="large-text"><?php echo esc_textarea($key_partnerships); ?></textarea>
                <p class="description"><?php _e('Strategic partnerships and collaborations', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render candidate media meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_candidate_media_meta_box($post) {
        // Get meta values
        $video_url = get_post_meta($post->ID, '_mt_video_url', true);
        $presentation_url = get_post_meta($post->ID, '_mt_presentation_url', true);
        $additional_docs = get_post_meta($post->ID, '_mt_additional_docs', true);
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-field-group">
                <label for="mt_video_url"><?php _e('Video URL', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt_video_url" name="mt_video_url" value="<?php echo esc_url($video_url); ?>" class="large-text" />
                <p class="description"><?php _e('YouTube or Vimeo URL', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_presentation_url"><?php _e('Presentation URL', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt_presentation_url" name="mt_presentation_url" value="<?php echo esc_url($presentation_url); ?>" class="large-text" />
                <p class="description"><?php _e('Link to pitch deck or presentation', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_additional_docs"><?php _e('Additional Documents', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_additional_docs" name="mt_additional_docs" rows="3" class="large-text"><?php echo esc_textarea($additional_docs); ?></textarea>
                <p class="description"><?php _e('Links to additional supporting documents', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render candidate evaluation meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_candidate_evaluation_meta_box($post) {
        global $wpdb;
        
        // Get evaluation statistics
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_evaluations,
                AVG(total_score) as average_score,
                MIN(total_score) as min_score,
                MAX(total_score) as max_score
             FROM $table_name 
             WHERE candidate_id = %d AND is_active = 1",
            $post->ID
        ));
        
        // Get assigned jury members
        $assigned_jury = mt_get_assigned_jury_members($post->ID);
        $completed_evaluations = 0;
        
        if (!empty($assigned_jury)) {
            foreach ($assigned_jury as $jury_id) {
                if (mt_has_evaluated($post->ID, $jury_id)) {
                    $completed_evaluations++;
                }
            }
        }
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-stat-row">
                <strong><?php _e('Average Score:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo $stats->average_score ? number_format($stats->average_score, 1) . ' / 50' : '—'; ?></span>
            </div>
            
            <div class="mt-stat-row">
                <strong><?php _e('Evaluations:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo $completed_evaluations . ' / ' . count($assigned_jury); ?></span>
            </div>
            
            <?php if ($stats->total_evaluations > 0) : ?>
                <div class="mt-stat-row">
                    <strong><?php _e('Score Range:', 'mobility-trailblazers'); ?></strong>
                    <span><?php echo $stats->min_score . ' - ' . $stats->max_score; ?></span>
                </div>
            <?php endif; ?>
            
            <hr>
            
            <div class="mt-field-group">
                <label for="mt_status"><?php _e('Status', 'mobility-trailblazers'); ?></label>
                <select id="mt_status" name="mt_status">
                    <?php
                    $current_status = get_post_meta($post->ID, '_mt_status', true);
                    $statuses = array(
                        'pending' => __('Pending', 'mobility-trailblazers'),
                        'approved' => __('Approved', 'mobility-trailblazers'),
                        'rejected' => __('Rejected', 'mobility-trailblazers'),
                        'shortlisted' => __('Shortlisted', 'mobility-trailblazers'),
                        'winner' => __('Winner', 'mobility-trailblazers'),
                    );
                    
                    foreach ($statuses as $value => $label) {
                        echo '<option value="' . esc_attr($value) . '" ' . selected($current_status, $value, false) . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_final_score"><?php _e('Final Score', 'mobility-trailblazers'); ?></label>
                <input type="number" id="mt_final_score" name="mt_final_score" value="<?php echo esc_attr(get_post_meta($post->ID, '_mt_final_score', true)); ?>" min="0" max="50" step="0.1" />
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render jury details meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_jury_details_meta_box($post) {
        // Add nonce field
        wp_nonce_field('mt_save_jury_meta', 'mt_jury_meta_nonce');
        
        // Get meta values
        $organization = get_post_meta($post->ID, '_mt_organization', true);
        $position = get_post_meta($post->ID, '_mt_position', true);
        $email = get_post_meta($post->ID, '_mt_email', true);
        $phone = get_post_meta($post->ID, '_mt_phone', true);
        $linkedin = get_post_meta($post->ID, '_mt_linkedin', true);
        $jury_role = get_post_meta($post->ID, '_mt_jury_role', true);
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-field-group">
                <label for="mt_organization"><?php _e('Organization', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_organization" name="mt_organization" value="<?php echo esc_attr($organization); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-group">
                <label for="mt_position"><?php _e('Position/Title', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_position" name="mt_position" value="<?php echo esc_attr($position); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-row">
                <div class="mt-field-group">
                    <label for="mt_email"><?php _e('Email', 'mobility-trailblazers'); ?></label>
                    <input type="email" id="mt_email" name="mt_email" value="<?php echo esc_attr($email); ?>" class="regular-text" />
                </div>
                
                <div class="mt-field-group">
                    <label for="mt_phone"><?php _e('Phone', 'mobility-trailblazers'); ?></label>
                    <input type="tel" id="mt_phone" name="mt_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" />
                </div>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_linkedin"><?php _e('LinkedIn Profile', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt_linkedin" name="mt_linkedin" value="<?php echo esc_url($linkedin); ?>" class="large-text" />
            </div>
            
            <div class="mt-field-group">
                <label for="mt_jury_role"><?php _e('Jury Role', 'mobility-trailblazers'); ?></label>
                <select id="mt_jury_role" name="mt_jury_role">
                    <option value="member" <?php selected($jury_role, 'member'); ?>><?php _e('Member', 'mobility-trailblazers'); ?></option>
                    <option value="vice_president" <?php selected($jury_role, 'vice_president'); ?>><?php _e('Vice President', 'mobility-trailblazers'); ?></option>
                    <option value="president" <?php selected($jury_role, 'president'); ?>><?php _e('President', 'mobility-trailblazers'); ?></option>
                </select>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render jury expertise meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_jury_expertise_meta_box($post) {
        // Get meta values
        $expertise_areas = get_post_meta($post->ID, '_mt_expertise_areas', true);
        $biography = get_post_meta($post->ID, '_mt_biography', true);
        $qualifications = get_post_meta($post->ID, '_mt_qualifications', true);
        
        if (!is_array($expertise_areas)) {
            $expertise_areas = array();
        }
        
        $available_expertise = array(
            'sustainable_mobility' => __('Sustainable Mobility', 'mobility-trailblazers'),
            'urban_planning' => __('Urban Planning', 'mobility-trailblazers'),
            'technology_innovation' => __('Technology & Innovation', 'mobility-trailblazers'),
            'public_transport' => __('Public Transport', 'mobility-trailblazers'),
            'shared_mobility' => __('Shared Mobility', 'mobility-trailblazers'),
            'electric_vehicles' => __('Electric Vehicles', 'mobility-trailblazers'),
            'autonomous_driving' => __('Autonomous Driving', 'mobility-trailblazers'),
            'logistics' => __('Logistics & Freight', 'mobility-trailblazers'),
            'policy_regulation' => __('Policy & Regulation', 'mobility-trailblazers'),
            'investment_finance' => __('Investment & Finance', 'mobility-trailblazers'),
        );
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-field-group">
                <label><?php _e('Expertise Areas', 'mobility-trailblazers'); ?></label>
                <div class="mt-checkbox-group">
                    <?php foreach ($available_expertise as $value => $label) : ?>
                        <label>
                            <input type="checkbox" name="mt_expertise_areas[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, $expertise_areas)); ?> />
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_biography"><?php _e('Biography', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_biography" name="mt_biography" rows="5" class="large-text"><?php echo esc_textarea($biography); ?></textarea>
                <p class="description"><?php _e('Brief professional biography', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-field-group">
                <label for="mt_qualifications"><?php _e('Key Qualifications', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_qualifications" name="mt_qualifications" rows="3" class="large-text"><?php echo esc_textarea($qualifications); ?></textarea>
                <p class="description"><?php _e('Relevant qualifications and achievements', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render jury account meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_jury_account_meta_box($post) {
        $user_id = get_post_meta($post->ID, '_mt_user_id', true);
        $user = $user_id ? get_user_by('id', $user_id) : null;
        ?>
        
        <div class="mt-meta-box">
            <?php if ($user) : ?>
                <div class="mt-stat-row">
                    <strong><?php _e('Username:', 'mobility-trailblazers'); ?></strong>
                    <span><?php echo esc_html($user->user_login); ?></span>
                </div>
                
                <div class="mt-stat-row">
                    <strong><?php _e('Email:', 'mobility-trailblazers'); ?></strong>
                    <span><?php echo esc_html($user->user_email); ?></span>
                </div>
                
                <div class="mt-stat-row">
                    <strong><?php _e('Status:', 'mobility-trailblazers'); ?></strong>
                    <span class="mt-status status-active"><?php _e('Active', 'mobility-trailblazers'); ?></span>
                </div>
                
                <hr>
                
                <p>
                    <a href="<?php echo get_edit_user_link($user_id); ?>" class="button"><?php _e('Edit User', 'mobility-trailblazers'); ?></a>
                </p>
            <?php else : ?>
                <p><?php _e('No user account created yet.', 'mobility-trailblazers'); ?></p>
                
                <p>
                    <button type="button" class="button button-primary" id="mt-create-jury-user"><?php _e('Create User Account', 'mobility-trailblazers'); ?></button>
                </p>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#mt-create-jury-user').on('click', function() {
                        if (confirm('<?php _e('Create a WordPress user account for this jury member?', 'mobility-trailblazers'); ?>')) {
                            // This would trigger an AJAX call to create the user
                            alert('<?php _e('User creation functionality to be implemented via AJAX', 'mobility-trailblazers'); ?>');
                        }
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        
        <?php
    }
    
    /**
     * Render jury statistics meta box
     *
     * @param WP_Post $post Current post object
     */
    public function render_jury_statistics_meta_box($post) {
        // Get assigned candidates
        $assigned_candidates = mt_get_assigned_candidates($post->ID);
        $completed_evaluations = 0;
        
        if (!empty($assigned_candidates)) {
            foreach ($assigned_candidates as $candidate_id) {
                if (mt_has_evaluated($candidate_id, $post->ID)) {
                    $completed_evaluations++;
                }
            }
        }
        
        // Get evaluation statistics
        $stats = mt_get_evaluation_statistics(array('jury_member_id' => $post->ID));
        ?>
        
        <div class="mt-meta-box">
            <div class="mt-stat-row">
                <strong><?php _e('Assigned:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo count($assigned_candidates); ?> <?php _e('candidates', 'mobility-trailblazers'); ?></span>
            </div>
            
            <div class="mt-stat-row">
                <strong><?php _e('Completed:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo $completed_evaluations; ?> (<?php echo count($assigned_candidates) > 0 ? round(($completed_evaluations / count($assigned_candidates)) * 100) : 0; ?>%)</span>
            </div>
            
            <?php if ($stats && $stats->total_evaluations > 0) : ?>
                <hr>
                
                <div class="mt-stat-row">
                    <strong><?php _e('Avg Score Given:', 'mobility-trailblazers'); ?></strong>
                    <span><?php echo number_format($stats->average_score, 1); ?> / 50</span>
                </div>
                
                <div class="mt-stat-row">
                    <strong><?php _e('Score Range:', 'mobility-trailblazers'); ?></strong>
                    <span><?php echo $stats->min_score . ' - ' . $stats->max_score; ?></span>
                </div>
            <?php endif; ?>
            
            <hr>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=mt-voting-results&jury_member=' . $post->ID); ?>" class="button"><?php _e('View Evaluations', 'mobility-trailblazers'); ?></a>
            </p>
        </div>
        
        <?php
    }
    
    /**
     * Save candidate meta
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function save_candidate_meta($post_id, $post, $update) {
        // Check nonce
        if (!isset($_POST['mt_candidate_meta_nonce']) || !wp_verify_nonce($_POST['mt_candidate_meta_nonce'], 'mt_save_candidate_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            'mt_company' => 'sanitize_text_field',
            'mt_position' => 'sanitize_text_field',
            'mt_location' => 'sanitize_text_field',
            'mt_email' => 'sanitize_email',
            'mt_phone' => 'sanitize_text_field',
            'mt_website' => 'esc_url_raw',
            'mt_linkedin' => 'esc_url_raw',
            'mt_founded_year' => 'absint',
            'mt_employees' => 'sanitize_text_field',
            'mt_innovation_title' => 'sanitize_text_field',
            'mt_innovation_description' => 'sanitize_textarea_field',
            'mt_innovation_stage' => 'sanitize_text_field',
            'mt_target_market' => 'sanitize_text_field',
            'mt_unique_selling_points' => 'sanitize_textarea_field',
            'mt_users_reached' => 'sanitize_text_field',
            'mt_revenue' => 'sanitize_text_field',
            'mt_funding_raised' => 'sanitize_text_field',
            'mt_co2_saved' => 'sanitize_text_field',
            'mt_awards_recognition' => 'sanitize_textarea_field',
            'mt_key_partnerships' => 'sanitize_textarea_field',
            'mt_video_url' => 'esc_url_raw',
            'mt_presentation_url' => 'esc_url_raw',
            'mt_additional_docs' => 'sanitize_textarea_field',
            'mt_status' => 'sanitize_text_field',
            'mt_final_score' => 'floatval',
        );
        
        foreach ($meta_fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_callback, $_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * Save jury meta
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function save_jury_meta($post_id, $post, $update) {
        // Check nonce
        if (!isset($_POST['mt_jury_meta_nonce']) || !wp_verify_nonce($_POST['mt_jury_meta_nonce'], 'mt_save_jury_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            'mt_organization' => 'sanitize_text_field',
            'mt_position' => 'sanitize_text_field',
            'mt_email' => 'sanitize_email',
            'mt_phone' => 'sanitize_text_field',
            'mt_linkedin' => 'esc_url_raw',
            'mt_jury_role' => 'sanitize_text_field',
            'mt_biography' => 'sanitize_textarea_field',
            'mt_qualifications' => 'sanitize_textarea_field',
        );
        
        foreach ($meta_fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_callback, $_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        // Save expertise areas
        if (isset($_POST['mt_expertise_areas'])) {
            $expertise_areas = array_map('sanitize_text_field', $_POST['mt_expertise_areas']);
            update_post_meta($post_id, '_mt_expertise_areas', $expertise_areas);
        } else {
            update_post_meta($post_id, '_mt_expertise_areas', array());
        }
    }
} 