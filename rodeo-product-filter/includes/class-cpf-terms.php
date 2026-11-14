<?php
defined( 'ABSPATH' ) || exit;

/** Attribute terms that are actually used by products in a category. */
class CPF_Terms {

	public static function get( $taxonomy, $category_id = 0 ) {
		$key    = CPF_Cache::key( 'terms', array( $taxonomy, (int) $category_id ) );
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$terms = self::query( $taxonomy, (int) $category_id );
		set_transient( $key, $terms, 12 * HOUR_IN_SECONDS );

		return $terms;
	}

	private static function query( $taxonomy, $category_id ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( $category_id ) {
			$ids = self::used_term_ids( $taxonomy, $category_id );

			if ( ! $ids ) {
				return array();
			}

			$args['include']    = $ids;
			$args['hide_empty'] = false;
		}

		$terms = get_terms( $args );

		return is_wp_error( $terms ) ? array() : array_values( $terms );
	}

	/** One SQL query instead of loading every product into PHP. */
	private static function used_term_ids( $taxonomy, $category_id ) {
		global $wpdb;

		$children = get_term_children( $category_id, 'product_cat' );
		$cat_ids  = array_map( 'absint', array_merge( array( $category_id ), is_wp_error( $children ) ? array() : $children ) );
		$in       = implode( ',', array_fill( 0, count( $cat_ids ), '%d' ) );

		$sql = "SELECT DISTINCT tt.term_id
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt
				ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
			INNER JOIN {$wpdb->term_relationships} trc
				ON trc.object_id = tr.object_id
			INNER JOIN {$wpdb->term_taxonomy} ttc
				ON ttc.term_taxonomy_id = trc.term_taxonomy_id
				AND ttc.taxonomy = 'product_cat' AND ttc.term_id IN ($in)
			INNER JOIN {$wpdb->posts} p
				ON p.ID = tr.object_id AND p.post_type = 'product' AND p.post_status = 'publish'";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, array_merge( array( $taxonomy ), $cat_ids ) ) );

		return array_map( 'absint', $ids );
	}
}
