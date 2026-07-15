<?php
// easy Version
add_shortcode('category_post_count', function () {

    $term = get_queried_object();

    if (!($term instanceof WP_Term)) {
        return '0 Articles';
    }

    return esc_html($term->count . ' Articles');
});


// first version

add_shortcode('category_post_count', function () {

    // Try to get current category (taxonomy loop / archive)
    $term = get_queried_object();

    // Fallback (for Elementor / custom loop)
    if (!isset($term->term_id)) {
        global $post;
        $terms = get_the_terms($post->ID, 'category');

        if (!empty($terms) && !is_wp_error($terms)) {
            $term = $terms[0];
        }
    }

    if (empty($term) || !isset($term->term_id)) {
        return '0 Articles';
    }

    // Get post count
    $count = $term->count;

    return esc_html($count . ' Articles');
});

// [category_post_count]


// another way done this

add_action('wp_footer', function () {

    // Get all categories
    $categories = get_categories([
        'hide_empty' => false,
    ]);

    $counts = [];

    foreach ($categories as $category) {
        $counts[] = [
            'name'  => $category->name,
            'count' => $category->count
        ];
    }

    ?>
    <script>
        window.categoryCounts = <?php echo wp_json_encode($counts); ?>;
    </script>
    <?php
});

?>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const headings = document.querySelectorAll('.count_heading .elementor-button-text');

    if (!window.categoryCounts || !headings.length) {
        return;
    }

    headings.forEach((heading, index) => {

        if (window.categoryCounts[index]) {

            heading.innerHTML = 
                `${window.categoryCounts[index].count} Articles`;

        }

    });

});
</script>
<?php