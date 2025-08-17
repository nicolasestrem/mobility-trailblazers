<?php
/**
 * Language Switcher Widget
 *
 * @package MobilityTrailblazers
 * @since 2.1.0
 */

namespace MobilityTrailblazers\Widgets;

use MobilityTrailblazers\Core\MT_I18n;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Language_Switcher
 *
 * Provides a frontend language switcher widget for users to change their language preference
 */
class MT_Language_Switcher {
    
    /**
     * I18n instance
     *
     * @var MT_I18n
     */
    private $i18n;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->i18n = new MT_I18n();
    }
    
    /**
     * Initialize the language switcher
     *
     * @return void
     */
    public function init() {
        // Register shortcode
        add_shortcode('mt_language_switcher', [$this, 'render_shortcode']);
        
        // Add AJAX handler for language switching
        add_action('wp_ajax_mt_switch_language', [$this, 'ajax_switch_language']);
        add_action('wp_ajax_nopriv_mt_switch_language', [$this, 'ajax_switch_language']);
        
        // Add styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Enqueue language switcher assets
     *
     * @return void
     */
    public function enqueue_assets() {
        // Add inline styles for language switcher
        $custom_css = '
            .mt-language-switcher {
                position: relative;
                display: inline-block;
                margin: 10px 0;
            }
            
            .mt-language-switcher-toggle {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                background: var(--mt-bg-light, #f5f5f5);
                border: 1px solid var(--mt-border, #ddd);
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.2s ease;
            }
            
            .mt-language-switcher-toggle:hover {
                background: var(--mt-bg-hover, #e9e9e9);
                border-color: var(--mt-primary, #004C5F);
            }
            
            .mt-language-switcher-flag {
                font-size: 20px;
                line-height: 1;
            }
            
            .mt-language-switcher-arrow {
                margin-left: 8px;
                transition: transform 0.2s ease;
            }
            
            .mt-language-switcher.active .mt-language-switcher-arrow {
                transform: rotate(180deg);
            }
            
            .mt-language-switcher-dropdown {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                margin-top: 4px;
                background: white;
                border: 1px solid var(--mt-border, #ddd);
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all 0.2s ease;
                z-index: 1000;
            }
            
            .mt-language-switcher.active .mt-language-switcher-dropdown {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .mt-language-switcher-option {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                cursor: pointer;
                transition: background 0.2s ease;
                color: var(--mt-text, #333);
                text-decoration: none;
            }
            
            .mt-language-switcher-option:hover {
                background: var(--mt-bg-hover, #f0f0f0);
            }
            
            .mt-language-switcher-option:first-child {
                border-radius: 4px 4px 0 0;
            }
            
            .mt-language-switcher-option:last-child {
                border-radius: 0 0 4px 4px;
            }
            
            .mt-language-switcher-inline {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }
            
            .mt-language-switcher-inline .mt-language-option {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                background: var(--mt-bg-light, #f5f5f5);
                border: 1px solid var(--mt-border, #ddd);
                border-radius: 4px;
                text-decoration: none;
                color: var(--mt-text, #333);
                transition: all 0.2s ease;
            }
            
            .mt-language-switcher-inline .mt-language-option:hover {
                background: var(--mt-bg-hover, #e9e9e9);
                border-color: var(--mt-primary, #004C5F);
            }
            
            .mt-language-switcher-inline .mt-language-option.active {
                background: var(--mt-primary, #004C5F);
                color: white;
                border-color: var(--mt-primary, #004C5F);
            }
            
            @media (max-width: 768px) {
                .mt-language-switcher-dropdown {
                    position: fixed;
                    top: auto;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    margin: 0;
                    border-radius: 16px 16px 0 0;
                    transform: translateY(100%);
                }
                
                .mt-language-switcher.active .mt-language-switcher-dropdown {
                    transform: translateY(0);
                }
            }
        ';
        
        wp_add_inline_style('mt-frontend', $custom_css);
    }
    
    /**
     * Render language switcher shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'type' => 'dropdown', // dropdown or inline
            'show_flags' => 'yes',
            'show_names' => 'yes',
            'class' => ''
        ], $atts);
        
        if (!get_option('mt_enable_language_switcher', '1')) {
            return '';
        }
        
        $current_lang = $this->i18n->get_current_language();
        $languages = $this->i18n->get_supported_languages();
        
        ob_start();
        
        if ($atts['type'] === 'dropdown') {
            $this->render_dropdown($current_lang, $languages, $atts);
        } else {
            $this->render_inline($current_lang, $languages, $atts);
        }
        
        // Add JavaScript for dropdown functionality
        if ($atts['type'] === 'dropdown') {
            ?>
            <script>
            (function($) {
                $(document).ready(function() {
                    $('.mt-language-switcher-toggle').on('click', function(e) {
                        e.preventDefault();
                        $(this).parent().toggleClass('active');
                    });
                    
                    // Close dropdown when clicking outside
                    $(document).on('click', function(e) {
                        if (!$(e.target).closest('.mt-language-switcher').length) {
                            $('.mt-language-switcher').removeClass('active');
                        }
                    });
                });
            })(jQuery);
            </script>
            <?php
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render dropdown language switcher
     *
     * @param string $current_lang Current language
     * @param array $languages Available languages
     * @param array $atts Shortcode attributes
     * @return void
     */
    private function render_dropdown($current_lang, $languages, $atts) {
        $current = $languages[$current_lang];
        ?>
        <div class="mt-language-switcher <?php echo esc_attr($atts['class']); ?>">
            <div class="mt-language-switcher-toggle">
                <?php if ($atts['show_flags'] === 'yes'): ?>
                    <span class="mt-language-switcher-flag"><?php echo esc_html($current['flag']); ?></span>
                <?php endif; ?>
                <?php if ($atts['show_names'] === 'yes'): ?>
                    <span class="mt-language-switcher-name"><?php echo esc_html($current['native_name']); ?></span>
                <?php endif; ?>
                <span class="mt-language-switcher-arrow">▼</span>
            </div>
            <div class="mt-language-switcher-dropdown">
                <?php foreach ($languages as $locale => $lang): ?>
                    <?php if ($locale === $current_lang) continue; ?>
                    <a href="<?php echo esc_url(add_query_arg('mt_lang', $locale)); ?>" 
                       class="mt-language-switcher-option"
                       data-lang="<?php echo esc_attr($locale); ?>">
                        <?php if ($atts['show_flags'] === 'yes'): ?>
                            <span class="mt-language-switcher-flag"><?php echo esc_html($lang['flag']); ?></span>
                        <?php endif; ?>
                        <?php if ($atts['show_names'] === 'yes'): ?>
                            <span class="mt-language-switcher-name"><?php echo esc_html($lang['native_name']); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render inline language switcher
     *
     * @param string $current_lang Current language
     * @param array $languages Available languages
     * @param array $atts Shortcode attributes
     * @return void
     */
    private function render_inline($current_lang, $languages, $atts) {
        ?>
        <div class="mt-language-switcher-inline <?php echo esc_attr($atts['class']); ?>">
            <?php foreach ($languages as $locale => $lang): ?>
                <a href="<?php echo esc_url(add_query_arg('mt_lang', $locale)); ?>" 
                   class="mt-language-option <?php echo $locale === $current_lang ? 'active' : ''; ?>"
                   data-lang="<?php echo esc_attr($locale); ?>">
                    <?php if ($atts['show_flags'] === 'yes'): ?>
                        <span class="mt-language-flag"><?php echo esc_html($lang['flag']); ?></span>
                    <?php endif; ?>
                    <?php if ($atts['show_names'] === 'yes'): ?>
                        <span class="mt-language-name"><?php echo esc_html($lang['native_name']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Handle AJAX language switching
     *
     * @return void
     */
    public function ajax_switch_language() {
        check_ajax_referer('mt_ajax_nonce', 'nonce');
        
        $lang = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';
        $languages = $this->i18n->get_supported_languages();
        
        if (!array_key_exists($lang, $languages)) {
            wp_send_json_error(__('Invalid language selection.', 'mobility-trailblazers'));
        }
        
        // Set cookie
        setcookie('mt_language', $lang, time() + (86400 * 30), '/');
        
        // Update user preference if logged in
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'mt_language_preference', $lang);
        }
        
        wp_send_json_success([
            'message' => __('Language updated successfully.', 'mobility-trailblazers'),
            'reload' => true
        ]);
    }
}
