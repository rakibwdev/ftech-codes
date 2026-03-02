<?php
$getCurentAuthor = get_field('attorney-author') ?: [];
if (!empty($getCurentAuthor)) {
    $author_id = $getCurentAuthor[0];
    $author_title = get_field('first_name', $author_id) . ' ' . get_field('last_name', $author_id);
    $author_position = get_field('position', $author_id);
    $author_intro_copy = get_field('intro_copy', $author_id);
    $author_image = get_the_post_thumbnail_url($author_id);
?>
    <style>
        .attorney-author-section {
            margin-top: 80px;
        }

        .attorney-author {
            display: flex;
            align-items: center;
            gap: 20px;

            h3 {
                margin: 0px !important;
                font-size: 20px !important;
            }

            p {
                margin: 0px !important;
            }

            img {
                max-width: 40px;
                aspect-ratio: 1/1;
                border-radius: 50%;
                object-fit: cover;
            }
        }

        .intro-copy {
            margin-top: 20px;
        }
    </style>
    <div class="attorney-author-section">
        <div class="container">
            <div class="attorney-author">
                <?php if ($author_image) : ?>
                    <img src="<?php echo esc_url($author_image); ?>" alt="<?php echo esc_attr($author_title); ?>" />
                <?php endif; ?>
                <div class="author-info">
                    <h3>Author By: <?php echo esc_html($author_title); ?></h3>
                    <p><?php echo esc_html($author_position); ?></p>
                </div>
            </div>
            <div class="intro-copy">
                <?php echo wp_kses_post($author_intro_copy); ?>
            </div>
        </div>
    </div>
<?php
}
?>