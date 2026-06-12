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