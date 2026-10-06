<?php
/**
 * Plugin Name: Website AI Chatbot
 * Description: AI chatbot that answers questions using WordPress website content and Ollama.
 * Version: 1.0.0
 * Author: Rakib
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WAC_PLUGIN_DIR . 'includes/class-chatbot-indexer.php';
require_once WAC_PLUGIN_DIR . 'includes/class-chatbot-api.php';
require_once WAC_PLUGIN_DIR . 'includes/class-chatbot-admin.php';


/**
 * Create database table
 */
register_activation_hook( __FILE__, 'wac_activate_plugin' );

function wac_activate_plugin() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'website_ai_knowledge';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        title TEXT NOT NULL,
        url TEXT NOT NULL,
        content LONGTEXT NOT NULL,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( $sql );

    add_option(
        'wac_ollama_url',
        'http://127.0.0.1:11434'
    );

    add_option(
        'wac_model',
        'llama3.2:3b'
    );

    add_option(
        'wac_bot_name',
        'Website Assistant'
    );
}


/**
 * Load frontend assets
 */
add_action( 'wp_enqueue_scripts', 'wac_load_assets' );

function wac_load_assets() {

    wp_enqueue_style(
        'wac-chatbot',
        WAC_PLUGIN_URL . 'assets/chatbot.css',
        array(),
        '1.0'
    );

    wp_enqueue_script(
        'wac-chatbot',
        WAC_PLUGIN_URL . 'assets/chatbot.js',
        array(),
        '1.0',
        true
    );

    wp_localize_script(
        'wac-chatbot',
        'WAC',
        array(
            'apiUrl' => esc_url_raw(
                rest_url( 'website-ai/v1/chat' )
            ),
        )
    );
}


/**
 * Chatbot HTML
 */
add_action( 'wp_footer', 'wac_chatbot_html' );

function wac_chatbot_html() {

    ?>

    <div id="wac-chatbot">

        <button id="wac-open">
            💬
        </button>

        <div id="wac-window">

            <div class="wac-header">

                <strong>
                    <?php
                    echo esc_html(
                        get_option(
                            'wac_bot_name',
                            'Website Assistant'
                        )
                    );
                    ?>
                </strong>

                <button id="wac-close">
                    ×
                </button>

            </div>


            <div id="wac-messages">

                <div class="wac-message bot">
                    Hi! How can I help you?
                </div>

            </div>


            <form id="wac-form">

                <input
                    type="text"
                    id="wac-input"
                    placeholder="Ask something..."
                    autocomplete="off"
                >

                <button type="submit">
                    Send
                </button>

            </form>

        </div>

    </div>

    <?php
}