<?php

function cpf_path_attribute_map() {
    return array(
        'brand'        => 'pa_brand',
        'size'         => 'pa_size',
        'watch-style'  => 'pa_watch_style',
        'material'     => 'pa_material',
        'jewelry-type' => 'pa_jewelry_type',
        'stone-type'   => 'pa_stone_type',
        'collection'   => 'pa_collection',
        'color'        => 'pa_color',
    );
}

/* "brand/rolex/size/40mm" => array( 'pa_brand' => array('rolex'), 'pa_size' => array('40mm') ) */
function cpf_parse_path_filters( $path ) {
    $map   = cpf_path_attribute_map();
    $out   = array();
    $parts = array_values( array_filter(
        explode( '/', trim( (string) $path, '/' ) ),
        'strlen'
    ) );

    for ( $i = 0; $i + 1 < count( $parts ); $i += 2 ) {
        $name = sanitize_title( $parts[ $i ] );

        if ( ! isset( $map[ $name ] ) ) {
            continue;
        }

        $values = array_filter( array_map(
            'sanitize_title',
            explode( ',', $parts[ $i + 1 ] )
        ) );

        if ( $values ) {
            $taxonomy         = $map[ $name ];
            $out[ $taxonomy ] = array_values( array_unique( array_merge(
                $out[ $taxonomy ] ?? array(),
                $values
            ) ) );
        }
    }

    return $out;
}

/* array( 'pa_brand' => array('rolex') ) => "brand/rolex/" */
function cpf_build_path_filters( $filters ) {
    $path = '';

    foreach ( cpf_path_attribute_map() as $name => $taxonomy ) {
        if ( ! empty( $filters[ $taxonomy ] ) ) {
            $path .= $name . '/' . implode(
                ',',
                array_map( 'rawurlencode', $filters[ $taxonomy ] )
            ) . '/';
        }
    }

    return $path;
}

add_action( 'init', function() {
    if ( ! function_exists( 'wc_get_page_id' ) ) {
        return;
    }

    $shop_id = wc_get_page_id( 'shop' );

    if ( $shop_id < 1 ) {
        return;
    }

    $shop_uri = trim( get_page_uri( $shop_id ), '/' );

    $names = implode( '|', array_map(
        function( $name ) {
            return preg_quote( $name, '/' );
        },
        array_keys( cpf_path_attribute_map() )
    ) );

    add_rewrite_rule(
        '^' . preg_quote( $shop_uri, '/' ) . '/((?:(?:' . $names . ')/[^/]+/?)+)$',
        'index.php?pagename=' . $shop_uri . '&cpf_path=$matches[1]',
        'top'
    );

    /* Category archives: /product-category/watches/brand/rolex/ */
    $permalinks = function_exists( 'wc_get_permalink_structure' )
        ? wc_get_permalink_structure()
        : array();

    $cat_base = trim( $permalinks['category_rewrite_slug'] ?? 'product-category', '/' );

    add_rewrite_rule(
        '^' . preg_quote( $cat_base, '/' ) . '/(.+?)/((?:(?:' . $names . ')/[^/]+/?)+)$',
        'index.php?product_cat=$matches[1]&cpf_path=$matches[2]',
        'top'
    );

    /* Flush once, after the rules are registered */
    if ( get_option( 'cpf_rewrite_version' ) !== '4' ) {
        flush_rewrite_rules( false );
        update_option( 'cpf_rewrite_version', '4' );
    }
}, 20 );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'cpf_path';
    return $vars;
} );

/* 301 old query-string URLs to the new path URLs */
add_action( 'template_redirect', function() {
    $is_shop = function_exists( 'is_shop' ) && is_shop();
    $is_cat  = function_exists( 'is_product_category' ) && is_product_category();

    if ( ! $is_shop && ! $is_cat ) {
        return;
    }

    $args     = wp_unslash( $_GET );
    $base     = '';
    $redirect = false;

    if ( $is_cat ) {
        $term = get_queried_object();

        if ( $term && ! is_wp_error( $term ) ) {
            $base = get_term_link( $term );
        }
    } else {
        $base = wc_get_page_permalink( 'shop' );

        if ( isset( $args['category'] ) && is_string( $args['category'] ) ) {
            $term = get_term( absint( $args['category'] ), 'product_cat' );

            if ( $term && ! is_wp_error( $term ) ) {
                $link = get_term_link( $term );

                if ( ! is_wp_error( $link ) ) {
                    $base     = $link;
                    $redirect = true;
                    unset( $args['category'] );
                }
            }
        }
    }

    if ( ! $base || is_wp_error( $base ) ) {
        return;
    }

    $legacy = array(
        'wpf_filter_brand' => 'pa_brand',
        'jewelry_type'     => 'pa_jewelry_type',
    );

    foreach ( cpf_path_attribute_map() as $taxonomy ) {
        $legacy[ $taxonomy ] = $taxonomy;
    }

    $filters = array();

    foreach ( $legacy as $key => $taxonomy ) {
        if ( ! isset( $args[ $key ] ) || ! is_string( $args[ $key ] ) ) {
            continue;
        }

        $values = array_filter( array_map(
            'sanitize_title',
            explode( ',', sanitize_text_field( $args[ $key ] ) )
        ) );

        if ( $values ) {
            $filters[ $taxonomy ] = array_values( array_unique( array_merge(
                $filters[ $taxonomy ] ?? array(),
                $values
            ) ) );
        }

        unset( $args[ $key ] );
    }

    if ( ! $filters && ! $redirect ) {
        return;
    }

    unset( $args['wpf_fbv'] );

    foreach ( cpf_parse_path_filters( get_query_var( 'cpf_path' ) ) as $taxonomy => $values ) {
        $filters[ $taxonomy ] = array_values( array_unique( array_merge(
            $values,
            $filters[ $taxonomy ] ?? array()
        ) ) );
    }

    $url = trailingslashit( $base ) . cpf_build_path_filters( $filters );

    wp_safe_redirect( add_query_arg( urlencode_deep( $args ), $url ), 301 );
    exit;
} );


/* ==========================================
   FIX: SERVER-SIDE FILTERING ON PAGE LOAD
   Menu link theke direct dhukle (/shop/brand/rolex/)
   Elementor Loop Grid nijei filtered products render korbe.

   Loop Grid widget -> Query -> Query ID = cpf_products
========================================== */

/* Collect all filters from path URL + price from query string */
function cpf_get_request_filters() {
    return cpf_parse_path_filters( get_query_var( 'cpf_path' ) );
}

