<?php

if (!defined('ABSPATH')) exit;

require_once MSB_PATH . 'includes/class-admin.php';
require_once MSB_PATH . 'includes/class-fetcher.php';
require_once MSB_PATH . 'includes/class-shortcode.php';

class MSB_Plugin {

    public function __construct() {
        new MSB_Admin();
        new MSB_Shortcode();
    }
}

add_action('wp_enqueue_scripts', function() {

    wp_enqueue_script(
        'msb-script',
        MSB_URL . 'assets/script.js',
        [],
        '1.0',
        true
    );

    wp_localize_script('msb-script', 'msb_ajax', [
        'url' => admin_url('admin-ajax.php')
    ]);
});