<?php
/**
 * File 1: templates/jury-dashboard.php (Admin Dashboard)
 * Updated to use unified functions for consistency
 */
?>

<!-- ADMIN DASHBOARD TEMPLATE -->
<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;

// Check if user is jury member
if (!current_user_can('mt_access_jury_dashboard')) {
    echo '<div class="notice notice-error"><p>' . __('Access denied. You are not authorized to view this page.', 'mobility-trailblazers') . '</p></div>';
    return;
}

// Database setup
global $wpdb;
$table_candidates = $wpdb->prefix . 'posts';
$table_scores = $wpdb->prefix . 'mt_candidate_scores';

// Get evaluation count using UNIFIED function
$evaluated_count = mt_get_user_evaluation_count($current_user_id);

// Get total candidates
$total_candidates = wp_count_posts('mt_candidate')->publish;

// Get candidates (paginated)
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

$candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'posts_per_page' => $per_page,
    'offset' => $offset,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC'
));

$total_pages = ceil($total_candidates / $per_page);
?>

<div class="wrap">
    <h1><?php _e('Jury Dashboard', 'mobility-trailblazers'); ?></h1>
    
    <!-- Progress Summary -->
    <div class="mt-dashboard-summary">
        <div class="mt-summary-card">
            <h3><?php _e('Your Progress', 'mobility-trailblazers'); ?></h3>
            <p class="mt-progress-numbers">
                <span class="evaluated"><?php echo $evaluated_count; ?></span> / 
                <span class="total"><?php echo $total_candidates; ?></span>
                <?php _e('candidates evaluated', 'mobility-trailblazers'); ?>
            </p>
            <div class="mt-progress-bar">
                <?php 
                $percentage = $total_candidates > 0 ? ($evaluated_count / $total_candidates) * 100 : 0;
                ?>
                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <p class="mt-progress-text"><?php echo number_format($percentage, 1); ?>% <?php _e('complete', 'mobility-trailblazers'); ?></p>
        </div>
    </div>
    
    <!-- Candidates List -->
    <div class="mt-candidates-list">
        <h2><?php _e('Candidates to Evaluate', 'mobility-trailblazers'); ?></h2>
        
        <?php if (empty($candidates)): ?>
            <p><?php _e('No candidates found.', 'mobility-trailblazers'); ?></p>
        <?php else: ?>
            <div class="mt-candidates-grid">
                <?php foreach ($candidates as $candidate): ?>
                    <?php
                    $candidate_id = $candidate->ID;
                    
                    // Use UNIFIED function to check if evaluated
                    $is_evaluated = mt_has_jury_evaluated($current_user_id, $candidate_id);
                    
                    // Get existing evaluation if it exists
                    $existing_score = null;
                    if ($is_evaluated) {
                        $existing_score = mt_get_jury_evaluation($current_user_id, $candidate_id);
                    }
                    
                    // Get candidate meta
                    $company = get_post_meta($candidate_id, '_mt_candidate_company', true);
                    $category = get_post_meta($candidate_id, '_mt_candidate_category', true);
                    $description = get_post_meta($candidate_id, '_mt_candidate_description', true);
                    ?>
                    
                    <div class="mt-candidate-card <?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>">
                        <div class="candidate-header">
                            <h3><?php echo esc_html($candidate->post_title); ?></h3>
                            <span class="status-badge <?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>">
                                <?php echo $is_evaluated ? __('✓ Evaluated', 'mobility-trailblazers') : __('Pending', 'mobility-trailblazers'); ?>
                            </span>
                        </div>
                        
                        <div class="candidate-meta">
                            <?php if ($company): ?>
                                <p><strong><?php _e('Company:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($company); ?></p>
                            <?php endif; ?>
                            <?php if ($category): ?>
                                <p><strong><?php _e('Category:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($category); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($description): ?>
                            <div class="candidate-description">
                                <p><?php echo wp_trim_words(esc_html($description), 30); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="candidate-actions">
                            <?php if ($is_evaluated && $existing_score): ?>
                                <div class="evaluation-summary">
                                    <p><strong><?php _e('Your Score:', 'mobility-trailblazers'); ?></strong> 
                                    <?php 
                                    $total_score = ($existing_score->innovation_score ?? 0) + 
                                                 ($existing_score->impact_score ?? 0) + 
                                                 ($existing_score->feasibility_score ?? 0) + 
                                                 ($existing_score->scalability_score ?? 0) + 
                                                 ($existing_score->sustainability_score ?? 0);
                                    echo $total_score . '/50';
                                    ?></p>
                                    <p><em><?php _e('Evaluated on:', 'mobility-trailblazers'); ?> <?php echo date('M j, Y', strtotime($existing_score->evaluated_at)); ?></em></p>
                                </div>
                            <?php endif; ?>
                            
                            <button class="button <?php echo $is_evaluated ? 'button-secondary' : 'button-primary'; ?> mt-evaluate-btn" 
                                    data-candidate-id="<?php echo $candidate_id; ?>">
                                <?php echo $is_evaluated ? __('Update Evaluation', 'mobility-trailblazers') : __('Evaluate', 'mobility-trailblazers'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $paged,
                        'total' => $total_pages,
                        'prev_text' => __('« Previous', 'mobility-trailblazers'),
                        'next_text' => __('Next »', 'mobility-trailblazers'),
                    ));
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Evaluation Modal -->
<div id="mt-evaluation-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <div class="mt-modal-header">
            <h2 id="mt-modal-title"><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h2>
            <span class="mt-modal-close">&times;</span>
        </div>
        
        <div class="mt-modal-body">
            <form id="mt-evaluation-form">
                <input type="hidden" id="candidate_id" name="candidate_id" value="">
                <input type="hidden" id="jury_member_id" name="jury_member_id" value="<?php echo $current_user_id; ?>">
                
                <div class="evaluation-criteria">
                    <div class="criterion">
                        <label for="innovation_score"><?php _e('Innovation (1-10):', 'mobility-trailblazers'); ?></label>
                        <input type="number" id="innovation_score" name="innovation_score" min="1" max="10" required>
                    </div>
                    
                    <div class="criterion">
                        <label for="impact_score"><?php _e('Impact (1-10):', 'mobility-trailblazers'); ?></label>
                        <input type="number" id="impact_score" name="impact_score" min="1" max="10" required>
                    </div>
                    
                    <div class="criterion">
                        <label for="feasibility_score"><?php _e('Feasibility (1-10):', 'mobility-trailblazers'); ?></label>
                        <input type="number" id="feasibility_score" name="feasibility_score" min="1" max="10" required>
                    </div>
                    
                    <div class="criterion">
                        <label for="scalability_score"><?php _e('Scalability (1-10):', 'mobility-trailblazers'); ?></label>
                        <input type="number" id="scalability_score" name="scalability_score" min="1" max="10" required>
                    </div>
                    
                    <div class="criterion">
                        <label for="sustainability_score"><?php _e('Sustainability (1-10):', 'mobility-trailblazers'); ?></label>
                        <input type="number" id="sustainability_score" name="sustainability_score" min="1" max="10" required>
                    </div>
                </div>
                
                <div class="evaluation-comments">
                    <label for="comments"><?php _e('Comments (optional):', 'mobility-trailblazers'); ?></label>
                    <textarea id="comments" name="comments" rows="4" placeholder="<?php _e('Share your thoughts about this candidate...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <div class="mt-modal-footer">
                    <button type="button" class="button" id="mt-cancel-evaluation"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Submit Evaluation', 'mobility-trailblazers'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Modal handling
    $('.mt-evaluate-btn').on('click', function() {
        var candidateId = $(this).data('candidate-id');
        var candidateName = $(this).closest('.mt-candidate-card').find('h3').text();
        
        $('#candidate_id').val(candidateId);
        $('#mt-modal-title').text('<?php _e('Evaluate', 'mobility-trailblazers'); ?> ' + candidateName);
        $('#mt-evaluation-modal').show();
        
        // Load existing evaluation if it exists
        $.post(ajaxurl, {
            action: 'mt_get_evaluation',
            candidate_id: candidateId,
            nonce: '<?php echo wp_create_nonce('mt_nonce'); ?>'
        }, function(response) {
            if (response.success && response.data) {
                $('#innovation_score').val(response.data.innovation_score);
                $('#impact_score').val(response.data.impact_score);
                $('#feasibility_score').val(response.data.feasibility_score);
                $('#scalability_score').val(response.data.scalability_score);
                $('#sustainability_score').val(response.data.sustainability_score);
                $('#comments').val(response.data.comments);
            }
        });
    });
    
    // Close modal
    $('.mt-modal-close, #mt-cancel-evaluation').on('click', function() {
        $('#mt-evaluation-modal').hide();
        $('#mt-evaluation-form')[0].reset();
    });
    
    // Submit evaluation
    $('#mt-evaluation-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=mt_submit_vote&nonce=<?php echo wp_create_nonce('mt_nonce'); ?>';
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert('<?php _e('Evaluation submitted successfully!', 'mobility-trailblazers'); ?>');
                location.reload();
            } else {
                alert('<?php _e('Error submitting evaluation:', 'mobility-trailblazers'); ?> ' + response.data.message);
            }
        });
    });
});
</script>

