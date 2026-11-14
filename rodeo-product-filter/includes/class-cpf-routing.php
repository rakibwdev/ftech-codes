<?php
defined( 'ABSPATH' ) || exit;

/**
 * Path URLs: /shop/brand/rolex/size/40mm/  and  /product-category/watches/brand/rolex/
 * plus 301 redirects from the old query-string URLs.
 */
class CPF_Routing {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rules' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_flush' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_legacy' ) );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'cpf_path';
		return $vars;
	}

	/** Flush once per plugin version, in wp-admin only (never on front-end requests). */
	public static function maybe_flush() {
		if ( get_option( 'cpf_rewrite_version' ) !== CPF_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'cpf_rewrite_version', CPF_VERSION );
		}
	}

	public static function register_rules() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}

		$shop_id = wc_get_page_id( 'shop' );

		if ( $shop_id < 1 ) {
			return;
		}

		$shop_uri = trim( get_page_uri( $shop_id ), '/' );

		$names = implode( '|', array_map(
			function ( $name ) {
				return preg_quote( $name, '/' );
			},
			array_keys( CPF_Config::path_map() )
		) );

		$segments = '((?:(?:' . $names . ')/[^/]+/?)+)$';

		add_rewrite_rule(
			'^' . preg_quote( $shop_uri, '/' ) . '/' . $segments,
			'index.php?pagename=' . $shop_uri . '&cpf_path=$matches[1]',
			'top'
		);

		$permalinks = function_exists( 'wc_get_permalink_structure' ) ? wc_get_permalink_structure() : array();
		$cat_base   = trim( $permalinks['category_rewrite_slug'] ?? 'product-category', '/' );

		add_rewrite_rule(
			'^' . preg_quote( $cat_base, '/' ) . '/(.+?)/' . $segments,
			'index.php?product_cat=$matches[1]&cpf_path=$matches[2]',
			'top'
		);
	}

	/** "brand/rolex/size/40mm" => array( 'pa_brand' => ['rolex'], 'pa_size' => ['40mm'] ) */
	public static function parse_path( $path ) {
		$map   = CPF_Config::path_map();
		$out   = array();
		$parts = array_values( array_filter( explode( '/', trim( (string) $path, '/' ) ), 'strlen' ) );

		for ( $i = 0; $i + 1 < count( $parts ); $i += 2 ) {
			$name = sanitize_title( $parts[ $i ] );

			if ( ! isset( $map[ $name ] ) ) {
				continue;
			}

			$values = array_filter( array_map( 'sanitize_title', explode( ',', $parts[ $i + 1 ] ) ) );

			if ( $values ) {
				$taxonomy         = $map[ $name ];
				$out[ $taxonomy ] = array_values( array_unique( array_merge( $out[ $taxonomy ] ?? array(), $values ) ) );
			}
		}

		return $out;
	}

	/** array( 'pa_brand' => ['rolex'] ) => "brand/rolex/" */
	public static function build_path( $filters ) {
		$path = '';

		foreach ( CPF_Config::path_map() as $name => $taxonomy ) {
			if ( ! empty( $filters[ $taxonomy ] ) ) {
				$path .= $name . '/' . implode( ',', array_map( 'rawurlencode', $filters[ $taxonomy ] ) ) . '/';
			}
		}

		return $path;
	}

	public static function request_filters() {
		return self::parse_path( get_query_var( 'cpf_path' ) );
	}

	/** Merge values into a filters array (deduplicated, sanitized). */
	public static function merge_values( array $filters, $taxonomy, $values ) {
		$values = array_filter( array_map( 'sanitize_title', (array) $values ) );

		if ( $values ) {
			$filters[ $taxonomy ] = array_values( array_unique( array_merge( $filters[ $taxonomy ] ?? array(), $values ) ) );
		}

		return $filters;
	}

	/** 301 old query-string URLs to the new path URLs */
	public static function redirect_legacy() {
		$is_shop = function_exists( 'is_shop' ) && is_shop();
		$is_cat  = function_exists( 'is_product_category' ) && is_product_category();

		if ( ! $is_shop && ! $is_cat ) {
			return;
		}

		$args     = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
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

		$filters = array();

		foreach ( CPF_Config::legacy_keys() as $key => $taxonomy ) {
			if ( ! isset( $args[ $key ] ) || ! is_string( $args[ $key ] ) ) {
				continue;
			}

			$filters = self::merge_values( $filters, $taxonomy, explode( ',', sanitize_text_field( $args[ $key ] ) ) );
			unset( $args[ $key ] );
		}

		if ( ! $filters && ! $redirect ) {
			return;
		}

		unset( $args['wpf_fbv'] );

		foreach ( self::request_filters() as $taxonomy => $values ) {
			$filters[ $taxonomy ] = array_values( array_unique( array_merge( $values, $filters[ $taxonomy ] ?? array() ) ) );
		}

		$url = trailingslashit( $base ) . self::build_path( $filters );

		wp_safe_redirect( add_query_arg( urlencode_deep( $args ), $url ), 301 );
		exit;
	}
}
