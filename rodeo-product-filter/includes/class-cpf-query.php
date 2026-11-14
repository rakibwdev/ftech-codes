<?php
defined( 'ABSPATH' ) || exit;

/** Query builders shared by page load (server side) and AJAX. */
class CPF_Query {

	public static function init() {
		add_action( 'elementor/query/cpf_products', array( __CLASS__, 'elementor_query' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'main_query' ) );
	}

	public static function tax_clauses( array $filters, $category_id = 0 ) {
		$clauses = array();

		foreach ( $filters as $taxonomy => $values ) {
			if ( ! in_array( $taxonomy, CPF_Config::taxonomies(), true ) || ! taxonomy_exists( $taxonomy ) || ! $values ) {
				continue;
			}

			$clauses[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => array_values( $values ),
				'operator' => 'IN',
			);
		}

		if ( $category_id ) {
			$clauses[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => (int) $category_id,
				'include_children' => true,
			);
		}

		return $clauses;
	}

	public static function price_clause( $min, $max ) {
		if ( '' === $min && '' === $max ) {
			return array();
		}

		$clause = array( 'key' => '_price', 'type' => 'DECIMAL(20,6)' );

		if ( '' !== $min && '' !== $max ) {
			$clause['value']   = array( (float) $min, (float) $max );
			$clause['compare'] = 'BETWEEN';
		} elseif ( '' !== $min ) {
			$clause['value']   = (float) $min;
			$clause['compare'] = '>=';
		} else {
			$clause['value']   = (float) $max;
			$clause['compare'] = '<=';
		}

		return $clause;
	}

	public static function clean_price( $value ) {
		if ( null === $value || '' === $value || is_array( $value ) ) {
			return '';
		}

		return wc_format_decimal( wp_unslash( $value ) );
	}

	private static function merge_tax_query( $query, array $clauses ) {
		$merged = array_merge( array_filter( (array) $query->get( 'tax_query' ) ), $clauses );

		if ( count( $merged ) > 1 && ! isset( $merged['relation'] ) ) {
			$merged['relation'] = 'AND';
		}

		if ( $merged ) {
			$query->set( 'tax_query', $merged );
		}
	}

	/** Elementor Loop Grid with Query ID = cpf_products */
	public static function elementor_query( $query ) {
		if ( ! function_exists( 'wc_format_decimal' ) ) {
			return;
		}

		$category_id = 0;

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$term        = get_queried_object();
			$category_id = ( $term && ! is_wp_error( $term ) && ! empty( $term->term_id ) ) ? (int) $term->term_id : 0;
		}

		self::merge_tax_query( $query, self::tax_clauses( CPF_Routing::request_filters(), $category_id ) );

		// phpcs:disable WordPress.Security.NonceVerification
		$price = self::price_clause(
			self::clean_price( $_GET['min_price'] ?? '' ),
			self::clean_price( $_GET['max_price'] ?? '' )
		);
		// phpcs:enable

		if ( $price ) {
			$meta   = (array) $query->get( 'meta_query' );
			$meta[] = $price;
			$query->set( 'meta_query', $meta );
		}
	}

	/** Main query on product category archives */
	public static function main_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! get_query_var( 'cpf_path' ) ) {
			return;
		}

		if ( ! $query->is_tax( 'product_cat' ) ) {
			return;
		}

		self::merge_tax_query( $query, self::tax_clauses( CPF_Routing::request_filters() ) );
	}
}
