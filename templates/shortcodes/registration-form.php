<?php
/**
 * Registration Form Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-registration-form-wrapper">
    <form id="mt-registration-form" class="mt-registration-form" enctype="multipart/form-data">
        <?php wp_nonce_field('mt_registration', 'mt_registration_nonce'); ?>
        
        <div class="mt-form-section">
            <h3><?php _e('Personal Information', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-form-row">
                <div class="mt-form-field">
                    <label for="mt-first-name"><?php _e('First Name', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                    <input type="text" id="mt-first-name" name="first_name" required>
                </div>
                
                <div class="mt-form-field">
                    <label for="mt-last-name"><?php _e('Last Name', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                    <input type="text" id="mt-last-name" name="last_name" required>
                </div>
            </div>
            
            <div class="mt-form-row">
                <div class="mt-form-field">
                    <label for="mt-email"><?php _e('Email Address', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                    <input type="email" id="mt-email" name="email" required>
                </div>
                
                <div class="mt-form-field">
                    <label for="mt-phone"><?php _e('Phone Number', 'mobility-trailblazers'); ?></label>
                    <input type="tel" id="mt-phone" name="phone">
                </div>
            </div>
        </div>
        
        <div class="mt-form-section">
            <h3><?php _e('Professional Information', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-form-row">
                <div class="mt-form-field">
                    <label for="mt-company"><?php _e('Company/Organization', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                    <input type="text" id="mt-company" name="company" required>
                </div>
                
                <div class="mt-form-field">
                    <label for="mt-position"><?php _e('Position/Title', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                    <input type="text" id="mt-position" name="position" required>
                </div>
            </div>
            
            <div class="mt-form-row">
                <div class="mt-form-field">
                    <label for="mt-location"><?php _e('Location', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                    <input type="text" id="mt-location" name="location" placeholder="<?php _e('City, Country', 'mobility-trailblazers'); ?>" required>
                </div>
                
                <div class="mt-form-field">
                    <label for="mt-website"><?php _e('Website', 'mobility-trailblazers'); ?></label>
                    <input type="url" id="mt-website" name="website" placeholder="https://">
                </div>
            </div>
        </div>
        
        <div class="mt-form-section">
            <h3><?php _e('Innovation Details', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-form-field">
                <label for="mt-innovation-title"><?php _e('Innovation Title', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                <input type="text" id="mt-innovation-title" name="innovation_title" required>
            </div>
            
            <div class="mt-form-field">
                <label for="mt-innovation-description"><?php _e('Innovation Description', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                <textarea id="mt-innovation-description" name="innovation_description" rows="6" required></textarea>
                <p class="description"><?php _e('Describe your innovation and its impact on mobility (500-1000 words)', 'mobility-trailblazers'); ?></p>
            </div>
            
            <?php if ($atts['show_categories'] === 'yes'): ?>
                <div class="mt-form-field">
                    <label for="mt-category"><?php _e('Category', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                    <select id="mt-category" name="category" required>
                        <option value=""><?php _e('Select a category', 'mobility-trailblazers'); ?></option>
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'mt_category',
                            'hide_empty' => false,
                        ));
                        foreach ($categories as $category):
                        ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="mt-form-field">
                <label for="mt-impact-metrics"><?php _e('Impact Metrics', 'mobility-trailblazers'); ?></label>
                <textarea id="mt-impact-metrics" name="impact_metrics" rows="4"></textarea>
                <p class="description"><?php _e('Provide quantifiable metrics demonstrating your impact', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <div class="mt-form-section">
            <h3><?php _e('Supporting Materials', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-form-field">
                <label for="mt-photo"><?php _e('Profile Photo', 'mobility-trailblazers'); ?></label>
                <input type="file" id="mt-photo" name="photo" accept="image/*">
                <p class="description"><?php _e('Professional headshot (JPG, PNG, max 2MB)', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-form-field">
                <label for="mt-innovation-file"><?php _e('Innovation Documentation', 'mobility-trailblazers'); ?></label>
                <input type="file" id="mt-innovation-file" name="innovation_file" accept=".pdf,.doc,.docx">
                <p class="description"><?php _e('Additional documentation (PDF, DOC, max 10MB)', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-form-field">
                <label for="mt-video-url"><?php _e('Video URL', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt-video-url" name="video_url" placeholder="https://youtube.com/watch?v=...">
                <p class="description"><?php _e('Link to a video presentation (YouTube, Vimeo)', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <div class="mt-form-section">
            <div class="mt-form-field">
                <label>
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <?php printf(
                        __('I agree to the <a href="%s" target="_blank">Terms and Conditions</a> and <a href="%s" target="_blank">Privacy Policy</a>', 'mobility-trailblazers'),
                        '#',
                        '#'
                    ); ?> <span class="required">*</span>
                </label>
            </div>
            
            <div class="mt-form-field">
                <label>
                    <input type="checkbox" name="newsletter_consent" value="1">
                    <?php _e('I would like to receive updates about the Mobility Trailblazers Award', 'mobility-trailblazers'); ?>
                </label>
            </div>
        </div>
        
        <div class="mt-form-actions">
            <button type="submit" class="button button-primary button-large">
                <?php _e('Submit Registration', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </form>
    
    <div id="registration-success" class="mt-success-message" style="display: none;">
        <h3><?php _e('Registration Successful!', 'mobility-trailblazers'); ?></h3>
        <p><?php _e('Thank you for your submission. We will review your application and contact you soon.', 'mobility-trailblazers'); ?></p>
    </div>
</div> 