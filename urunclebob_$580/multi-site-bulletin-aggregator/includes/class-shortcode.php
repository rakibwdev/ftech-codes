<?php

if (!defined('ABSPATH')) exit;

class MSB_Shortcode {

    public function __construct() {
        add_shortcode('msb_breaking', [$this, 'breaking']);
        add_shortcode('msb_bulletins', [$this, 'bulletins']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'msb-style',
            MSB_URL . 'assets/style.css',
            [],
            '1.0'
        );
    }

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

    // BREAKING NEWS SHORTCODE
    public function breaking() {

        $posts = MSB_Fetcher::get_posts();
        $breaking_slug = get_option('mb_breaking_tag');

        ob_start();
        ?>

        <div class="mb-breaking">
            <strong>BREAKING:</strong>
            <marquee scrollamount="7" >
                <?php foreach ($posts as $post): ?>

                    <?php
                    if (!empty($post->_embedded->{'wp:term'})) {
                        foreach ($post->_embedded->{'wp:term'} as $tax) {
                            foreach ($tax as $term) {
                                if ($term->slug === $breaking_slug) {
                                    echo '<a href="'.$post->link.'" target="_blank">'.$post->title->rendered.'</a> • ';
                                }
                            }
                        }
                    }
                    ?>

                <?php endforeach; ?>
            </marquee>
        </div>

        <?php
        return ob_get_clean();
    }

    // LATEST BULLETINS SHORTCODE
    public function bulletins() {

        $posts = MSB_Fetcher::get_posts();

        ob_start();
        ?>

        <div class="mb-bulletins">
            <h3>Latest Bulletins</h3>

            <?php foreach (array_slice($posts, 0, 5) as $post): ?>

                <div class="mb-card"
                     onclick="window.location.href='<?php echo esc_url($post->link); ?>'">

                    <div class="mb-top">
                        <span class="mb-tag">
                            <?php
                            echo !empty($post->_embedded->{'wp:term'}[0][0]->name)
                                ? esc_html($post->_embedded->{'wp:term'}[0][0]->name)
                                : 'News';
                            ?>
                        </span>

                        <span class="mb-time">
                            <?php echo esc_html($this->time_ago($post->date)); ?>
                        </span>
                    </div>

                    <h4><?php echo esc_html($post->title->rendered); ?></h4>

                </div>

            <?php endforeach; ?>

        </div>

        <?php
        return ob_get_clean();
    }
}