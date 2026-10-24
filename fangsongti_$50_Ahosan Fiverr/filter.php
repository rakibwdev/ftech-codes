<?php
/**
 * Shortcode: [pdf_document_filter]
 */

/* -------------------------------------------------------
 * 1. CONFIGURATION
 * ----------------------------------------------------- */

function rakib_pdf_filter_config() {
    return array(
        'post_type'        => 'pdf-post',
        'file_taxonomy'    => 'file-type',
        'product_taxonomy' => 'product-name',
        'pdf_field'        => 'pdf_link',
        'posts_per_page'   => -1,
    );
}

/* -------------------------------------------------------
 * 2. GET ACF PDF URL
 * Supports ACF URL, File Array, and Attachment ID.
 * ----------------------------------------------------- */

function rakib_pdf_filter_get_file_url( $post_id ) {
    $config = rakib_pdf_filter_config();
    $file   = get_field( $config['pdf_field'], $post_id );

    if ( is_array( $file ) && ! empty( $file['url'] ) ) {
        $url = $file['url'];
    } elseif ( is_numeric( $file ) ) {
        $url = wp_get_attachment_url( absint( $file ) );
    } elseif ( is_string( $file ) ) {
        $url = $file;
    } else {
        $url = '';
    }

    return $url ? esc_url_raw( $url ) : '';
}

/* -------------------------------------------------------
 * 3. GET CHECKBOX TERMS
 * ----------------------------------------------------- */

function rakib_pdf_filter_get_terms( $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return new WP_Error( 'invalid_taxonomy', 'Taxonomy not found.' );
    }

    return get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
}

function rakib_pdf_filter_render_checkboxes( $taxonomy, $group ) {
    $terms = rakib_pdf_filter_get_terms( $taxonomy );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '<p class="pdf-filter-empty">No terms found.</p>';
    }

    $html = '<div class="pdf-filter-options">';

    foreach ( $terms as $term ) {
        $id = 'pdf-' . $group . '-' . absint( $term->term_id );

        $html .= '<label class="pdf-filter-option" for="' .
            esc_attr( $id ) . '">';

        $html .= '<input type="checkbox" id="' .
            esc_attr( $id ) . '" class="pdf-filter-checkbox" ' .
            'data-group="' . esc_attr( $group ) . '" ' .
            'value="' . absint( $term->term_id ) . '">';

        $html .= '<span>' . esc_html( $term->name ) . '</span>';
        $html .= '</label>';
    }

    $html .= '</div>';

    return $html;
}

/* -------------------------------------------------------
 * 4. SHORTCODE: FILTER INTERFACE + RESULTS
 * ----------------------------------------------------- */

add_shortcode( 'pdf_document_filter', function() {
    $config = rakib_pdf_filter_config();

    if (
        ! post_type_exists( $config['post_type'] ) ||
        ! taxonomy_exists( $config['file_taxonomy'] ) ||
        ! taxonomy_exists( $config['product_taxonomy'] )
    ) {
        return '<p>PDF library configuration error. Check your post type and taxonomy slugs.</p>';
    }

    $instance_id = wp_unique_id( 'pdf-library-' );

    ob_start();
    ?>

    <div
        id="<?php echo esc_attr( $instance_id ); ?>"
        class="pdf-document-library"
        data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
        data-nonce="<?php echo esc_attr( wp_create_nonce( 'rakib_pdf_filter_nonce' ) ); ?>"
    >

        <div class="pdf-filter-tabs" role="tablist">
            <button
                type="button"
                class="pdf-filter-tab is-active"
                data-tab="file"
                role="tab"
                aria-selected="true"
            >File Type</button>

            <button
                type="button"
                class="pdf-filter-tab"
                data-tab="product"
                role="tab"
                aria-selected="false"
            >Product Name</button>
        </div>

        <div class="pdf-filter-panels">

            <div
                class="pdf-filter-panel is-active"
                data-panel="file"
                role="tabpanel"
            >
                <?php
                echo wp_kses(
                    rakib_pdf_filter_render_checkboxes(
                        $config['file_taxonomy'],
                        'file'
                    ),
                    array(
                        'div' => array( 'class' => true ),
                        'label' => array(
                            'class' => true,
                            'for' => true,
                        ),
                        'input' => array(
                            'type' => true,
                            'id' => true,
                            'class' => true,
                            'data-group' => true,
                            'value' => true,
                        ),
                        'span' => array(),
                        'p' => array( 'class' => true ),
                    )
                );
                ?>
            </div>

            <div
                class="pdf-filter-panel"
                data-panel="product"
                role="tabpanel"
                hidden
            >
                <?php
                echo wp_kses(
                    rakib_pdf_filter_render_checkboxes(
                        $config['product_taxonomy'],
                        'product'
                    ),
                    array(
                        'div' => array( 'class' => true ),
                        'label' => array(
                            'class' => true,
                            'for' => true,
                        ),
                        'input' => array(
                            'type' => true,
                            'id' => true,
                            'class' => true,
                            'data-group' => true,
                            'value' => true,
                        ),
                        'span' => array(),
                        'p' => array( 'class' => true ),
                    )
                );
                ?>
            </div>

        </div>

        <div class="pdf-filter-toolbar">
            <label for="<?php echo esc_attr( $instance_id . '-sort' ); ?>">
                Sort Product Name
            </label>

            <select id="<?php echo esc_attr( $instance_id . '-sort' ); ?>"
                    class="pdf-filter-sort">
                <option value="asc">A–Z</option>
                <option value="desc">Z–A</option>
            </select>

            <button type="button" class="pdf-filter-reset">
                Clear filters
            </button>
        </div>

        <div class="pdf-filter-status" aria-live="polite"></div>

        <div class="pdf-filter-results" aria-live="polite">
            <p>Select a File Type or Product Name to filter documents.</p>
        </div>

    </div>

    <?php
    return ob_get_clean();
} );

