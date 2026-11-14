<?php
defined( 'ABSPATH' ) || exit;

/**
 * Central configuration. Everything is filterable so nothing needs hard-coding.
 */
class CPF_Config {

	/** URL segment => taxonomy */
	public static function path_map() {
		return apply_filters( 'cpf_path_attribute_map', array(
			'brand'        => 'pa_brand',
			'size'         => 'pa_size',
			'watch-style'  => 'pa_watch_style',
			'material'     => 'pa_material',
			'jewelry-type' => 'pa_jewelry_type',
			'stone-type'   => 'pa_stone_type',
			'collection'   => 'pa_collection',
			'color'        => 'pa_color',
		) );
	}

	/** Category slug => extra attribute filters shown for that category (and its children) */
	public static function extra_attributes() {
		return apply_filters( 'cpf_category_extra_attributes', array(
			'watches'  => array(
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
			'jewelry'  => array(
				'pa_material'     => 'Material',
				'pa_jewelry_type' => 'Jewelry Type',
				'pa_stone_type'   => 'Stone Type',
			),
			'handbags' => array(
				'pa_collection' => 'Collection',
				'pa_color'      => 'Color',
			),
		) );
	}

	/** Attribute taxonomy => category slug (used to auto-select a category from the URL) */
	public static function attribute_category_hints() {
		return apply_filters( 'cpf_attribute_category_hints', array(
			'pa_watch_style'  => 'watches',
			'pa_material'     => 'watches',
			'pa_jewelry_type' => 'jewelry',
			'pa_stone_type'   => 'jewelry',
			'pa_collection'   => 'handbags',
			'pa_color'        => 'handbags',
		) );
	}

	/** Whitelist of every taxonomy the filter may query */
	public static function taxonomies() {
		$all = array_merge( array( 'pa_brand', 'pa_size' ), array_values( self::path_map() ) );

		foreach ( self::extra_attributes() as $set ) {
			$all = array_merge( $all, array_keys( $set ) );
		}

		return array_values( array_unique( $all ) );
	}

	/** Old query-string key => taxonomy (for 301 redirects) */
	public static function legacy_keys() {
		$legacy = array(
			'wpf_filter_brand' => 'pa_brand',
			'jewelry_type'     => 'pa_jewelry_type',
		);

		foreach ( self::path_map() as $taxonomy ) {
			$legacy[ $taxonomy ] = $taxonomy;
		}

		return $legacy;
	}

	public static function per_page() {
		return (int) apply_filters( 'cpf_products_per_page', 12 );
	}

	/** Elementor Loop Item template ID. Override: define('CPF_LOOP_TEMPLATE_ID', 123); or the filter. */
	public static function template_id() {
		$id = defined( 'CPF_LOOP_TEMPLATE_ID' ) ? CPF_LOOP_TEMPLATE_ID : 326;
		return absint( apply_filters( 'cpf_loop_template_id', $id ) );
	}

	/** Off-canvas panels that should close after "View Results" */
	public static function offcanvas_selectors() {
		return apply_filters( 'cpf_offcanvas_selectors', array( '#off-canvas-c27cf1e', '#off-canvas-798f353' ) );
	}

	/** Extra close button(s) that should apply the filter */
	public static function close_selector() {
		return apply_filters( 'cpf_close_selector', '.folter_close' );
	}

	public static function loop_selector() {
		return apply_filters( 'cpf_loop_selector', '#product_loop' );
	}
}
