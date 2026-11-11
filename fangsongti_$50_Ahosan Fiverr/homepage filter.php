/**
 * PREVOR - Multiple taxonomy AJAX product filter
 * Chemical Categories = OR | Body Parts = OR | Between them = AND
 */

define('PREVOR_LOOP_TEMPLATE_ID', 2097);
define('PREVOR_POSTS_PER_PAGE', -1); // -1 = show all matches, or e.g. 12

add_action('wp_head', function () {
    if (is_admin()) return;
    ?>
    <script>
        window.prevorFilterData = {
            ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>
        };
    </script>
    <?php
}, 5);

add_action('wp_ajax_prevor_filter_products', 'prevor_filter_products');
add_action('wp_ajax_nopriv_prevor_filter_products', 'prevor_filter_products');

function prevor_clean_slugs($value) {
    $value = isset($value) ? (array) wp_unslash($value) : [];
    $value = array_filter($value, 'is_string');
    $value = array_map('sanitize_title', $value);
    return array_values(array_unique(array_filter($value)));
}

function prevor_filter_products() {

    $categories = prevor_clean_slugs($_POST['categories'] ?? null);
    $body_parts = prevor_clean_slugs($_POST['body_parts'] ?? null);

    $tax_query = ['relation' => 'AND'];

    if ($categories) {
        $tax_query[] = [
            'taxonomy' => 'chemical-categorie',
            'field'    => 'slug',
            'terms'    => $categories,
            'operator' => 'IN',
        ];
    }

    if ($body_parts) {
        $tax_query[] = [
            'taxonomy' => 'body-part',
            'field'    => 'slug',
            'terms'    => $body_parts,
            'operator' => 'IN',
        ];
    }

    $args = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => PREVOR_POSTS_PER_PAGE,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => false,
    ];

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $products = new WP_Query($args);

    ob_start();

    if ($products->have_posts()) {

        $document = \Elementor\Plugin::instance()
            ->documents
            ->get(PREVOR_LOOP_TEMPLATE_ID);

        while ($products->have_posts()) {
            $products->the_post();

            if ($document) {
                $document->print_content();
            } else {
                echo \Elementor\Plugin::instance()
                    ->frontend
                    ->get_builder_content_for_display(PREVOR_LOOP_TEMPLATE_ID, true);
            }
        }

    } else {
        echo '<div class="prevor-no-products">' .
             esc_html__('No matching products found.', 'prevor') .
             '</div>';
    }

    wp_reset_postdata();

    wp_send_json_success([
        'html'  => ob_get_clean(),
        'count' => $products->found_posts,
    ]);
}