/* Apply filters to a WP_Query */
function cpf_apply_filters_to_query( $query ) {
    $filters = cpf_get_request_filters();

    $tax_query = (array) $query->get( 'tax_query' );

    foreach ( $filters as $taxonomy => $values ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $values,
            'operator' => 'IN',
        );
    }

    /* Category page: keep the category constraint */
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term = get_queried_object();

        if ( $term && ! is_wp_error( $term ) && ! empty( $term->term_id ) ) {
            $tax_query[] = array(
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => (int) $term->term_id,
                'include_children' => true,
            );
        }
    }

    if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
        $tax_query['relation'] = 'AND';
    }

    if ( $tax_query ) {
        $query->set( 'tax_query', $tax_query );
    }

    /* Price */
    $min = isset( $_GET['min_price'] ) && $_GET['min_price'] !== ''
        ? wc_format_decimal( wp_unslash( $_GET['min_price'] ) )
        : '';

    $max = isset( $_GET['max_price'] ) && $_GET['max_price'] !== ''
        ? wc_format_decimal( wp_unslash( $_GET['max_price'] ) )
        : '';

    if ( $min !== '' || $max !== '' ) {
        $meta_query = (array) $query->get( 'meta_query' );

        $clause = array(
            'key'  => '_price',
            'type' => 'DECIMAL(20,6)',
        );

        if ( $min !== '' && $max !== '' ) {
            $clause['value']   = array( (float) $min, (float) $max );
            $clause['compare'] = 'BETWEEN';
        } elseif ( $min !== '' ) {
            $clause['value']   = (float) $min;
            $clause['compare'] = '>=';
        } else {
            $clause['value']   = (float) $max;
            $clause['compare'] = '<=';
        }

        $meta_query[] = $clause;

        $query->set( 'meta_query', $meta_query );
    }
}

/* Elementor Loop Grid with Query ID = cpf_products */
add_action( 'elementor/query/cpf_products', function( $query ) {
    if ( ! function_exists( 'wc_format_decimal' ) ) {
        return;
    }

    cpf_apply_filters_to_query( $query );
} );

/* Main query (product category archives / Current Query source) */
add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( ! get_query_var( 'cpf_path' ) ) {
        return;
    }

    if ( ! function_exists( 'is_product_category' ) || ! $query->is_tax( 'product_cat' ) ) {
        return;
    }

    $filters   = cpf_get_request_filters();
    $tax_query = (array) $query->get( 'tax_query' );

    foreach ( $filters as $taxonomy => $values ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $values,
            'operator' => 'IN',
        );
    }

    if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
        $tax_query['relation'] = 'AND';
    }

    $query->set( 'tax_query', $tax_query );
} );


/* ==========================================
   SPEED: CACHE (transients)
   Product save / update / term change hole cache auto clear hoy.
========================================== */

function cpf_cache_version() {
    $v = get_option( 'cpf_cache_v' );

    if ( ! $v ) {
        $v = time();
        update_option( 'cpf_cache_v', $v, false );
    }

    return $v;
}

function cpf_cache_key( $type, $data ) {
    return 'cpf_' . $type . '_' . md5( cpf_cache_version() . wp_json_encode( $data ) );
}

function cpf_bump_cache() {
    update_option( 'cpf_cache_v', time(), false );
}

add_action( 'save_post_product', 'cpf_bump_cache' );
add_action( 'woocommerce_update_product', 'cpf_bump_cache' );
add_action( 'woocommerce_new_product', 'cpf_bump_cache' );
add_action( 'deleted_post', 'cpf_bump_cache' );
add_action( 'created_term', 'cpf_bump_cache' );
add_action( 'edited_term', 'cpf_bump_cache' );
add_action( 'delete_term', 'cpf_bump_cache' );

/* Cached wrapper: attribute terms */
function cpf_get_filter_terms( $taxonomy, $category_id = 0 ) {
    $key    = cpf_cache_key( 'terms', array( $taxonomy, (int) $category_id ) );
    $cached = get_transient( $key );

    if ( $cached !== false ) {
        return $cached;
    }

    $terms = cpf_get_filter_terms_uncached( $taxonomy, $category_id );

    set_transient( $key, $terms, 12 * HOUR_IN_SECONDS );

    return $terms;
}


/* Find Elementor Loop Item template */
function cpf_find_loop_template_id() {
    static $template_id = null;

    if ( $template_id !== null ) {
        return $template_id;
    }

    $template_id = 0;

    $ids = get_posts( array(
        'post_type'      => array( 'elementor_library', 'page' ),
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => '_elementor_data',
                'compare' => 'EXISTS',
            ),
        ),
    ) );

    foreach ( $ids as $post_id ) {
        $raw = get_post_meta( $post_id, '_elementor_data', true );

        if ( ! $raw ) {
            continue;
        }

        $elements = is_string( $raw ) ? json_decode( $raw, true ) : $raw;

        if ( ! is_array( $elements ) ) {
            continue;
        }

        $walk = function( $items ) use ( &$walk ) {
            foreach ( $items as $item ) {
                if ( ! is_array( $item ) ) {
                    continue;
                }

                $settings = $item['settings'] ?? array();

                if (
                    ( $settings['_element_id'] ?? '' ) === 'product_loop'
                    && strpos( $item['widgetType'] ?? '', 'loop' ) !== false
                ) {
                    return absint(
                        $settings['template_id']
                        ?? $settings['loop_item_template']
                        ?? 0
                    );
                }

                if ( ! empty( $item['elements'] ) ) {
                    $found = $walk( $item['elements'] );

                    if ( $found ) {
                        return $found;
                    }
                }
            }

            return 0;
        };

        $found = $walk( $elements );

        if ( $found ) {
            $template_id = absint( $found );
            break;
        }
    }

    return $template_id;
}


/* Get attribute terms used by products in the selected category */
function cpf_get_filter_terms_uncached( $taxonomy, $category_id = 0 ) {
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return array();
    }

    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    if ( is_wp_error( $terms ) || ! $terms ) {
        return array();
    }

    if ( $category_id ) {
        $product_ids = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $category_id,
                    'include_children' => true,
                ),
            ),
        ) );

        if ( ! $product_ids ) {
            return array();
        }

        $used_ids = wp_get_object_terms(
            $product_ids,
            $taxonomy,
            array( 'fields' => 'ids' )
        );

        if ( is_wp_error( $used_ids ) || ! $used_ids ) {
            return array();
        }

        $used_ids = array_map( 'absint', $used_ids );

        $terms = array_values( array_filter(
            $terms,
            function( $term ) use ( $used_ids ) {
                return in_array( (int) $term->term_id, $used_ids, true );
            }
        ) );
    }

    return $terms;
}


