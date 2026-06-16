<?php
/**
 * Plugin Name: Multi Site Bulletin
 * Description: Fetch and display latest bulletins + breaking news from multiple WP sites.
 * Version: 1.0.0
 * Author: Forazi Tech
 */

if (!defined('ABSPATH')) exit;

define('MSB_PATH', plugin_dir_path(__FILE__));
define('MSB_URL', plugin_dir_url(__FILE__));

require_once MSB_PATH . 'includes/class-plugin.php';

function msb_run_plugin() {
    new MSB_Plugin();
}
msb_run_plugin();