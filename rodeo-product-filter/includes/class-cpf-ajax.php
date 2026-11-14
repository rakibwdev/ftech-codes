<?php
defined( 'ABSPATH' ) || exit;

/**
 * AJAX endpoints. They are read-only and public, so no nonce is used:
 * a nonce printed into a cached page expires and then breaks the filter
 * (admin-ajax returns "-1", the front-end waits forever).
 */
class CPF_Ajax {

	public static function init() {
		foreach ( array( 'cpf_get_attributes', 'cpf_filter_products' ) as $action ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $action ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, $action ) );
		}
	}

	private static function no_cache() {
		nocache_headers();
	}

	public static function cpf_get_attributes() {
		self::no_cache();

		$category_id = absint( $_POST['category'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification

		wp_send_json_success( array(
			'brand' => CPF_Render::attribute( 'pa_brand', 'Brand', $category_id ),
			'size'  => CPF_Render::attribute( 'pa_size', 'Size', $category_id ),
			'extra' => CPF_Render::extra_attributes_html( $category_id ),
		) );
	}

	public static function cpf_filter_products() {
		self::no_cache();

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => 'WooCommerce is unavailable.' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$category_id = absint( $_POST['category'] ?? 0 );
		$min         = CPF_Query::clean_price( $_POST['min_price'] ?? '' );
		$max         = CPF_Query::clean_price( $_POST['max_price'] ?? '' );

		if ( '' !== $min && '' !== $max && (float) $min > (float) $max ) {
			wp_send_json_error( array( 'message' => 'Minimum price must not exceed maximum price.' ) );
		}

		$filters = array();

		foreach ( CPF_Config::taxonomies() as $taxonomy ) {
			if ( isset( $_POST[ $taxonomy ] ) && is_array( $_POST[ $taxonomy ] ) ) {
				$filters = CPF_Routing::merge_values( $filters, $taxonomy, wp_unslash( $_POST[ $taxonomy ] ) );
			}
		}
		// phpcs:enable

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => CPF_Config::per_page(),
			'paged'               => 1,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
		);

		$tax = CPF_Query::tax_clauses( $filters, $category_id );

		if ( $tax ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax );
		}

		$price = CPF_Query::price_clause( $min, $max );

		if ( $price ) {
			$args['meta_query'] = array( $price );
		}

		$template_id = CPF_Config::template_id();

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => 'Elementor Loop Item template ID is not configured.' ) );
		}

		$cache_key = CPF_Cache::key( 'products', array( $args, $template_id ) );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			wp_send_json_success( $cached );
		}

		$query = new WP_Query( $args );
		$html  = '';
		$first = true;

		foreach ( $query->posts as $product ) {
			$html .= '<div class="cpf-loop-item">' . self::render_card( $product, $template_id, true ) . '</div>';
			$first = false;
		}

		$html = self::dedupe_styles( $html );

		$result = array(
			'html'  => $html,
			'count' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
		);

		if ( '' !== $html ) {
			set_transient( $cache_key, $result, 6 * HOUR_IN_SECONDS );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Elementor prints the full template CSS (including @font-face) inside every card.
	 * Keep each distinct <style> block once; repeated @font-face blocks make iOS Safari
	 * re-parse and re-layout for every product.
	 */
	private static function dedupe_styles( $html ) {
		$seen = array();

		return preg_replace_callback(
			'#<style\b[^>]*>.*?</style>#is',
			function ( $m ) use ( &$seen ) {
				$key = md5( $m[0] );

				if ( isset( $seen[ $key ] ) ) {
					return '';
				}

				$seen[ $key ] = true;

				return $m[0];
			},
			$html
		);
	}

	/** Render one Elementor loop item. Elementor CSS is only printed with the first card. */
	private static function render_card( $product, $template_id, $with_css ) {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return '';
		}

		global $post;

		$previous = $post;
		$post     = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		setup_postdata( $post );

		$html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id, $with_css );

		$post = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		if ( $previous ) {
			setup_postdata( $previous );
		} else {
			wp_reset_postdata();
		}

		return $html;
	}
}