/* -------------------------------------------------------
 * 5. AJAX FILTER ENDPOINT
 * ----------------------------------------------------- */

add_action( 'wp_ajax_rakib_filter_pdf_documents', 'rakib_pdf_filter_ajax' );
add_action( 'wp_ajax_nopriv_rakib_filter_pdf_documents', 'rakib_pdf_filter_ajax' );

function rakib_pdf_filter_ajax() {
    check_ajax_referer( 'rakib_pdf_filter_nonce', 'nonce' );

    $config = rakib_pdf_filter_config();

    if (
        ! post_type_exists( $config['post_type'] ) ||
        ! taxonomy_exists( $config['file_taxonomy'] ) ||
        ! taxonomy_exists( $config['product_taxonomy'] )
    ) {
        wp_send_json_error( array(
            'message' => 'The document library configuration is invalid.',
        ), 400 );
    }

    $file_ids = isset( $_POST['file_ids'] )
        ? array_map( 'absint', (array) wp_unslash( $_POST['file_ids'] ) )
        : array();

    $product_ids = isset( $_POST['product_ids'] )
        ? array_map( 'absint', (array) wp_unslash( $_POST['product_ids'] ) )
        : array();

    $file_ids    = array_values( array_unique( array_filter( $file_ids ) ) );
    $product_ids = array_values( array_unique( array_filter( $product_ids ) ) );

    // Only accept existing terms belonging to the expected taxonomy.
    foreach ( $file_ids as $term_id ) {
        if ( ! term_exists( $term_id, $config['file_taxonomy'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid File Type term.' ), 400 );
        }
    }

    foreach ( $product_ids as $term_id ) {
        if ( ! term_exists( $term_id, $config['product_taxonomy'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid Product Name term.' ), 400 );
        }
    }

    $sort = isset( $_POST['sort'] )
        ? sanitize_key( wp_unslash( $_POST['sort'] ) )
        : 'asc';

    if ( ! in_array( $sort, array( 'asc', 'desc' ), true ) ) {
        $sort = 'asc';
    }

    $tax_query = array( 'relation' => 'AND' );

    if ( $file_ids ) {
        $tax_query[] = array(
            'taxonomy'         => $config['file_taxonomy'],
            'field'            => 'term_id',
            'terms'            => $file_ids,
            'operator'         => 'IN',
            'include_children' => false,
        );
    }

    if ( $product_ids ) {
        $tax_query[] = array(
            'taxonomy'         => $config['product_taxonomy'],
            'field'            => 'term_id',
            'terms'            => $product_ids,
            'operator'         => 'IN',
            'include_children' => false,
        );
    }

    $args = array(
        'post_type'           => $config['post_type'],
        'post_status'         => 'publish',
        'posts_per_page'      => $config['posts_per_page'],
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => 'title',
        'order'               => 'ASC',
    );

    if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $args );
    $documents = array();

    foreach ( $query->posts as $document ) {
        $pdf_url = rakib_pdf_filter_get_file_url( $document->ID );

        // Only include documents with a usable PDF URL.
        if ( ! $pdf_url ) {
            continue;
        }

        $product_terms = get_the_terms(
            $document->ID,
            $config['product_taxonomy']
        );

        $product_names = array();

        if ( ! is_wp_error( $product_terms ) && $product_terms ) {
            foreach ( $product_terms as $term ) {
                $product_names[] = $term->name;
            }
        }

        // If multiple product terms exist, sort by the alphabetically
        // first assigned Product Name.
        natcasesort( $product_names );
        $sort_name = $product_names ? reset( $product_names ) : '';

        $documents[] = array(
            'title'       => get_the_title( $document ),
            'url'         => $pdf_url,
            'product'     => $sort_name,
            'product_ids' => $product_ids,
            'id'          => $document->ID,
        );
    }

    // Sort by Product Name, not by PDF post title.
    usort( $documents, function( $a, $b ) use ( $sort ) {
        $comparison = strnatcasecmp( $a['product'], $b['product'] );

        if ( 0 === $comparison ) {
            $comparison = strnatcasecmp( $a['title'], $b['title'] );
        }

        return 'desc' === $sort ? -$comparison : $comparison;
    } );

    ob_start();

    if ( $documents ) {
        echo '<div class="pdf-document-grid">';

        foreach ( $documents as $document ) {
            echo '<article class="pdf-document-card">';

            echo '<h3 class="pdf-document-title">' .
                esc_html( $document['title'] ) . '</h3>';

            echo '<a class="pdf-document-download" href="' .
                esc_url( $document['url'] ) .
                '" target="_blank" rel="noopener" download>';

            echo '<span>Download PDF</span>';
            echo '</a>';

            echo '</article>';
        }

        echo '</div>';
    } else {
        echo '<p class="pdf-filter-no-results">No matching PDF documents found.</p>';
    }

    $html = ob_get_clean();

    wp_send_json_success( array(
        'html'  => $html,
        'count' => count( $documents ),
    ) );
}
