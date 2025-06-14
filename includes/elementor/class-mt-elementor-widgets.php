// includes/elementor/class-mt-elementor-widgets.php
class MT_Elementor_Dashboard_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mt_jury_dashboard';
    }
    
    public function get_title() {
        return __('Jury Dashboard', 'mobility-trailblazers');
    }
    
    protected function render() {
        // Check if in editor
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            echo '<div class="mt-elementor-preview">';
            echo '<h3>Jury Dashboard</h3>';
            echo '<p>Dashboard will display here on frontend</p>';
            echo '</div>';
            return;
        }
        
        // Render actual dashboard
        echo do_shortcode('[mt_jury_dashboard]');
    }
}