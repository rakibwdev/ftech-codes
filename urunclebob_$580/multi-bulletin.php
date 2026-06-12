<?php
/**
 * Plugin Name: Multi Site Bulletin Aggregator
 * Description: Fetch and display latest bulletins + breaking news from multiple WP sites.
 */

if (!defined('ABSPATH')) exit;

class Multi_Bulletin_Plugin {

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_shortcode('multi_bulletins', [$this, 'render_shortcode']);
    }

    // ✅ Admin Menu
    public function menu() {
        add_menu_page(
            'Bulletins',
            'Bulletins',
            'manage_options',
            'multi-bulletins',
            [$this, 'settings_page'],
            'dashicons-rss'
        );
    }

    // ✅ Register Settings
    public function settings() {
        register_setting('mb_settings', 'mb_api_urls');
        register_setting('mb_settings', 'mb_breaking_tag');
    }

    // ✅ Settings Page UI
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Bulletin Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('mb_settings'); ?>

                <h3>API URLs (one per line)</h3>
                <textarea name="mb_api_urls" rows="6" style="width:100%;">
<?php echo esc_textarea(get_option('mb_api_urls')); ?>
                </textarea>

                <h3>Breaking News Tag (slug)</h3>
                <input type="text" name="mb_breaking_tag"
                       value="<?php echo esc_attr(get_option('mb_breaking_tag')); ?>"
                       placeholder="breaking-news">

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // ✅ Fetch Posts
    private function fetch_posts() {
        $urls = explode("\n", get_option('mb_api_urls'));
        $all_posts = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if (!$url) continue;

            $response = wp_remote_get($url . '?per_page=5&_embed');

            if (is_wp_error($response)) continue;

            $body = json_decode(wp_remote_retrieve_body($response));

            if (!is_array($body)) continue;

            foreach ($body as $post) {
                $all_posts[] = $post;
            }
        }

        // sort by latest
        usort($all_posts, function($a, $b) {
            return strtotime($b->date) - strtotime($a->date);
        });

        return $all_posts;
    }

    // ✅ Time Ago
    private function time_ago($datetime) {
        $seconds = time() - strtotime($datetime);

        $units = [
            "year" => 31536000,
            "month" => 2592000,
            "day" => 86400,
            "hour" => 3600,
            "minute" => 60
        ];

        foreach ($units as $name => $value) {
            $val = floor($seconds / $value);
            if ($val >= 1) return strtoupper($val . substr($name, 0, 1) . " AGO");
        }

        return "JUST NOW";
    }

    // ✅ Shortcode Output
    public function render_shortcode() {
        $posts = $this->fetch_posts();
        $breaking_slug = get_option('mb_breaking_tag');

        ob_start();
        ?>

        <div class="mb-breaking">
            <strong>BREAKING:</strong>
            <marquee>
                <?php
                foreach ($posts as $post) {
                    if (!empty($post->_embedded->{'wp:term'})) {
                        foreach ($post->_embedded->{'wp:term'} as $tax) {
                            foreach ($tax as $term) {
                                if ($term->slug === $breaking_slug) {
                                    echo '<a href="'.$post->link.'" target="_blank">'.$post->title->rendered.'</a> • ';
                                }
                            }
                        }
                    }
                }
                ?>
            </marquee>
        </div>

        <div class="mb-bulletins">
            <h3>Latest Bulletins</h3>

            <?php foreach (array_slice($posts, 0, 5) as $post): ?>
                <div class="mb-card" onclick="window.location.href='<?php echo esc_url($post->link); ?>'">
                    
                    <div class="mb-top">
                        <span class="mb-tag">
                            <?php
                            if (!empty($post->_embedded->{'wp:term'}[0][0]->name)) {
                                echo esc_html($post->_embedded->{'wp:term'}[0][0]->name);
                            } else {
                                echo 'News';
                            }
                            ?>
                        </span>

                        <span class="mb-time">
                            <?php echo $this->time_ago($post->date); ?>
                        </span>
                    </div>

                    <h4><?php echo esc_html($post->title->rendered); ?></h4>
                </div>
            <?php endforeach; ?>

        </div>

        <style>
        .mb-card {
            border:1px solid #ddd;
            padding:12px;
            margin-bottom:10px;
            border-radius:8px;
            cursor:pointer;
        }
        .mb-card:hover { background:#f5f5f5; }
        .mb-top {
            display:flex;
            justify-content:space-between;
            margin-bottom:6px;
        }
        .mb-tag {
            border:1px solid #333;
            padding:2px 6px;
            font-size:12px;
        }
        .mb-breaking {
            background:#111;
            color:#fff;
            padding:8px;
            margin-bottom:15px;
        }
        .mb-breaking a {
            color:#fff;
            text-decoration:none;
            margin-right:10px;
        }
        </style>

        <?php
        return ob_get_clean();
    }
}

new Multi_Bulletin_Plugin();

<?php

class MSB_API_Fetcher {

    public static function get_posts() {

        $sites = explode("\n", get_option('mb_api_urls'));
        $all_posts = [];

        foreach ($sites as $site) {

            $site = trim($site);
            if (!$site) continue;

            // ✅ Remove trailing slash
            $site = rtrim($site, '/');

            // ✅ Build endpoint automatically
            $endpoint = $site . '/wp-json/wp/v2/posts?per_page=5&_embed';

            $response = wp_remote_get($endpoint);

            if (is_wp_error($response)) continue;

            $body = json_decode(wp_remote_retrieve_body($response));

            if (!is_array($body)) continue;

            foreach ($body as $post) {
                $all_posts[] = $post;
            }
        }

        // ✅ Sort latest first
        usort($all_posts, function($a, $b) {
            return strtotime($b->date) - strtotime($a->date);
        });

        return $all_posts;
    }
}