<style>
.mt-dashboard-summary {
    margin: 20px 0;
}

.mt-summary-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    max-width: 400px;
}

.mt-progress-numbers {
    font-size: 24px;
    margin: 10px 0;
}

.mt-progress-numbers .evaluated {
    color: #28a745;
    font-weight: bold;
}

.mt-progress-bar {
    width: 100%;
    height: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s ease;
}

.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.mt-candidate-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    transition: border-color 0.3s ease;
}

.mt-candidate-card.evaluated {
    border-left: 4px solid #28a745;
}

.mt-candidate-card.pending {
    border-left: 4px solid #ffc107;
}

.candidate-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.evaluated {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.mt-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
}

.mt-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.mt-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.mt-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.mt-modal-body {
    padding: 20px;
}

.evaluation-criteria {
    display: grid;
    gap: 15px;
    margin-bottom: 20px;
}

.criterion {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.criterion label {
    font-weight: bold;
    margin-right: 10px;
}

.criterion input {
    width: 80px;
}

.mt-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}
</style>

<?php
/**
 * File 2: templates/jury-dashboard-frontend.php (Frontend Dashboard)
 * Updated to use unified functions for consistency
 */
?>

<!-- FRONTEND DASHBOARD TEMPLATE -->
<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in and is jury member
if (!is_user_logged_in()) {
    return '<p>' . __('Please log in to access the jury dashboard.', 'mobility-trailblazers') . '</p>';
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;

if (!in_array('mt_jury_member', (array) $current_user->roles) && !current_user_can('manage_options')) {
    return '<p>' . __('Access denied. You are not authorized to view this page.', 'mobility-trailblazers') . '</p>';
}

// Database setup
global $wpdb;
$table_candidates = $wpdb->prefix . 'posts';
$table_scores = $wpdb->prefix . 'mt_candidate_scores';

// Get evaluation count using UNIFIED function
$evaluated_count = mt_get_user_evaluation_count($current_user_id);

// Get total candidates
$total_candidates = wp_count_posts('mt_candidate')->publish;

// Get candidates (paginated)
$paged = isset($_GET['jury_page']) ? max(1, intval($_GET['jury_page'])) : 1;
$per_page = 6;
$offset = ($paged - 1) * $per_page;

$candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'posts_per_page' => $per_page,
    'offset' => $offset,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC'
));

