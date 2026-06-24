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
            <?php echo '<pre>'; print_r(get_field('iq_skills')); echo '</pre>'; ?>

                <?php $programs = get_field('iq_skills'); ?>

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
?>

<style>
        .skill-group {
            margin-bottom: 50px;
        }
        .skill-title {
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .skill-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 768px) {
            .skill-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .skill-grid {
                grid-template-columns: 1fr;
            }
        }
        .skill-card {
            background: #f7f7f7;
            border-radius: 10px;
            overflow: hidden;
            transition: 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .skill-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            color: inherit;
            text-decoration: none;
        }
        .card-img img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }
        .card-img-placeholder {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        .card-content {
            padding: 15px;
        }
        .card-content h3 {
            font-size: 16px;
            margin: 0 0 8px 0;
            font-weight: 600;
            color: #222;
        }
        .card-content p {
            font-size: 14px;
            color: #666;
            margin: 0;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>


<?php
add_shortcode('what_you_will_learn', function () {
    try {
        $learn_items = get_field('outcome_objectives_list', get_the_ID());
        $learn_items = is_array($learn_items) ? $learn_items : [];
        if (empty($learn_items)) return '';

        ob_start();
        ?> <ul class="learn-box"> 
            <?php
            foreach ($learn_items as $item) {
                if (empty($item['learn_objective'])) continue;
                ?><li><?php echo esc_html($item['learn_objective']); ?></li><?php
            }
            ?>
        </ul> <?php
        return ob_get_clean();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return '<div class="fz-no-content">Something went wrong.</div>';
    }
});
?>
 <style>
            .links-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                margin-top: 20px;
            }
            @media (max-width: 768px) {
                .links-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 480px) {
                .links-grid { grid-template-columns: 1fr; }
            }
            .link-card {
                background: #f7f7f7;
                border-radius: 10px;
                overflow: hidden;
                text-decoration: none;
                color: inherit;
                display: block;
                transition: 0.3s;
            }
            .link-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                text-decoration: none;
                color: inherit;
            }
            .link-card-img img {
                width: 100%;
                height: 180px;
                object-fit: cover;
                display: block;
            }
            .link-card-img-placeholder {
                width: 100%;
                height: 180px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 40px;
            }
            .link-card-content {
                padding: 15px;
            }
            .link-card-content h3 {
                font-size: 16px;
                margin: 0 0 8px 0;
                font-weight: 600;
                color: #222;
            }
            .link-card-content p {
                font-size: 14px;
                color: #666;
                margin: 0;
                line-height: 1.5;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        </style>
<?php

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