/* Render one attribute filter */
function cpf_render_attribute( $taxonomy, $label, $category_id = 0, $selected = array() ) {
    $terms = cpf_get_filter_terms( $taxonomy, $category_id );

    if ( ! $terms ) {
        return '';
    }

    ob_start();
    ?>
    <div class="cpf-group">
        <button type="button" class="cpf-heading" aria-expanded="false">
            <span><?php echo esc_html( $label ); ?></span>
            <span class="cpf-plus">+</span>
        </button>

        <div class="cpf-options">
            <?php foreach ( $terms as $term ) : ?>
                <label class="cpf-option">
                    <input
                        type="checkbox"
                        class="cpf-term"
                        data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
                        value="<?php echo esc_attr( $term->slug ); ?>"
                        <?php checked( in_array( $term->slug, $selected, true ) ); ?>
                    >
                    <span class="cpf-check"></span>
                    <span><?php echo esc_html( $term->name ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}


/* Extra attributes for Watches, Jewelry and Handbags */
function cpf_get_category_extra_attributes( $category_id = 0 ) {
    if ( ! $category_id ) {
        return array();
    }

    $category = get_term( $category_id, 'product_cat' );

    if ( ! $category || is_wp_error( $category ) ) {
        return array();
    }

    $map = array(
        'watches' => array(
            'pa_watch_style'     => 'Watch Style',
            'pa_material'        => 'Material',
            'pa_case_size'       => 'Case Size',
            'pa_condition'       => 'Condition',
            'pa_dial'            => 'Dial',
            'pa_bezel'           => 'Bezel',
            'pa_series'          => 'Series',
            'pa_warranty_papers' => 'Warranty Papers',
            'pa_box'             => 'Box',
        ),
        'jewelry' => array(
            'pa_material'     => 'Material',
            'pa_jewelry_type' => 'Jewelry Type',
            'pa_stone_type'   => 'Stone Type',
        ),
        'handbags' => array(
            'pa_collection' => 'Collection',
            'pa_color'      => 'Color',
        ),
    );

    $category_ids = array( (int) $category->term_id );
    $ancestors = get_ancestors( $category->term_id, 'product_cat' );

    foreach ( $ancestors as $ancestor_id ) {
        $category_ids[] = (int) $ancestor_id;
    }

    foreach ( $category_ids as $check_id ) {
        $check_category = get_term( $check_id, 'product_cat' );

        if ( ! $check_category || is_wp_error( $check_category ) ) {
            continue;
        }

        if ( isset( $map[ $check_category->slug ] ) ) {
            return $map[ $check_category->slug ];
        }
    }

    return array();
}


/* Render category-specific attributes */
function cpf_render_category_extra_attributes( $category_id = 0, $initial_filters = array() ) {
    $html = '';

    foreach ( cpf_get_category_extra_attributes( $category_id ) as $taxonomy => $label ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }

        $html .= cpf_render_attribute(
            $taxonomy,
            $label,
            $category_id,
            $initial_filters[ $taxonomy ] ?? array()
        );
    }

    return $html;
}


