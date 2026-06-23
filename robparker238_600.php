<?php
add_shortcode('skill_level_programs', function () {

    ob_start();

    // 1. Get all skill levels
    $skills = new WP_Query([
        'post_type' => 'skill-level',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    if ($skills->have_posts()) :

        while ($skills->have_posts()) : $skills->the_post();

            $skill_id = get_the_ID();
            ?>

            <div class="skill-group">

                <!-- Skill Title -->
                <h2 class="skill-title"><?php the_title(); ?></h2>

                <div class="skill-grid">

                    <?php
                    // 2. Get programs under this skill
                    $programs = new WP_Query([
                        'post_type' => 'vex-iq-skills-progra',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'skill_level',
                                'value' => '"' . $skill_id . '"',
                                'compare' => 'LIKE'
                            ]
                        ]
                    ]);

                    if ($programs->have_posts()) :
                        while ($programs->have_posts()) : $programs->the_post();
                            ?>

                            <div class="skill-card">

                                <div class="card-img">
                                    <?php 
                                    if (has_post_thumbnail()) {
                                        the_post_thumbnail('medium');
                                    }
                                    ?>
                                </div>

                                <div class="card-content">
                                    <h3><?php the_title(); ?></h3>
                                    <p><?php echo get_the_excerpt(); ?></p>
                                </div>

                            </div>

                            <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>

                </div>
            </div>

            <?php

        endwhile;
        wp_reset_postdata();

    else :
        echo '<p>No Skill Levels Found</p>';
    endif;

    return ob_get_clean();
});