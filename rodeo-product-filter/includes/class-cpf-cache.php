<?php
defined( 'ABSPATH' ) || exit;

/** Transient cache with a single version number; any product/term change invalidates everything. */
class CPF_Cache {

	public static function init() {
		foreach ( array(
			'save_post_product',
			'woocommerce_update_product',
			'woocommerce_new_product',
			'deleted_post',
			'created_term',
			'edited_term',
			'delete_term',
		) as $hook ) {
			add_action( $hook, array( __CLASS__, 'bump' ) );
		}
	}

	public static function version() {
		$v = get_option( 'cpf_cache_v' );

		if ( ! $v ) {
			$v = time();
			update_option( 'cpf_cache_v', $v, false );
		}

		return $v;
	}

	public static function key( $type, $data ) {
		return 'cpf_' . $type . '_' . md5( self::version() . wp_json_encode( $data ) );
	}

	public static function bump() {
		update_option( 'cpf_cache_v', time(), false );
	}
}
