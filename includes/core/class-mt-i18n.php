<?php
/**
 * Internationalization Management Class
 *
 * @package MobilityTrailblazers
 * @since 2.1.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_I18n
 *
 * Handles all internationalization functionality including language detection,
 * switching, and translation management for the Mobility Trailblazers platform.
 */
class MT_I18n {
    
    /**
     * Supported languages
     *
     * @var array
     */
    private $supported_languages = [
        'en_US' => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'flag' => '🇬🇧'
        ],
        'de_DE' => [
            'code' => 'de',
            'name' => 'German',
            'native_name' => 'Deutsch',
            'flag' => '🇩🇪'
        ]
    ];
    
    /**
     * Current language
     *
     * @var string
     */
    private $current_language;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->current_language = $this->detect_language();
    }
    
    /**
     * Initialize i18n functionality
     *
     * @return void
     */
    public function init() {
        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Add language switcher to admin bar
        add_action('admin_bar_menu', [$this, 'add_admin_bar_language_switcher'], 100);
        
        // Handle language switching
        add_action('init', [$this, 'handle_language_switch']);
        
        // Add language selector to user profile
        add_action('show_user_profile', [$this, 'add_user_language_preference']);
        add_action('edit_user_profile', [$this, 'add_user_language_preference']);
        add_action('personal_options_update', [$this, 'save_user_language_preference']);
        add_action('edit_user_profile_update', [$this, 'save_user_language_preference']);
        
        // Add language settings to plugin settings
        add_filter('mt_settings_fields', [$this, 'add_language_settings']);
        
        // Set locale filter
        add_filter('locale', [$this, 'set_locale']);
        
        // Add body class for current language
        add_filter('body_class', [$this, 'add_language_body_class']);
        add_filter('admin_body_class', [$this, 'add_admin_language_body_class']);
    }
    
    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mobility-trailblazers',
            false,
            dirname(plugin_basename(MT_PLUGIN_FILE)) . '/languages/'
        );
    }
    
    /**
     * Detect current language
     *
     * @return string
     */
    private function detect_language() {
        // Check for language switch request
        if (isset($_GET['mt_lang']) && array_key_exists($_GET['mt_lang'], $this->supported_languages)) {
            return $_GET['mt_lang'];
        }
        
        // Check user preference
        if (is_user_logged_in()) {
            $user_lang = get_user_meta(get_current_user_id(), 'mt_language_preference', true);
            if ($user_lang && array_key_exists($user_lang, $this->supported_languages)) {
                return $user_lang;
            }
        }
        
        // Check cookie
        if (isset($_COOKIE['mt_language']) && array_key_exists($_COOKIE['mt_language'], $this->supported_languages)) {
            return $_COOKIE['mt_language'];
        }
        
        // Check site language setting
        $site_lang = get_option('mt_default_language', '');
        if ($site_lang && array_key_exists($site_lang, $this->supported_languages)) {
            return $site_lang;
        }
        
        // Use WordPress locale
        $locale = get_locale();
        if (array_key_exists($locale, $this->supported_languages)) {
            return $locale;
        }
        
        // Default to German for DACH region
        return 'de_DE';
    }
    
    /**
     * Set locale
     *
     * @param string $locale Current locale
     * @return string Modified locale
     */
    public function set_locale($locale) {
        // Apply locale for plugin context OR when a language preference is set
        if ($this->is_plugin_context() || $this->current_language !== $locale) {
            // Always prefer the detected/selected language for plugin strings
            return $this->current_language;
        }
        
        return $locale;
    }
    
    /**
     * Handle language switching
     *
     * @return void
     */
    public function handle_language_switch() {
        if (!isset($_GET['mt_lang'])) {
            return;
        }
        
        $new_lang = sanitize_text_field($_GET['mt_lang']);
        
        if (!array_key_exists($new_lang, $this->supported_languages)) {
            return;
        }
        
        // Update current language
        $this->current_language = $new_lang;
        
        // Set cookie
        setcookie('mt_language', $new_lang, time() + (86400 * 30), '/');
        
        // Update user preference if logged in
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'mt_language_preference', $new_lang);
        }
        
        // Redirect to remove the query parameter
        $redirect_url = remove_query_arg('mt_lang');
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Add language switcher to admin bar
     *
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar object
     * @return void
     */
    public function add_admin_bar_language_switcher($wp_admin_bar) {
        if (!current_user_can('read')) {
            return;
        }
        
        $current_lang = $this->supported_languages[$this->current_language];
        
        // Add parent node
        $wp_admin_bar->add_node([
            'id' => 'mt-language-switcher',
            'title' => $current_lang['flag'] . ' ' . $current_lang['native_name'],
            'href' => '#',
            'meta' => [
                'class' => 'mt-language-switcher'
            ]
        ]);
        
        // Add language options
        foreach ($this->supported_languages as $locale => $lang) {
            if ($locale === $this->current_language) {
                continue;
            }
            
            $wp_admin_bar->add_node([
                'id' => 'mt-lang-' . $locale,
                'parent' => 'mt-language-switcher',
                'title' => $lang['flag'] . ' ' . $lang['native_name'],
                'href' => add_query_arg('mt_lang', $locale)
            ]);
        }
    }
    
    /**
     * Add user language preference field
     *
     * @param \WP_User $user User object
     * @return void
     */
    public function add_user_language_preference($user) {
        ?>
        <h3><?php esc_html_e('Mobility Trailblazers Language Settings', 'mobility-trailblazers'); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="mt_language_preference">
                        <?php esc_html_e('Preferred Language', 'mobility-trailblazers'); ?>
                    </label>
                </th>
                <td>
                    <select name="mt_language_preference" id="mt_language_preference">
                        <?php
                        $user_lang = get_user_meta($user->ID, 'mt_language_preference', true);
                        foreach ($this->supported_languages as $locale => $lang) {
                            printf(
                                '<option value="%s" %s>%s %s</option>',
                                esc_attr($locale),
                                selected($user_lang, $locale, false),
                                esc_html($lang['flag']),
                                esc_html($lang['native_name'])
                            );
                        }
                        ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Select your preferred language for the Mobility Trailblazers platform.', 'mobility-trailblazers'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save user language preference
     *
     * @param int $user_id User ID
     * @return void
     */
    public function save_user_language_preference($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (isset($_POST['mt_language_preference'])) {
            $lang = sanitize_text_field($_POST['mt_language_preference']);
            if (array_key_exists($lang, $this->supported_languages)) {
                update_user_meta($user_id, 'mt_language_preference', $lang);
            }
        }
    }
    
    /**
     * Add language settings to plugin settings
     *
     * @param array $fields Settings fields
     * @return array Modified settings fields
     */
    public function add_language_settings($fields) {
        $fields['language'] = [
            'title' => __('Language Settings', 'mobility-trailblazers'),
            'fields' => [
                'mt_default_language' => [
                    'label' => __('Default Language', 'mobility-trailblazers'),
                    'type' => 'select',
                    'options' => $this->get_language_options(),
                    'default' => 'de_DE',
                    'description' => __('Select the default language for the platform.', 'mobility-trailblazers')
                ],
                'mt_enable_language_switcher' => [
                    'label' => __('Enable Language Switcher', 'mobility-trailblazers'),
                    'type' => 'checkbox',
                    'default' => '1',
                    'description' => __('Show language switcher in the frontend.', 'mobility-trailblazers')
                ],
                'mt_auto_detect_language' => [
                    'label' => __('Auto-detect Language', 'mobility-trailblazers'),
                    'type' => 'checkbox',
                    'default' => '1',
                    'description' => __('Automatically detect user language based on browser settings.', 'mobility-trailblazers')
                ]
            ]
        ];
        
        return $fields;
    }
    
    /**
     * Get language options for select field
     *
     * @return array
     */
    private function get_language_options() {
        $options = [];
        foreach ($this->supported_languages as $locale => $lang) {
            $options[$locale] = $lang['flag'] . ' ' . $lang['native_name'] . ' (' . $lang['name'] . ')';
        }
        return $options;
    }
    
    /**
     * Add language body class
     *
     * @param array $classes Body classes
     * @return array Modified body classes
     */
    public function add_language_body_class($classes) {
        $classes[] = 'mt-lang-' . $this->supported_languages[$this->current_language]['code'];
        $classes[] = 'mt-locale-' . $this->current_language;
        
        return $classes;
    }
    
    /**
     * Add admin language body class
     *
     * @param string $classes Admin body classes
     * @return string Modified admin body classes
     */
    public function add_admin_language_body_class($classes) {
        $classes .= ' mt-lang-' . $this->supported_languages[$this->current_language]['code'];
        $classes .= ' mt-locale-' . $this->current_language;
        
        return $classes;
    }
    
    /**
     * Check if we're in plugin context
     *
     * @return bool
     */
    private function is_plugin_context() {
        // Check if we're on a plugin admin page
        if (is_admin()) {
            if (function_exists('get_current_screen')) {
                $screen = \get_current_screen();
                if ($screen && (
                    strpos($screen->id, 'mobility-trailblazers') !== false ||
                    strpos($screen->id, 'mt-') !== false
                )) {
                    return true;
                }
            }
        }
        // Check if we're on a frontend page with our shortcodes
        if (!is_admin()) {
            global $post;
            if ($post && (
                has_shortcode($post->post_content, 'mt_jury_dashboard') ||
                has_shortcode($post->post_content, 'mt_evaluation_form') ||
                has_shortcode($post->post_content, 'mt_winners_display') ||
                has_shortcode($post->post_content, 'mt_candidates_grid')
            )) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get current language
     *
     * @return string
     */
    public function get_current_language() {
        return $this->current_language;
    }
    
    /**
     * Get current language code
     *
     * @return string
     */
    public function get_current_language_code() {
        return $this->supported_languages[$this->current_language]['code'];
    }
    
    /**
     * Get supported languages
     *
     * @return array
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
}

