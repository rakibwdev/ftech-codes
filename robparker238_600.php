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

<!-- single skill level -->
 <style>
        .skill-wrapper {
            padding: 20px 0;
        }
        .skill-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        @media (max-width: 1024px) {
            .skill-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .skill-grid { grid-template-columns: 1fr; }
        }
        .skill-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .skill-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }
        .card-img {
            position: relative;
            overflow: hidden;
        }
        .card-img img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .skill-card:hover .card-img img {
            transform: scale(1.05);
        }
        .card-img-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        .card-content {
            padding: 18px;
        }
        .card-content h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .card-content p {
            font-size: 14px;
            color: #777;
            margin: 0 0 14px 0;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-top: 1px solid #f0f0f0;
            background: #fafafa;
        }
        .card-tag {
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
            background: #eef0fd;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-arrow {
            width: 28px;
            height: 28px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            transition: background 0.3s;
        }
        .skill-card:hover .card-arrow {
            background: #764ba2;
        }
    </style>
<?php
add_shortcode('single_skill_program_loop', function () {
    $programs = get_field('iq_skills', get_the_ID());

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
?>

<!-- program links loop -->
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


// back button
add_shortcode('back_button', function () {
    $current_id = get_the_ID();

    $raw = get_posts([
        'post_type'      => 'skill-level',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    foreach ($raw as $skill) {
        $meta = get_post_meta($skill->ID, 'iq_skills', true);
        echo '<pre>Skill: ' . $skill->post_title . ' | Meta: ';
        var_dump($meta);
        echo '</pre>';
    }

    return '';
});