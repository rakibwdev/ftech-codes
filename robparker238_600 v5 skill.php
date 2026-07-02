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

				<?php $programs = get_field('v5_skills'); ?>

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


// single skill level loop

add_shortcode('v5_single_skill_program_loop', function () {
    $programs = get_field('v5_skills', get_the_ID());

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


// back button
add_shortcode('v5_back_button_url', function() {
    $program_id = get_the_ID();

    // Search all skill-level posts where v5_skills contains this program ID
    $skill_levels = get_posts([
        'post_type'      => 'v5-skill-level',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => 'v5_skills',
                'value'   => serialize(strval($program_id)), 
                'compare' => 'LIKE',
            ],
        ],
    ]);

    if (empty($skill_levels)) return home_url('/v5-skill-level/');

    return get_permalink($skill_levels[0]->ID);
});

// dinamic menu
add_filter('wp_nav_menu_objects', function ($items, $args) {

    // Change this to your menu ID or slug if needed
    if (empty($args->menu)) {
        return $items;
    }

    // Check by menu name
    if (is_object($args->menu) && $args->menu->name !== 'Main Menu') {
        return $items;
    }

    $item_ids = 900000; // Starting ID for new items

    foreach ($items as $item) {
        if ($item->object !== 'v5-skill-level') {
            continue;
        }
        $item_ids++;

        $get_weeks = get_field('v5_skills', $item->object_id);

        if (!$get_weeks || !is_array($get_weeks) || empty($get_weeks)) {
            continue;
        }

        foreach ($get_weeks as $week) {
            $item_ids++;

            $child = new stdClass();
            $child->ID = $item_ids;
            $child->db_id = $child->ID;
            $child->menu_item_parent = $item->ID;

            $child->object_id = $week->ID;
            $child->object = $week->post_type;      // e.g. lesson, page, post
            $child->type = 'post_type';
            $child->type_label = get_post_type_object($week->post_type)->labels->singular_name;

            $child->title = get_the_title($week);
            $child->url = get_permalink($week);

            $child->classes = [];
            $child->current = false;

            $items[] = $child;

            $get_links = get_field('links', $week->ID);
            if(!$get_links || !is_array($get_links) || empty($get_links)) {
                continue;
            }

            foreach ($get_links as $link) {
                $item_ids++;

                $grandchild = new stdClass();
                $grandchild->ID = $item_ids;
                $grandchild->db_id = $grandchild->ID;
                $grandchild->menu_item_parent = $child->ID;

                $grandchild->object_id = 0; // No specific post ID for custom links
                $grandchild->object = 'custom';
                $grandchild->type = 'custom';
                $grandchild->type_label = 'Custom';

                 $grandchild->target = '_blank';

                $grandchild->title = $link['title'];
                $grandchild->url = $link['connected_link'] ?? '#';

                $grandchild->classes = [];
                $grandchild->current = false;

                $items[] = $grandchild;
            }
        }
    }
    return $items;

}, 10, 2);
