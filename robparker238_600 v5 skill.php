<?php
// All iq skill loop
add_shortcode('v5_skill_program_loop', function () {
    $skills = new WP_Query([
        'post_type'      => 'v5-skill-level',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    ob_start();
    ?>
    
    <div class="skill-wrapper">
        <?php if ($skills->have_posts()) : ?>
            <?php while ($skills->have_posts()) : $skills->the_post(); ?>

				<?php $programs = get_field('v5_skill_level'); ?>

                <div class="skill-group">
                    <h2 class="skill-title"><?php the_title(); ?></h2>

                    <div class="skill-grid">
                        <?php if (!empty($programs)) : ?>
                            <?php foreach ($programs as $program) : ?>
                                <?php
                                    $thumbnail = get_the_post_thumbnail($program->ID, 'medium');
                                    // Strip tags from content for excerpt
                                    $excerpt = wp_trim_words(strip_tags($program->post_content), 20, '...');
                                    $link = get_permalink($program->ID);
                                ?>
                                <a href="<?php echo esc_url($link); ?>" class="skill-card">
                                    <div class="card-img">
                                        <?php if ($thumbnail) : ?>
                                            <?php echo $thumbnail; ?>
                                        <?php else : ?>
                                            <div class="card-img-placeholder">📚</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-content">
                                        <h3><?php echo esc_html($program->post_title); ?></h3>
                                        <p><?php echo esc_html($excerpt); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No Programs Found</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

// link loop under the program

add_shortcode('program_links_loop', function () {
    try {
        $post_id = get_the_ID();
        $links = get_field('links', $post_id);
        $links = is_array($links) ? $links : [];

        if (empty($links)) return '';

        ob_start();
        ?>

        <div class="links-grid">
            <?php foreach ($links as $item) : ?>

                <?php
                $image       = $item['link_image'] ?? null;
                $title       = $item['title'] ?? '';
                $description = $item['description'] ?? '';
                $url         = $item['connected_link'] ?? '#';
                ?>
                <a href="<?php echo esc_url($url); ?>" class="link-card" target="_blank" rel="noopener noreferrer">

                    <div class="link-card-img">
                        <?php if (!empty($image)) : ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>">
                        <?php else : ?>
                            <div class="link-card-img-placeholder">🔗</div>
                        <?php endif; ?>
                    </div>

                    <div class="link-card-content">
                        <?php if ($title) : ?>
                            <h3><?php echo esc_html($title); ?></h3>
                        <?php endif; ?>
                        <?php if ($description) : ?>
                            <p><?php echo esc_html($description); ?></p>
                        <?php endif; ?>
                    </div>

                </a>
            <?php endforeach; ?>
        </div>

        <?php
        return ob_get_clean();

    } catch (Throwable $e) {
        error_log($e->getMessage());
        return '<div class="fz-no-content">Something went wrong.</div>';
    }
});


// single skill level loop

add_shortcode('v5_single_skill_program_loop', function () {
    $programs = get_field('v5_skill_level', get_the_ID());

    if (empty($programs)) return '<p>No Programs Found</p>';

    ob_start();
    ?>
    <div class="skill-wrapper">
        <div class="skill-grid">
            <?php foreach ($programs as $program) : ?>
                <?php
                $thumbnail = get_the_post_thumbnail($program->ID, 'medium');
                $excerpt   = wp_trim_words(strip_tags($program->post_content), 20, '...');
                $link      = get_permalink($program->ID);
                ?>
                <a href="<?php echo esc_url($link); ?>" class="skill-card">
                    <div class="card-img">
                        <?php if ($thumbnail) : ?>
                            <?php echo $thumbnail; ?>
                        <?php else : ?>
                            <div class="card-img-placeholder">📚</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3><?php echo esc_html($program->post_title); ?></h3>
                        <p><?php echo esc_html($excerpt); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// back button
add_shortcode('back_button_url', function() {
    $program_id = get_the_ID();

    // Search all skill-level posts where iq_skills contains this program ID
    $skill_levels = get_posts([
        'post_type'      => 'v5-skill-level',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => 'iq_skills',
                'value'   => serialize(strval($program_id)), 
                'compare' => 'LIKE',
            ],
        ],
    ]);

    if (empty($skill_levels)) return home_url('/skill-level/');

    return get_permalink($skill_levels[0]->ID);
});


