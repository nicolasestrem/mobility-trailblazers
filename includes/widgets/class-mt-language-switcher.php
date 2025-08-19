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
     * Track if language switcher has been rendered
     *
     * @var bool
     */
    private static $switcher_rendered = false;
    
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
        
        // Reset the rendered flag at the beginning of each request
        add_action('init', function() {
            self::$switcher_rendered = false;
        }, 1);
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
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            
            .mt-language-switcher-toggle {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 20px;
                background: var(--mt-bg-light, #ffffff);
                border: 2px solid var(--mt-border, #004C5F);
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 500;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .mt-language-switcher-toggle:hover {
                background: var(--mt-bg-hover, #f0f8ff);
                border-color: var(--mt-primary, #003845);
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }
            
            .mt-language-switcher-flag {
                font-size: 24px;
                line-height: 1;
                filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
            }
            
            .mt-language-switcher-arrow {
                margin-left: auto;
                transition: transform 0.3s ease;
                font-size: 12px;
                color: var(--mt-primary, #004C5F);
            }
            
            .mt-language-switcher.active .mt-language-switcher-arrow {
                transform: rotate(180deg);
            }
            
            .mt-language-switcher-dropdown {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                margin-top: 8px;
                background: white;
                border: 2px solid var(--mt-border, #004C5F);
                border-radius: 8px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1000;
                min-width: 200px;
            }
            
            .mt-language-switcher.active .mt-language-switcher-dropdown {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .mt-language-switcher-option {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 20px;
                cursor: pointer;
                transition: all 0.2s ease;
                color: var(--mt-text, #333);
                text-decoration: none;
                font-size: 15px;
                font-weight: 500;
            }
            
            .mt-language-switcher-option:hover {
                background: var(--mt-bg-hover, #f0f8ff);
                padding-left: 24px;
                color: var(--mt-primary, #004C5F);
            }
            
            .mt-language-switcher-option:first-child {
                border-radius: 4px 4px 0 0;
            }
            
            .mt-language-switcher-option:last-child {
                border-radius: 0 0 4px 4px;
            }
            
            .mt-language-switcher-inline {
                display: flex;
                gap: 16px;
                flex-wrap: wrap;
                align-items: center;
            }
            
            .mt-language-switcher-inline .mt-language-option {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 18px;
                background: var(--mt-bg-light, #ffffff);
                border: 2px solid var(--mt-border, #e0e0e0);
                border-radius: 8px;
                text-decoration: none;
                color: var(--mt-text, #333);
                font-size: 15px;
                font-weight: 500;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .mt-language-switcher-inline .mt-language-option:hover {
                background: var(--mt-bg-hover, #f0f8ff);
                border-color: var(--mt-primary, #004C5F);
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            
            .mt-language-switcher-inline .mt-language-option.active {
                background: var(--mt-primary, #004C5F);
                color: white;
                border-color: var(--mt-primary, #004C5F);
                box-shadow: 0 4px 12px rgba(0, 76, 95, 0.3);
                font-weight: 600;
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
            'class' => '',
            'force_display' => 'no' // Allow forcing display even if already rendered
        ], $atts);
        
        if (!get_option('mt_enable_language_switcher', '1')) {
            return '';
        }
        
        // Check if switcher has already been rendered (unless forced)
        if (self::$switcher_rendered && $atts['force_display'] !== 'yes') {
            return '';
        }
        
        // Mark as rendered
        self::$switcher_rendered = true;
        
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
                    // Toggle dropdown with animation
                    $('.mt-language-switcher-toggle').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var $switcher = $(this).parent();
                        var isActive = $switcher.hasClass('active');
                        
                        // Close all other dropdowns
                        $('.mt-language-switcher').not($switcher).removeClass('active');
                        
                        // Toggle current dropdown
                        $switcher.toggleClass('active');
                        
                        // Animate arrow rotation
                        var $arrow = $(this).find('.mt-language-switcher-arrow');
                        if (!isActive) {
                            $arrow.css('transform', 'rotate(180deg)');
                        } else {
                            $arrow.css('transform', 'rotate(0deg)');
                        }
                    });
                    
                    // Language selection via AJAX
                    $('.mt-language-switcher-option, .mt-language-option').on('click', function(e) {
                        e.preventDefault();
                        var $this = $(this);
                        var lang = $this.data('lang');
                        
                        // Show loading state
                        $this.css('opacity', '0.5').append('<span class="mt-loading-spinner" style="margin-left: 10px;">⏳</span>');
                        
                        // Make AJAX request
                        $.post(mt_ajax.ajax_url, {
                            action: 'mt_switch_language',
                            language: lang,
                            nonce: mt_ajax.nonce || ''
                        })
                        .done(function(response) {
                            if (response.success) {
                                // Show success feedback
                                $this.find('.mt-loading-spinner').html('✓');
                                
                                // Reload page after short delay
                                setTimeout(function() {
                                    window.location.reload();
                                }, 300);
                            } else {
                                // On error, just navigate normally
                                window.location.href = $this.attr('href');
                            }
                        })
                        .fail(function() {
                            // On fail, navigate normally
                            window.location.href = $this.attr('href');
                        });
                    });
                    
                    // Close dropdown when clicking outside
                    $(document).on('click', function(e) {
                        if (!$(e.target).closest('.mt-language-switcher').length) {
                            $('.mt-language-switcher').removeClass('active');
                            $('.mt-language-switcher-arrow').css('transform', 'rotate(0deg)');
                        }
                    });
                    
                    // Keyboard navigation
                    $('.mt-language-switcher').on('keydown', function(e) {
                        if (e.key === 'Escape') {
                            $(this).removeClass('active');
                            $(this).find('.mt-language-switcher-arrow').css('transform', 'rotate(0deg)');
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
     * Reset the rendered flag (useful for AJAX requests)
     *
     * @return void
     */
    public static function reset_rendered_flag() {
        self::$switcher_rendered = false;
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