/* Product filter shortcode */
add_shortcode( 'custom_product_filter', function() {
    if ( ! function_exists( 'wc_get_products' ) ) {
        return '';
    }

    $categories = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    if ( is_wp_error( $categories ) ) {
        $categories = array();
    }

    $current_category = 0;

    if ( is_product_category() ) {
        $current = get_queried_object();

        if ( $current && ! is_wp_error( $current ) ) {
            $current_category = absint( $current->term_id );
        }
    }

    /* Read selected filters: path URL first, then old query-string URLs */
    $initial_filters = array();

    $merge_filter = function( $taxonomy, $values ) use ( &$initial_filters ) {
        $values = array_filter( array_map( 'sanitize_title', (array) $values ) );

        if ( ! $values ) {
            return;
        }

        $initial_filters[ $taxonomy ] = array_values( array_unique( array_merge(
            $initial_filters[ $taxonomy ] ?? array(),
            $values
        ) ) );
    };

    foreach ( cpf_parse_path_filters( get_query_var( 'cpf_path' ) ) as $taxonomy => $values ) {
        $merge_filter( $taxonomy, $values );
    }

    $legacy_keys = array(
        'wpf_filter_brand' => 'pa_brand',
        'jewelry_type'     => 'pa_jewelry_type',
    );

    foreach ( cpf_path_attribute_map() as $taxonomy ) {
        $legacy_keys[ $taxonomy ] = $taxonomy;
    }

    foreach ( $legacy_keys as $key => $taxonomy ) {
        if ( isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) {
            $merge_filter(
                $taxonomy,
                explode( ',', sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) )
            );
        }
    }

    /* Category from ?category=ID */
    if ( ! $current_category && isset( $_GET['category'] ) ) {
        $requested_category = absint( $_GET['category'] );

        foreach ( $categories as $category ) {
            if ( (int) $category->term_id === $requested_category ) {
                $current_category = $requested_category;
                break;
            }
        }
    }

    /* Auto-select the category when the URL has a category-specific attribute */
    if ( ! $current_category ) {
        $attribute_category = array(
            'pa_watch_style'  => 'watches',
            'pa_material'     => 'watches',
            'pa_jewelry_type' => 'jewelry',
            'pa_stone_type'   => 'jewelry',
            'pa_collection'   => 'handbags',
            'pa_color'        => 'handbags',
        );

        foreach ( $attribute_category as $taxonomy => $category_slug ) {
            if ( empty( $initial_filters[ $taxonomy ] ) ) {
                continue;
            }

            foreach ( $categories as $category ) {
                if ( $category->slug === $category_slug ) {
                    $current_category = absint( $category->term_id );
                    break 2;
                }
            }
        }
    }

    $has_filters = ! empty( array_filter( $initial_filters ) )
        || ! empty( $_GET['min_price'] )
        || ! empty( $_GET['max_price'] )
        || isset( $_GET['category'] );

    /* Category id => path */
    $category_paths = array();

    foreach ( $categories as $category ) {
        $link = get_term_link( $category );

        if ( ! is_wp_error( $link ) ) {
            $category_paths[ $category->term_id ] = trailingslashit(
                wp_parse_url( $link, PHP_URL_PATH )
            );
        }
    }

    $path_mode = ( function_exists( 'is_shop' ) && is_shop() )
        || is_product_category();

    $category_radio_name = wp_unique_id( 'cpf_category_' );

    ob_start();
    ?>
    <div
        class="cpf-filter"
        data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
        data-nonce="<?php echo esc_attr( wp_create_nonce( 'cpf_filter_nonce' ) ); ?>"
        data-current-category="<?php echo esc_attr( $current_category ); ?>"
        data-shop-path="<?php echo esc_attr( trailingslashit( wp_parse_url( wc_get_page_permalink( 'shop' ), PHP_URL_PATH ) ) ); ?>"
        data-has-filters="<?php echo $has_filters ? '1' : '0'; ?>"
        data-path-mode="<?php echo $path_mode ? '1' : '0'; ?>"
        data-category-paths="<?php echo esc_attr( wp_json_encode( $category_paths ) ); ?>"
    >
        <div class="cpf-category-field cpf-group">
            <button type="button" class="cpf-heading cpf-category-heading" aria-expanded="false">
                <span>Choose Product Category</span>
                <span class="cpf-plus">+</span>
            </button>

            <div class="cpf-options cpf-category-options">
                <label class="cpf-option">
                    <input
                        type="radio"
                        class="cpf-category"
                        name="<?php echo esc_attr( $category_radio_name ); ?>"
                        value="0"
                        <?php checked( ! $current_category ); ?>
                    >
                    <span class="cpf-check"></span>
                    <span>All Categories</span>
                </label>

                <?php foreach ( $categories as $category ) : ?>
                    <label class="cpf-option">
                        <input
                            type="radio"
                            class="cpf-category"
                            name="<?php echo esc_attr( $category_radio_name ); ?>"
                            value="<?php echo esc_attr( $category->term_id ); ?>"
                            <?php checked(
                                (int) $current_category,
                                (int) $category->term_id
                            ); ?>
                        >
                        <span class="cpf-check"></span>
                        <span><?php echo esc_html( $category->name ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cpf-dynamic-attributes">
            <?php
            echo cpf_render_attribute(
                'pa_brand',
                'Brand',
                $current_category,
                $initial_filters['pa_brand'] ?? array()
            );

            echo cpf_render_attribute(
                'pa_size',
                'Size',
                $current_category,
                $initial_filters['pa_size'] ?? array()
            );

            echo cpf_render_category_extra_attributes(
                $current_category,
                $initial_filters
            );
            ?>
        </div>

        <div class="cpf-price">
            <h4>Price Range</h4>

            <div class="cpf-price-inputs">
                <label>
                    <span>Min price</span>
                    <input
                        type="number"
                        class="cpf-min"
                        min="0"
                        step="any"
                        placeholder="0"
                        value="<?php echo isset( $_GET['min_price'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['min_price'] ) ) ) : ''; ?>"
                    >
                </label>

                <label>
                    <span>Max price</span>
                    <input
                        type="number"
                        class="cpf-max"
                        min="0"
                        step="any"
                        placeholder="No limit"
                        value="<?php echo isset( $_GET['max_price'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['max_price'] ) ) ) : ''; ?>"
                    >
                </label>
            </div>

            <div class="cpf-button">
                <p class="cpf-status" aria-live="polite"></p>
                <button type="button" class="cpf-apply">View Results</button>
                <button type="button" class="cpf-clear">Clear All</button>
            </div>
        </div>
    </div>
    <?php

    return ob_get_clean();
} );


/* AJAX: Load attributes after category selection */
add_action( 'wp_ajax_cpf_get_attributes', 'cpf_ajax_get_attributes' );
add_action( 'wp_ajax_nopriv_cpf_get_attributes', 'cpf_ajax_get_attributes' );

function cpf_ajax_get_attributes() {
    check_ajax_referer( 'cpf_filter_nonce', 'nonce' );

    $category_id = absint( $_POST['category'] ?? 0 );

    wp_send_json_success( array(
        'brand' => cpf_render_attribute( 'pa_brand', 'Brand', $category_id ),
        'size'  => cpf_render_attribute( 'pa_size', 'Size', $category_id ),
        'extra' => cpf_render_category_extra_attributes( $category_id ),
    ) );
}


/* Render Elementor product card */
function cpf_render_product_card( $product_id, $template_id ) {
    $product = get_post( $product_id );

    if ( ! $product || ! $template_id ) {
        return '';
    }

    global $post, $wp_query;

    $old_post  = $post;
    $old_query = $wp_query;

    $post = $product;
    setup_postdata( $post );

    $html = '';

    if ( class_exists( '\Elementor\Plugin' ) ) {
        $html = \Elementor\Plugin::instance()
            ->frontend
            ->get_builder_content_for_display( $template_id, true );
    }

    wp_reset_postdata();

    $post     = $old_post;
    $wp_query = $old_query;

    return $html;
}


/* AJAX: Filter products */
add_action( 'wp_ajax_cpf_filter_products', 'cpf_ajax_filter_products' );
add_action( 'wp_ajax_nopriv_cpf_filter_products', 'cpf_ajax_filter_products' );