$total_pages = ceil($total_candidates / $per_page);
?>

<div class="mt-frontend-dashboard">
    <div class="dashboard-header">
        <h2><?php _e('Jury Dashboard', 'mobility-trailblazers'); ?></h2>
        <p><?php printf(__('Welcome, %s!', 'mobility-trailblazers'), $current_user->display_name); ?></p>
    </div>
    
    <!-- Progress Summary -->
    <div class="progress-summary">
        <div class="progress-card">
            <div class="progress-info">
                <h3><?php _e('Your Progress', 'mobility-trailblazers'); ?></h3>
                <div class="progress-numbers">
                    <span class="evaluated"><?php echo $evaluated_count; ?></span>
                    <span class="separator">/</span>
                    <span class="total"><?php echo $total_candidates; ?></span>
                    <span class="label"><?php _e('evaluated', 'mobility-trailblazers'); ?></span>
                </div>
            </div>
            <div class="progress-visual">
                <?php 
                $percentage = $total_candidates > 0 ? ($evaluated_count / $total_candidates) * 100 : 0;
                ?>
                <div class="progress-circle" data-percentage="<?php echo $percentage; ?>">
                    <div class="progress-text"><?php echo number_format($percentage, 0); ?>%</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Options -->
    <div class="dashboard-filters">
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all"><?php _e('All Candidates', 'mobility-trailblazers'); ?></button>
            <button class="filter-btn" data-filter="pending"><?php _e('Pending', 'mobility-trailblazers'); ?></button>
            <button class="filter-btn" data-filter="evaluated"><?php _e('Evaluated', 'mobility-trailblazers'); ?></button>
        </div>
    </div>
    
    <!-- Candidates Grid -->
    <div class="candidates-container">
        <?php if (empty($candidates)): ?>
            <div class="no-candidates">
                <p><?php _e('No candidates available for evaluation.', 'mobility-trailblazers'); ?></p>
            </div>
        <?php else: ?>
            <div class="candidates-grid">
                <?php foreach ($candidates as $candidate): ?>
                    <?php
                    $candidate_id = $candidate->ID;
                    
                    // Use UNIFIED function to check if evaluated
                    $is_evaluated = mt_has_jury_evaluated($current_user_id, $candidate_id);
                    
                    // Get existing evaluation if it exists
                    $existing_score = null;
                    if ($is_evaluated) {
                        $existing_score = mt_get_jury_evaluation($current_user_id, $candidate_id);
                    }
                    
                    // Get candidate meta
                    $company = get_post_meta($candidate_id, '_mt_candidate_company', true);
                    $category = get_post_meta($candidate_id, '_mt_candidate_category', true);
                    $description = get_post_meta($candidate_id, '_mt_candidate_description', true);
                    $website = get_post_meta($candidate_id, '_mt_candidate_website', true);
                    ?>
                    
                    <div class="candidate-card <?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>" 
                         data-status="<?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>">
                        
                        <div class="card-header">
                            <div class="candidate-info">
                                <h3><?php echo esc_html($candidate->post_title); ?></h3>
                                <?php if ($company): ?>
                                    <p class="company"><?php echo esc_html($company); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="status-indicator <?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>">
                                <?php if ($is_evaluated): ?>
                                    <span class="status-icon">✓</span>
                                    <span class="status-text"><?php _e('Done', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="status-icon">⏳</span>
                                    <span class="status-text"><?php _e('Pending', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($category): ?>
                                <div class="category-badge"><?php echo esc_html($category); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($description): ?>
                                <p class="description"><?php echo wp_trim_words(esc_html($description), 25); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($is_evaluated && $existing_score): ?>
                                <div class="evaluation-preview">
                                    <div class="score-summary">
                                        <span class="score-label"><?php _e('Your Score:', 'mobility-trailblazers'); ?></span>
                                        <span class="score-value">
                                            <?php 
                                            $total_score = ($existing_score->innovation_score ?? 0) + 
                                                         ($existing_score->impact_score ?? 0) + 
                                                         ($existing_score->feasibility_score ?? 0) + 
                                                         ($existing_score->scalability_score ?? 0) + 
                                                         ($existing_score->sustainability_score ?? 0);
                                            echo $total_score . '/50';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="evaluation-date">
                                        <?php _e('Evaluated:', 'mobility-trailblazers'); ?> 
                                        <?php echo date('M j, Y', strtotime($existing_score->evaluated_at)); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer">
                            <div class="card-actions">
                                <?php if ($website): ?>
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" class="btn btn-outline">
                                        <?php _e('Visit Website', 'mobility-trailblazers'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <button class="btn <?php echo $is_evaluated ? 'btn-secondary' : 'btn-primary'; ?> evaluate-btn" 
                                        data-candidate-id="<?php echo $candidate_id; ?>"
                                        data-candidate-name="<?php echo esc_attr($candidate->post_title); ?>">
                                    <?php echo $is_evaluated ? __('Update', 'mobility-trailblazers') : __('Evaluate', 'mobility-trailblazers'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="dashboard-pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?jury_page=<?php echo $i; ?>" 
                           class="page-link <?php echo $i === $paged ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Evaluation Modal (same as admin) -->
<div id="mt-evaluation-modal" class="evaluation-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title"><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h3>
            <button class="modal-close" type="button">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="evaluation-form">
                <input type="hidden" id="candidate_id" name="candidate_id" value="">
                <input type="hidden" id="jury_member_id" name="jury_member_id" value="<?php echo $current_user_id; ?>">
                
                <div class="criteria-grid">
                    <div class="criterion-item">
                        <label for="innovation_score"><?php _e('Innovation', 'mobility-trailblazers'); ?></label>
                        <div class="score-input">
                            <input type="range" id="innovation_score" name="innovation_score" min="1" max="10" value="5">
                            <span class="score-display">5</span>
                        </div>
                    </div>
                    
                    <div class="criterion-item">
                        <label for="impact_score"><?php _e('Impact', 'mobility-trailblazers'); ?></label>
                        <div class="score-input">
                            <input type="range" id="impact_score" name="impact_score" min="1" max="10" value="5">
                            <span class="score-display">5</span>
                        </div>
                    </div>
                    
                    <div class="criterion-item">
                        <label for="feasibility_score"><?php _e('Feasibility', 'mobility-trailblazers'); ?></label>
                        <div class="score-input">
                            <input type="range" id="feasibility_score" name="feasibility_score" min="1" max="10" value="5">
                            <span class="score-display">5</span>
                        </div>
                    </div>
                    
                    <div class="criterion-item">
                        <label for="scalability_score"><?php _e('Scalability', 'mobility-trailblazers'); ?></label>
                        <div class="score-input">
                            <input type="range" id="scalability_score" name="scalability_score" min="1" max="10" value="5">
                            <span class="score-display">5</span>
                        </div>
                    </div>
                    
                    <div class="criterion-item">
                        <label for="sustainability_score"><?php _e('Sustainability', 'mobility-trailblazers'); ?></label>
                        <div class="score-input">
                            <input type="range" id="sustainability_score" name="sustainability_score" min="1" max="10" value="5">
                            <span class="score-display">5</span>
                        </div>
                    </div>
                </div>
                
                <div class="total-score">
                    <span><?php _e('Total Score:', 'mobility-trailblazers'); ?></span>
                    <span id="total-score-display">25/50</span>
                </div>
                
                <div class="comments-section">
                    <label for="comments"><?php _e('Comments (Optional)', 'mobility-trailblazers'); ?></label>
                    <textarea id="comments" name="comments" rows="4" 
                              placeholder="<?php _e('Share your thoughts about this candidate...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline cancel-btn"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php _e('Submit Evaluation', 'mobility-trailblazers'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Frontend Dashboard Styles */
.mt-frontend-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 30px;
}

.dashboard-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.progress-summary {
    margin-bottom: 30px;
}

.progress-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.progress-info h3 {
    margin: 0 0 15px 0;
    font-size: 24px;
}

.progress-numbers {
    display: flex;
    align-items: baseline;
    gap: 5px;
}

.progress-numbers .evaluated {
    font-size: 48px;
    font-weight: bold;
}

.progress-numbers .total {
    font-size: 24px;
}

.progress-numbers .separator {
    font-size: 24px;
    opacity: 0.7;
}

.progress-numbers .label {
    font-size: 16px;
    opacity: 0.9;
}

.progress-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: conic-gradient(#ffffff 0deg, #ffffff var(--progress-deg, 90deg), rgba(255,255,255,0.3) var(--progress-deg, 90deg));
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.progress-circle::before {
    content: '';
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: inherit;
    position: absolute;
}

.progress-text {
    font-size: 18px;
    font-weight: bold;
    z-index: 1;
}

.dashboard-filters {
    margin-bottom: 30px;
    display: flex;
    justify-content: center;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    background: #f8f9fa;
    padding: 5px;
    border-radius: 50px;
}

.filter-btn {
    padding: 10px 20px;
    border: none;
    background: transparent;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.filter-btn.active,
.filter-btn:hover {
    background: #007cba;
    color: white;
    transform: translateY(-2px);
}

.candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.candidate-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.candidate-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.candidate-card.evaluated {
    border-color: #28a745;
}

.candidate-card.pending {
    border-color: #ffc107;
}

.card-header {
    padding: 20px 20px 10px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.candidate-info h3 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 18px;
    line-height: 1.3;
}

.candidate-info .company {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.status-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.status-indicator.evaluated {
    color: #28a745;
}

.status-indicator.pending {
    color: #ffc107;
}

.status-icon {
    font-size: 20px;
}

.status-text {
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.card-body {
    padding: 0 20px 20px;
}

.category-badge {
    display: inline-block;
    background: #e9ecef;
    color: #495057;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 15px;
}

.description {
    color: #666;
    line-height: 1.5;
    margin-bottom: 15px;
}

.evaluation-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.score-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.score-label {
    font-weight: 500;
    color: #495057;
}

.score-value {
    font-weight: bold;
    color: #28a745;
    font-size: 18px;
}

.evaluation-date {
    font-size: 12px;
    color: #6c757d;
}

.card-footer {
    padding: 0 20px 20px;
    border-top: 1px solid #f1f3f4;
    margin-top: 15px;
    padding-top: 15px;
}

.card-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-primary {
    background: #007cba;
    color: white;
}

.btn-primary:hover {
    background: #005a87;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    color: #007cba;
    border: 1px solid #007cba;
}

.btn-outline:hover {
    background: #007cba;
    color: white;
}

.dashboard-pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.page-link {
    padding: 10px 15px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    color: #007cba;
    text-decoration: none;
    transition: all 0.3s ease;
}

.page-link:hover,
.page-link.active {
    background: #007cba;
    color: white;
    transform: translateY(-1px);
}

/* Evaluation Modal Styles */
.evaluation-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f8f9fa;
    color: #495057;
}

.modal-body {
    padding: 30px;
}

.criteria-grid {
    display: grid;
    gap: 25px;
    margin-bottom: 25px;
}

.criterion-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.criterion-item label {
    font-weight: 600;
    color: #2c3e50;
    margin-right: 20px;
}

.score-input {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
    max-width: 200px;
}

.score-input input[type="range"] {
    flex: 1;
    -webkit-appearance: none;
    appearance: none;
    height: 6px;
    background: #ddd;
    border-radius: 3px;
    outline: none;
}

.score-input input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    background: #007cba;
    border-radius: 50%;
    cursor: pointer;
}

.score-input input[type="range"]::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background: #007cba;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}

.score-display {
    font-weight: bold;
    color: #007cba;
    font-size: 18px;
    min-width: 20px;
    text-align: center;
}

.total-score {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    margin-bottom: 25px;
    font-size: 18px;
    font-weight: bold;
}

.comments-section {
    margin-bottom: 25px;
}

.comments-section label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #2c3e50;
}

.comments-section textarea {
    width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.3s ease;
}

.comments-section textarea:focus {
    border-color: #007cba;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0,124,186,0.1);
}

.modal-footer {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

/* Responsive Design */
@media (max-width: 768px) {
    .progress-card {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .candidates-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .status-indicator {
        flex-direction: row;
        align-self: flex-end;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .criterion-item {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .score-input {
        max-width: none;
    }
}

/* Hide/Show based on filter */
.candidate-card[data-status="pending"] {
    display: block;
}

.candidate-card[data-status="evaluated"] {
    display: block;
}

.candidates-grid[data-filter="pending"] .candidate-card[data-status="evaluated"] {
    display: none;
}

.candidates-grid[data-filter="evaluated"] .candidate-card[data-status="pending"] {
    display: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        $('.candidates-grid').attr('data-filter', filter);
    });
    
    // Progress circle animation
    $('.progress-circle').each(function() {
        var percentage = $(this).data('percentage');
        var degrees = (percentage / 100) * 360;
        $(this).css('--progress-deg', degrees + 'deg');
    });
    
    // Modal handling
    $('.evaluate-btn').on('click', function() {
        var candidateId = $(this).data('candidate-id');
        var candidateName = $(this).data('candidate-name');
        
        $('#candidate_id').val(candidateId);
        $('#modal-title').text('<?php _e('Evaluate', 'mobility-trailblazers'); ?> ' + candidateName);
        $('#mt-evaluation-modal').show();
        
        // Load existing evaluation if it exists
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'mt_get_evaluation',
            candidate_id: candidateId,
            nonce: '<?php echo wp_create_nonce('mt_nonce'); ?>'
        }, function(response) {
            if (response.success && response.data) {
                $('#innovation_score').val(response.data.innovation_score || 5);
                $('#impact_score').val(response.data.impact_score || 5);
                $('#feasibility_score').val(response.data.feasibility_score || 5);
                $('#scalability_score').val(response.data.scalability_score || 5);
                $('#sustainability_score').val(response.data.sustainability_score || 5);
                $('#comments').val(response.data.comments || '');
                
                // Update score displays
                updateScoreDisplays();
            }
        });
    });
    
    // Close modal
    $('.modal-close, .cancel-btn, .modal-overlay').on('click', function() {
        $('#mt-evaluation-modal').hide();
        $('#evaluation-form')[0].reset();
        resetScoreDisplays();
    });
    
    // Score slider updates
    $('input[type="range"]').on('input', function() {
        updateScoreDisplays();
    });
    
    function updateScoreDisplays() {
        $('input[type="range"]').each(function() {
            var value = $(this).val();
            $(this).next('.score-display').text(value);
        });
        
        // Update total score
        var total = 0;
        $('input[type="range"]').each(function() {
            total += parseInt($(this).val());
        });
        $('#total-score-display').text(total + '/50');
    }
    
    function resetScoreDisplays() {
        $('input[type="range"]').each(function() {
            $(this).val(5);
            $(this).next('.score-display').text('5');
        });
        $('#total-score-display').text('25/50');
    }
    
    // Initialize score displays
    updateScoreDisplays();
    
    // Submit evaluation
    $('#evaluation-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('<?php _e('Submitting...', 'mobility-trailblazers'); ?>');
        
        var formData = $form.serialize();
        formData += '&action=mt_submit_vote&nonce=<?php echo wp_create_nonce('mt_nonce'); ?>';
        
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
            if (response.success) {
                $('#mt-evaluation-modal').hide();
                
                // Show success message
                $('<div class="success-message" style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 8px; z-index: 10001;"><?php _e('Evaluation submitted successfully!', 'mobility-trailblazers'); ?></div>')
                    .appendTo('body')
                    .delay(3000)
                    .fadeOut(500, function() { $(this).remove(); });
                
                // Reload page to show updated data
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert('<?php _e('Error submitting evaluation:', 'mobility-trailblazers'); ?> ' + (response.data ? response.data.message : '<?php _e('Unknown error', 'mobility-trailblazers'); ?>'));
            }
        }).always(function() {
            $submitBtn.prop('disabled', false).text(originalText);
        });
    });
});
</script>