<?php
add_shortcode('skill_program_loop', function () {
    $skills = new WP_Query([
        'post_type'      => 'skill-level',
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

                <?php $programs = get_field('iq_skills'); ?>

                <div class="skill-group">
                    <h2><?php the_title(); ?></h2>

                    <div class="program-list">
                        <?php if (!empty($programs)) : ?>
                            <?php foreach ($programs as $program) : ?>
                                <div class="program-item">
                                    <?php echo esc_html($program->post_title); ?>
                                </div>
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