function cpf_ajax_filter_products() {
    check_ajax_referer( 'cpf_filter_nonce', 'nonce' );

    if ( ! class_exists( 'WooCommerce' ) ) {
        wp_send_json_error( array(
            'message' => 'WooCommerce is unavailable.',
        ) );
    }

    $category_id = absint( $_POST['category'] ?? 0 );

    $min_price = isset( $_POST['min_price'] ) && $_POST['min_price'] !== ''
        ? wc_format_decimal( wp_unslash( $_POST['min_price'] ) )
        : '';

    $max_price = isset( $_POST['max_price'] ) && $_POST['max_price'] !== ''
        ? wc_format_decimal( wp_unslash( $_POST['max_price'] ) )
        : '';

    if (
        $min_price !== ''
        && $max_price !== ''
        && (float) $min_price > (float) $max_price
    ) {
        wp_send_json_error( array(
            'message' => 'Minimum price must not exceed maximum price.',
        ) );
    }

    $tax_query = array( 'relation' => 'AND' );

    if ( $category_id ) {
        $tax_query[] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => $category_id,
            'include_children' => true,
        );
    }

    $filter_taxonomies = array(
        'pa_brand',
        'pa_size',
        'pa_watch_style',
        'pa_material',

        // Watches attributes
        'pa_case_size',
        'pa_condition',
        'pa_dial',
        'pa_bezel',
        'pa_series',
        'pa_warranty_papers',
        'pa_box',

        // Jewelry attributes
        'pa_jewelry_type',
        'pa_stone_type',

        // Handbags attributes
        'pa_collection',
        'pa_color',
    );

    foreach ( $filter_taxonomies as $taxonomy ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }

        $values = isset( $_POST[ $taxonomy ] ) && is_array( $_POST[ $taxonomy ] )
            ? array_map(
                'sanitize_title',
                wp_unslash( $_POST[ $taxonomy ] )
            )
            : array();

        $values = array_filter( $values );

        /*
         * Support /shop/?jewelry_type=diamond
         * while keeping the actual attribute taxonomy unchanged.
         */
        if ( $taxonomy === 'pa_jewelry_type' && isset( $_POST['jewelry_type'] ) ) {
            $url_jewelry_types = sanitize_text_field(
                wp_unslash( $_POST['jewelry_type'] )
            );

            $url_jewelry_types = array_filter(
                array_map( 'sanitize_title', explode( ',', $url_jewelry_types ) )
            );

            $values = array_values(
                array_unique(
                    array_merge( $values, $url_jewelry_types )
                )
            );
        }

        if ( $values ) {
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $values,
                'operator' => 'IN',
            );
        }
    }

    $meta_query = array();

    if ( $min_price !== '' || $max_price !== '' ) {
        $price_clause = array(
            'key'  => '_price',
            'type' => 'DECIMAL(20,6)',
        );

        if ( $min_price !== '' && $max_price !== '' ) {
            $price_clause['value'] = array(
                (float) $min_price,
                (float) $max_price,
            );
            $price_clause['compare'] = 'BETWEEN';
        } elseif ( $min_price !== '' ) {
            $price_clause['value'] = (float) $min_price;
            $price_clause['compare'] = '>=';
        } else {
            $price_clause['value'] = (float) $max_price;
            $price_clause['compare'] = '<=';
        }

        $meta_query[] = $price_clause;
    }

    $args = array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 12,
        'paged'               => 1,
        'orderby'             => 'menu_order title',
        'order'               => 'ASC',
        'ignore_sticky_posts' => true,
    );

    if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
    }

    if ( $meta_query ) {
        $args['meta_query'] = $meta_query;
    }

    $template_id = 326;

    /* Cache: same filter = instant response */
    $cache_key = cpf_cache_key( 'products', array( $args, $template_id ) );
    $cached    = get_transient( $cache_key );

    if ( $cached !== false && is_array( $cached ) ) {
        wp_send_json_success( $cached );
    }

    $query = new WP_Query( $args );

    // $template_id = cpf_find_loop_template_id();

    if ( ! $template_id ) {
        wp_send_json_error( array(
            'message' => 'Could not automatically find the Elementor Loop Item template. Check the Loop Grid CSS ID and template settings.',
        ) );
    }

    $html = '';

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $html .= '<div class="cpf-loop-item">';
            $html .= cpf_render_product_card( get_the_ID(), $template_id );
            $html .= '</div>';
        }
    }

    wp_reset_postdata();

    $result = array(
        'html'  => $html,
        'count' => (int) $query->found_posts,
        'pages' => (int) $query->max_num_pages,
    );

    if ( $html !== '' ) {
        set_transient( $cache_key, $result, 6 * HOUR_IN_SECONDS );
    }

    wp_send_json_success( $result );
}


