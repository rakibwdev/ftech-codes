
/**
 * ==========================================================
 * PREVOR - MULTIPLE TAXONOMY AJAX PRODUCT FILTER
 * ==========================================================
 *
 * Post Type: product
 *
 * Taxonomy 1: chemical-categorie
 * Taxonomy 2: body-part
 *
 * Multiple selection:
 * Chemical Categories = OR
 * Body Parts = OR
 *
 * Between taxonomies = AND
 *
 * ==========================================================
 */


/**
 * 1. Frontend AJAX Configuration
 */

add_action('wp_head', 'prevor_filter_frontend_config', 5);

function prevor_filter_frontend_config() {

    if (is_admin()) {
        return;
    }

    ?>
    <script>
        window.prevorFilterData = {
            ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
            nonce: <?php echo wp_json_encode(wp_create_nonce('prevor_product_filter_nonce')); ?>
        };
    </script>
    <?php
}


/**
 * 2. Register AJAX Actions
 */

add_action(
    'wp_ajax_prevor_filter_products',
    'prevor_filter_products'
);

add_action(
    'wp_ajax_nopriv_prevor_filter_products',
    'prevor_filter_products'
);


/**
 * 3. AJAX Product Filtering
 */

function prevor_filter_products() {

    check_ajax_referer(
        'prevor_product_filter_nonce',
        'nonce'
    );


    // Get selected Chemical Categories.
    $categories = isset($_POST['categories'])
        ? (array) wp_unslash($_POST['categories'])
        : [];


    // Get selected Body Parts.
    $body_parts = isset($_POST['body_parts'])
        ? (array) wp_unslash($_POST['body_parts'])
        : [];


    // Sanitize selected slugs.
    $categories = array_unique(
        array_filter(
            array_map('sanitize_title', $categories)
        )
    );


    $body_parts = array_unique(
        array_filter(
            array_map('sanitize_title', $body_parts)
        )
    );


    /**
     * Build taxonomy query.
     */

    $tax_query = [
        'relation' => 'AND',
    ];


    // Chemical Category condition.
    if (!empty($categories)) {

        $tax_query[] = [

            'taxonomy' => 'chemical-categorie',

            'field'    => 'slug',

            'terms'    => $categories,

            'operator' => 'IN',

        ];

    }


    // Body Part condition.
    if (!empty($body_parts)) {

        $tax_query[] = [

            'taxonomy' => 'body-part',

            'field'    => 'slug',

            'terms'    => $body_parts,

            'operator' => 'IN',

        ];

    }


    /**
     * Product query.
     */

    $args = [

        'post_type'      => 'product',

        'post_status'    => 'publish',

        'posts_per_page' => 12,

        'orderby'        => 'date',

        'order'          => 'DESC',

    ];


    // Add taxonomy conditions only when filters are selected.
    if (count($tax_query) > 1) {

        $args['tax_query'] = $tax_query;

    }


    $products = new WP_Query($args);


    /**
     * Render Elementor Loop Grid items.
     */

    ob_start();

    if ($products->have_posts()) {

        while ($products->have_posts()) {

            $products->the_post();

            echo \Elementor\Plugin::instance()
                ->frontend
                ->get_builder_content_for_display(2097);

        }

    } else {

        echo '<div class="prevor-no-products">';
        echo esc_html__('No matching products found.', 'prevor');
        echo '</div>';

    }


    wp_reset_postdata();


    $html = ob_get_clean();


    wp_send_json_success([

        'html'  => $html,

        'count' => $products->found_posts,

    ]);

}
