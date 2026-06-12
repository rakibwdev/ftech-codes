<?php

if (!defined('ABSPATH')) exit;

class MSB_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
    }

    public function menu() {
        add_menu_page(
            'Bulletins',
            'Bulletins',
            'manage_options',
            'msb-bulletins',
            [$this, 'page'],
            'dashicons-rss'
        );
    }

    public function settings() {
        register_setting('msb_settings', 'mb_api_urls');
        register_setting('msb_settings', 'mb_breaking_tag');
    }

    public function page() {
        ?>
        <div class="wrap">
            <h1>Bulletin Settings</h1>

            <form method="post" action="options.php">
                <?php settings_fields('msb_settings'); ?>

                <h3>API URLs (one per line)</h3>
                <textarea name="mb_api_urls" rows="6" style="width:100%;"><?php
                    echo esc_textarea(get_option('mb_api_urls'));
                ?></textarea>

                <h3>Breaking News Tag (slug)</h3>
                <input type="text" name="mb_breaking_tag"
                       value="<?php echo esc_attr(get_option('mb_breaking_tag')); ?>"
                       placeholder="breaking-news">

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}