/* CSS and JavaScript */
add_action( 'wp_footer', function() {
    if ( is_admin() && ! is_archive() ) {
        return;
    }
    ?>
    <style>

/* ==========================================
   CPF FILTER - REFERENCE IMAGE DESIGN
   ONLY CSS CHANGES
========================================== */

.cpf-filter {
    width: 100%;
    display: flex;
    flex-direction: column;
    color: #10243d;
    font-family: "Figtree", sans-serif;
    font-size: 14px;
    box-sizing: border-box;
}

.cpf-filter *,
.cpf-filter *::before,
.cpf-filter *::after {
    box-sizing: border-box;
}

/* PRICE SECTION */
.cpf-price {
    order: -1;
    width: 100%;
    padding: 12px 0 16px !important;
    margin: 0;
}

.cpf-price h4 {
    margin: 0 0 20px;
    color: #10243d;
    font-family: "Figtree", sans-serif;
    font-size: 15px;
    font-weight: 600;
}

.cpf-price-inputs {
    display: flex;
    gap: 30px;
    width: 100%;
}

.cpf-price-inputs label {
    display: block;
    flex: 1;
    min-width: 0;
    margin: 0;
}

.cpf-price-inputs label span {
    display: block;
    margin-bottom: 5px;
    color: #10243d;
    font-size: 13px;
    font-weight: 400;
}

.cpf-price-inputs input {
    display: block;
    width: 100%;
    padding: 6px 8px 9px;
    border: 0;
    border-bottom: 1px solid #333;
    border-radius: 0;
    background: transparent;
    color: #64748b;
    font-family: "Figtree", sans-serif;
    font-size: 14px;
    outline: none;
    box-shadow: none;
}

.cpf-price-inputs input:focus {
    border-bottom: 1px solid #10243d;
    outline: none;
}

/* FILTER GROUP */
.cpf-group {
    width: 100%;
    margin: 0;
    border-bottom: 1px solid #dedede;
}

/* FILTER HEADING */
.cpf-heading {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    width: 100%;
    padding: 15px 0 10px !important;
    margin: 0;
    border: 0 !important;
    border-radius: 0;
    background: transparent !important;
    color: #10243d !important;
    font-family: "Figtree", sans-serif;
    font-size: 15px !important;
    font-weight: 600 !important;
    line-height: 1.4;
    text-align: left;
    cursor: default;
    box-shadow: none !important;
}

/* REMOVE PLUS ICON */
.cpf-plus {
    display: none !important;
}

/* ALWAYS SHOW ALL FILTER VALUES */
.cpf-options {
    display: block !important;
    width: 100%;
    height: auto !important;
    max-height: none !important;
    overflow: visible !important;
    padding: 5px 0 14px !important;
}

/* REMOVE DROPDOWN HOVER EFFECT */
.cpf-heading:hover,
.cpf-heading:focus,
.cpf-group.is-open > .cpf-heading,
.cpf-heading[aria-expanded="true"] {
    background: transparent !important;
    color: #10243d !important;
    outline: none !important;
    box-shadow: none !important;
}

.cpf-group.is-open > .cpf-heading span,
.cpf-heading[aria-expanded="true"] span {
    color: #10243d !important;
}

/* FILTER OPTIONS */
.cpf-option {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0;
    width: 100%;
    padding: 10px 0 !important;
    margin: 0;
    color: #64748b;
    font-family: "Figtree", sans-serif;
    font-size: 13px !important;
    font-weight: 400;
    line-height: 1.5;
    cursor: pointer;
}

.cpf-option > span:last-child {
    color: #10243d;
    font-size: 13px;
    font-weight: 400;
}

.cpf-option:hover > span:last-child {
    color: #10243d;
}

/* HIDE CHECKBOX DESIGN */
.cpf-check {
    display: none !important;
}

/* KEEP CHECKBOX FUNCTIONALITY */
.cpf-option input {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

/* SELECTED OPTION */
.cpf-option:has(input:checked) > span:last-child {
    color: #10243d !important;
    font-weight: 600 !important;
    display: inline-block;
    width: fit-content;
    border-bottom: 1px solid #777;
    padding-bottom: 2px;
}

/* CATEGORY OPTIONS */
.cpf-category-options {
    display: block !important;
    max-height: none !important;
    overflow: visible !important;
    padding-bottom: 14px !important;
}

.cpf-category-heading {
    padding-top: 15px !important;
}

/* PRICE BUTTONS */
.cpf-button {
    display: flex;
    flex-direction: column;
    position: fixed;
    left: auto;
    bottom: 0;
    z-index: 100;
    width: 370px;
    max-width: 100%;
    padding: 10px 0;
    background: #fff;
}

/* APPLY BUTTON */
.cpf-filter .cpf-apply,
.cpf-filter .cpf-apply:focus {
    display: inline-block;
    width: 100%;
    margin-top: 14px;
    padding: 12px 18px;
    border: 1px solid #000 !important;
    border-radius: 8px !important;
    background: #000 !important;
    color: #fff !important;
    font-family: "Figtree", sans-serif;
    font-size: 14px;
    font-weight: 400;
    text-transform: uppercase;
    cursor: pointer;
    transition: opacity .25s ease;
}

.cpf-filter .cpf-apply:hover {
    background: #000 !important;
    color: #fff !important;
    opacity: .8;
}

/* CLEAR BUTTON */
.cpf-filter .cpf-clear {
    display: inline-block;
    width: 100%;
    margin-top: 10px;
    padding: 12px 18px;
    border: 1px solid #ccc;
    border-radius: 8px !important;
    background: #fff;
    color: #222;
    font-family: "Figtree", sans-serif;
    font-size: 14px;
    font-weight: 400;
    text-transform: uppercase;
    cursor: pointer;
}

.cpf-filter .cpf-clear:hover,
.cpf-filter .cpf-clear:focus {
    background: #000;
    border-color: #000;
    color: #fff;
}

/* STATUS */
.cpf-status {
    margin: 5px 0;
    color: #64748b;
    font-family: "Figtree", sans-serif;
    font-size: 13px;
    line-height: 1.5;
}

/* LOADING */
.cpf-loading {
    opacity: .6;
    pointer-events: none;
}

/* ==========================================
   VIEW MORE - ADDED
========================================== */

.cpf-option.cpf-hidden {
    display: none !important;
}

.cpf-view-more {
    display: inline-block;
    width: auto;
    margin: 8px 0 10px;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    color: #10243d !important;
    font-family: "Figtree", sans-serif;
    font-size: 13px;
    font-weight: 600;
    text-decoration: underline;
    cursor: pointer;
    box-shadow: none !important;
}

.cpf-view-more:hover,
.cpf-view-more:focus {
    background: transparent !important;
    color: #64748b !important;
    outline: none !important;
}

/* ==========================================
   PRODUCT LOOP LOADER
========================================== */

#product_loop {
    position: relative;
    min-height: 260px;
}

#product_loop.cpf-loop-loading > * {
    opacity: .25;
    transition: opacity .2s ease;
    pointer-events: none;
}

#product_loop.cpf-loop-loading::after {
    content: "";
    position: absolute;
    top: 120px;
    left: 50%;
    width: 42px;
    height: 42px;
    margin-left: -21px;
    border: 3px solid #e2e2e2;
    border-top-color: #000;
    border-radius: 50%;
    animation: cpf-spin .7s linear infinite;
    z-index: 5;
}

#product_loop .cpf-loading-text {
    display: none;
}

#product_loop.cpf-loop-loading .cpf-loading-text {
    display: block;
    position: absolute;
    top: 175px;
    left: 0;
    width: 100%;
    text-align: center;
    color: #10243d;
    font-family: "Figtree", sans-serif;
    font-size: 14px;
    z-index: 5;
    opacity: 1 !important;
}

@keyframes cpf-spin {
    to { transform: rotate(360deg); }
}

.cpf-no-products {
    width: 100%;
    padding: 40px 0;
    text-align: center;
    color: #64748b;
    font-family: "Figtree", sans-serif;
}

/* RESPONSIVE */
@media (max-width: 480px) {

    .cpf-price-inputs {
        gap: 20px;
    }

    .cpf-heading {
        font-size: 15px !important;
    }

    .cpf-option {
        font-size: 13px !important;
    }

    .cpf-apply,
    .cpf-clear {
        padding: 10px 12px;
        font-size: 16px;
    }
}

.cpf-button {
    display: flex;
    flex-direction: column;
    position: fixed;
    width: 338px;
    padding-bottom: 10px;
    bottom: 0;
    background-color: #fff;
}

