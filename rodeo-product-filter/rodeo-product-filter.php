<?php
/**
 * Plugin Name:       Rodeo Vault Product Filter
 * Description:       AJAX + SEO-friendly path URL product filter for WooCommerce and Elementor Loop Grid. Shortcode: [custom_product_filter]
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       cpf
 */

defined( 'ABSPATH' ) || exit;

define( 'CPF_VERSION', '2.0.0' );
define( 'CPF_FILE', __FILE__ );
define( 'CPF_PATH', plugin_dir_path( __FILE__ ) );
define( 'CPF_URL', plugin_dir_url( __FILE__ ) );

foreach ( array( 'config', 'routing', 'cache', 'terms', 'query', 'render', 'ajax' ) as $cpf_file ) {
	require_once CPF_PATH . 'includes/class-cpf-' . $cpf_file . '.php';
}

add_action( 'plugins_loaded', function () {
	CPF_Routing::init();
	CPF_Cache::init();
	CPF_Query::init();
	CPF_Render::init();
	CPF_Ajax::init();
} );

register_activation_hook( __FILE__, function () {
	if ( function_exists( 'wc_get_page_id' ) ) {
		CPF_Routing::register_rules();
	}
	flush_rewrite_rules( false );
	update_option( 'cpf_rewrite_version', CPF_VERSION );
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules( false );
} );
