<?php 
/**
 * Archive Template for mt_candidate
 * 
 * @package MobilityTrailblazers
 * @since 2.5.33
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<main class="mt-archive mt-container">
    <header class="mt-archive__header">
        <h1><?php post_type_archive_title(); ?></h1>
    </header>
    
    <div class="mt-candidates-grid">
        <?php if (have_posts()): while (have_posts()): the_post(); ?>
            <?php
            // Try to use renderer if available
            if (class_exists('\MobilityTrailblazers\Public\Renderers\MT_Shortcode_Renderer') && 
                method_exists('\MobilityTrailblazers\Public\Renderers\MT_Shortcode_Renderer','render_candidate_card')) {
                $renderer = new \MobilityTrailblazers\Public\Renderers\MT_Shortcode_Renderer();
                if (method_exists($renderer, 'render_single_candidate_card')) {
                    echo $renderer->render_single_candidate_card(get_the_ID(), ['class' => 'mt-candidate-card']);
                } else {
                    // Fallback to basic card
                    ?>
                    <article <?php post_class('mt-candidate-card'); ?>>
                        <a class="mt-card__link" href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium', ['class' => 'mt-card__image']); ?>
                            <?php endif; ?>
                            <h3 class="mt-card__title"><?php the_title(); ?></h3>
                            <?php 
                            $position = get_post_meta(get_the_ID(), '_mt_position', true);
                            $organization = get_post_meta(get_the_ID(), '_mt_organization', true);
                            ?>
                            <?php if ($position): ?>
                                <div class="mt-card__role"><?php echo esc_html($position); ?></div>
                            <?php endif; ?>
                            <?php if ($organization): ?>
                                <div class="mt-card__org"><?php echo esc_html($organization); ?></div>
                            <?php endif; ?>
                        </a>
                    </article>
                    <?php
                }
            } else { 
                // Basic fallback 
                ?>
                <article <?php post_class('mt-candidate-card'); ?>>
                    <a class="mt-card__link" href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium', ['class' => 'mt-card__image']); ?>
                        <?php endif; ?>
                        <h3 class="mt-card__title"><?php the_title(); ?></h3>
                        <?php 
                        $position = get_post_meta(get_the_ID(), '_mt_position', true);
                        $organization = get_post_meta(get_the_ID(), '_mt_organization', true);
                        ?>
                        <?php if ($position): ?>
                            <div class="mt-card__role"><?php echo esc_html($position); ?></div>
                        <?php endif; ?>
                        <?php if ($organization): ?>
                            <div class="mt-card__org"><?php echo esc_html($organization); ?></div>
                        <?php endif; ?>
                    </a>
                </article>
            <?php } ?>
        <?php endwhile; endif; ?>
    </div>
    
    <nav class="mt-archive__pagination">
        <?php the_posts_pagination(); ?>
    </nav>
</main>

<?php get_footer(); ?>