@media (max-width: 767px) {
    .cpf-button {
        width: 100% !important;
		left: 0 !important;	
		padding-left: 10px !important;
		padding-right: 10px !important;
    }
}

    </style>

    <script>
    (function() {

        function initCPF() {

            document.querySelectorAll('.cpf-filter:not([data-cpf-init])')
                .forEach(function(filter) {

                filter.dataset.cpfInit = '1';

                var attributes = filter.querySelector('.cpf-dynamic-attributes');
                var status = filter.querySelector('.cpf-status');
                var minInput = filter.querySelector('.cpf-min');
                var maxInput = filter.querySelector('.cpf-max');
                var applyButton = filter.querySelector('.cpf-apply');
                var clearButton = filter.querySelector('.cpf-clear');
				var folterClose = document.querySelector('.folter_close')



                function getCategoryValue() {
                    var selectedCategory = filter.querySelector('.cpf-category:checked');
                    return selectedCategory ? selectedCategory.value : '0';
                }
					
					
					

                /* ==========================================
                   VIEW MORE FUNCTION
                ========================================== */

                function setupViewMore() {

                    filter.querySelectorAll('.cpf-options').forEach(function(options) {

                        // Get only direct cpf-option children
                        var items = Array.from(options.children).filter(function(item) {
                            return item.classList.contains('cpf-option');
                        });

                        // Remove old View More button
                        var oldButton = options.querySelector('.cpf-view-more');

                        if (oldButton) {
                            oldButton.remove();
                        }

                        // Reset all options
                        items.forEach(function(item) {
                            item.classList.remove('cpf-hidden');
                        });

                        // If 5 or fewer options, no View More
                        if (items.length <= 5) {
                            return;
                        }

                        // Hide options after first 5
                        items.forEach(function(item, index) {
                            if (index >= 5) {
                                item.classList.add('cpf-hidden');
                            }
                        });

                        // Create View More button
                        var button = document.createElement('button');

                        button.type = 'button';
                        button.className = 'cpf-view-more';
                        button.textContent = 'View More';

                        // Expand / collapse
                        button.addEventListener('click', function() {

                            var expanded = button.dataset.expanded === '1';

                            items.forEach(function(item, index) {
                                if (index >= 5) {
                                    item.classList.toggle('cpf-hidden', expanded);
                                }
                            });

                            button.dataset.expanded = expanded ? '0' : '1';

                            button.textContent = expanded
                                ? 'View More'
                                : 'View Less';
                        });

                        options.appendChild(button);
                    });
                }


                /* EXISTING GROUP FUNCTION + VIEW MORE */
                function bindGroups() {

                    filter.querySelectorAll('.cpf-heading').forEach(function(button) {

                        if (button.dataset.bound) {
                            return;
                        }

                        button.dataset.bound = '1';

                        button.addEventListener('click', function() {

                            var group = button.closest('.cpf-group');

                            if (!group) {
                                return;
                            }

                            var isOpen = group.classList.toggle('is-open');

                            button.setAttribute(
                                'aria-expanded',
                                isOpen ? 'true' : 'false'
                            );
                        });
                    });

                    // Run View More on initial load and after AJAX reload
                    setupViewMore();
                }


                function selectedTerms() {

                    var result = {};

                    [
                        'pa_brand',
                        'pa_size',
                        'pa_watch_style',
                        'pa_material',
                        'pa_case_size',
                        'pa_condition',
                        'pa_dial',
                        'pa_bezel',
                        'pa_series',
                        'pa_warranty_papers',
                        'pa_box',
                        'pa_jewelry_type',
                        'pa_stone_type',
                        'pa_collection',
                        'pa_color'
                    ].forEach(function(taxonomy) {
                        result[taxonomy] = [];
                    });

                    filter.querySelectorAll('.cpf-term:checked').forEach(function(input) {
                        var taxonomy = input.dataset.taxonomy;

                        if (Object.prototype.hasOwnProperty.call(result, taxonomy)) {
                            result[taxonomy].push(input.value);
                        }
                    });

                    return result;
                }


                /* Path URL */
                var pathMap = [
                    ['brand', 'pa_brand'],
                    ['size', 'pa_size'],
                    ['watch-style', 'pa_watch_style'],
                    ['material', 'pa_material'],
                    ['jewelry-type', 'pa_jewelry_type'],
                    ['stone-type', 'pa_stone_type'],
                    ['collection', 'pa_collection'],
                    ['color', 'pa_color']
                ];


                function updateFilterURL(payload) {

                    var url = new URL(window.location.href);
                    var shopPath = filter.dataset.shopPath || '/shop/';
                    var pathMode = filter.dataset.pathMode === '1';
                    var categoryPaths = {};

                    try {
                        categoryPaths = JSON.parse(filter.dataset.categoryPaths || '{}');
                    } catch (error) {
                        categoryPaths = {};
                    }

                    var categoryPath = (payload.category && payload.category !== '0')
                        ? categoryPaths[payload.category]
                        : '';

                    [
                        'wpf_filter_brand',
                        'wpf_fbv',
                        'category',
                        'min_price',
                        'max_price',
                        'jewelry_type'
                    ]
                        .concat(pathMap.map(function(pair) {
                            return pair[1];
                        }))
                        .forEach(function(key) {
                            url.searchParams.delete(key);
                        });

                    /* /product-category/watches/ when category selected */
                    if (pathMode) {
                        url.pathname = categoryPath || shopPath;
                    }

                    pathMap.forEach(function(pair) {

                        var values = payload[pair[1]];

                        if (!values || !values.length) {
                            return;
                        }

                        if (pathMode) {
                            url.pathname += pair[0] + '/' +
                                values.map(encodeURIComponent).join(',') + '/';
                        } else if (pair[1] === 'pa_brand') {
                            url.searchParams.set('wpf_filter_brand', values.join(','));
                            url.searchParams.set('wpf_fbv', '1');
                        } else {
                            url.searchParams.set(pair[1], values.join(','));
                        }
                    });

                    if (payload.category && payload.category !== '0' && !(pathMode && categoryPath)) {
                        url.searchParams.set('category', payload.category);
                    }

                    if (payload.min_price !== '') {
                        url.searchParams.set('min_price', payload.min_price);
                    }

                    if (payload.max_price !== '') {
                        url.searchParams.set('max_price', payload.max_price);
                    }

                    window.history.replaceState({}, '', url.toString());
                }


                function request(action, extra) {

                    var data = new FormData();

                    data.append('action', action);
                    data.append('nonce', filter.dataset.nonce);

                    Object.keys(extra || {}).forEach(function(key) {

                        if (Array.isArray(extra[key])) {
                            extra[key].forEach(function(value) {
                                data.append(key + '[]', value);
                            });
                        } else {
                            data.append(key, extra[key]);
                        }
                    });

                    return fetch(filter.dataset.ajax, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: data
                    }).then(function(response) {
                        return response.json();
                    });
                }


                /*
                 * FIX: Find the loop container. If Elementor rendered
                 * "Nothing found" (no .elementor-loop-container),
                 * create the container ourselves.
                 */
                function getLoopContainer(loop) {

                    var container = loop.querySelector('.elementor-loop-container');

                    if (container) {
                        return container;
                    }

                    container = document.createElement('div');
                    container.className = 'elementor-loop-container elementor-grid';

                    var wrap = loop.querySelector('.elementor-widget-container') || loop;

                    wrap.innerHTML = '';
                    wrap.appendChild(container);

                    return container;
                }


                function showLoader(loop) {

                    /* Remove Elementor "Nothing found" message while loading */
                    var existing = loop.querySelector('.elementor-loop-container');

                    if (!existing) {
                        getLoopContainer(loop);
                    }

                    if (!loop.querySelector('.cpf-loading-text')) {
                        var text = document.createElement('div');
                        text.className = 'cpf-loading-text';
                        text.textContent = 'Loading products...';
                        loop.appendChild(text);
                    }

                    loop.classList.add('cpf-loop-loading');
                }


                function hideLoader(loop) {
                    loop.classList.remove('cpf-loop-loading');
                }


                function updateProducts() {

                    var loop = document.querySelector('#product_loop');

                    if (!loop) {
                        status.textContent = 'Could not find #product_loop on this page.';
                        return;
                    }

                    showLoader(loop);

                    var terms = selectedTerms();

                    var payload = {
                        category: getCategoryValue(),
                        min_price: minInput.value.trim(),
                        max_price: maxInput.value.trim()
                    };

                    Object.keys(terms).forEach(function(taxonomy) {
                        payload[taxonomy] = terms[taxonomy];
                    });

                    /* Send Jewelry Type URL value to PHP as well */
                    payload.jewelry_type = (
                        terms['pa_jewelry_type'] &&
                        terms['pa_jewelry_type'].length
                    )
                        ? terms['pa_jewelry_type'].join(',')
                        : '';

                    updateFilterURL(payload);

                    filter.classList.add('cpf-loading');
                    status.textContent = 'Updating products...';

                    request('cpf_filter_products', payload)
                        .then(function(response) {

                            if (!response.success) {
                                throw new Error(
                                    response.data && response.data.message
                                        ? response.data.message
                                        : 'Filtering failed.'
                                );
                            }

                            var container = getLoopContainer(loop);

                            container.innerHTML = response.data.html;

                            if (!response.data.html) {
                                container.innerHTML = '<p class="cpf-no-products">No products found.</p>';
                            }

                            status.textContent = response.data.count + ' products found.';
                        })
                        .catch(function(error) {
                            status.textContent = error.message;
                        })
                        .finally(function() {
                            filter.classList.remove('cpf-loading');
                            hideLoader(loop);
                        });
                }


                function updateAttributes() {

                    attributes.innerHTML = '<p>Loading filters...</p>';
                    filter.classList.add('cpf-loading');

                    request('cpf_get_attributes', {
                        category: getCategoryValue()
                    })
                    .then(function(response) {

                        if (!response.success) {
                            throw new Error('Could not load attributes.');
                        }

                        attributes.innerHTML =
                            response.data.brand +
                            response.data.size +
                            (response.data.extra || '');

                        bindGroups();

                        updateProducts();
                    })
                    .catch(function(error) {
                        attributes.innerHTML = '';
                        status.textContent = error.message;
                    })
                    .finally(function() {
                        filter.classList.remove('cpf-loading');
                    });
                }


                filter.querySelectorAll('.cpf-category').forEach(function(input) {

                    input.addEventListener('change', function() {

                        var categoryPaths = {};

                        try {
                            categoryPaths = JSON.parse(
                                filter.dataset.categoryPaths || '{}'
                            );
                        } catch (error) {
                            categoryPaths = {};
                        }

                        var selectedCategory = input.value;

                        // Directly open the selected category archive
                        if (selectedCategory !== '0' && categoryPaths[selectedCategory]) {
                            window.location.href = categoryPaths[selectedCategory];
                            return;
                        }

                        // All Categories: return to the Shop page
                        if (selectedCategory === '0') {
                            window.location.href = filter.dataset.shopPath || '/shop/';
                        }
                    });
                });


                filter.addEventListener('change', function(event) {

                    if (event.target.classList.contains('cpf-term')) {
                        // updateProducts();
                    }
                });


                applyButton.addEventListener('click', function() {

                    updateProducts();

                    var offcanvasElements = [
                        document.querySelector('#off-canvas-c27cf1e'),
                        document.querySelector('#off-canvas-798f353')
                    ];

                    offcanvasElements.forEach(function(offcanvas) {

                        if (!offcanvas || !offcanvas.querySelector('div')) {
                            return;
                        }

                        offcanvas.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                        offcanvas.style.opacity = '0';
                        offcanvas.style.transform = 'translateX(-20px)';
                        offcanvas.style.pointerEvents = 'none';

                        setTimeout(function() {

                            offcanvas.setAttribute('aria-hidden', 'true');

                            offcanvas.style.opacity = '';
                            offcanvas.style.transform = '';
                            offcanvas.style.pointerEvents = '';
                            offcanvas.style.transition = '';

                        }, 350);
                    });
                });
					
				folterClose.addEventListener('click', function () {
					applyButton.click()
				})


                clearButton.addEventListener('click', function() {

                    minInput.value = '';
                    maxInput.value = '';

                    filter.querySelectorAll('.cpf-term').forEach(function(input) {
                        input.checked = false;
                    });

                    var allCategories = filter.querySelector('.cpf-category[value="0"]');

                    if (allCategories) {
                        allCategories.checked = true;
                    }

                    updateAttributes();
                });


                [minInput, maxInput].forEach(function(input) {

                    input.addEventListener('keydown', function(event) {

                        if (event.key === 'Enter') {
                            event.preventDefault();
                            updateProducts();
                        }
                    });
                });


                bindGroups();

                /*
                 * If URL already has filters,
                 * matching checkboxes are rendered checked by PHP.
                 * Loop is rendered by server (Query ID: cpf_products),
                 * JS also refreshes it as a safety net.
                 */
                if (filter.dataset.hasFilters === '1') {

                    var initialLoop = document.querySelector('#product_loop');
                    var initialContainer = initialLoop
                        ? initialLoop.querySelector('.elementor-loop-container')
                        : null;

                    /*
                     * Server already rendered filtered products
                     * (Query ID: cpf_products) => no second AJAX request.
                     * Otherwise show the loader and fetch via AJAX.
                     */
                    if (!initialContainer || !initialContainer.children.length) {
                        updateProducts();
                    }
                }

            });
        }


        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCPF);
        } else {
            initCPF();
        }

    })();
    </script>
    <?php
}, 99 );
