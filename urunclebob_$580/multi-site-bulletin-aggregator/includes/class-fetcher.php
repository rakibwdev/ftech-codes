<?php

if (!defined('ABSPATH')) exit;

class MSB_Fetcher {

    public static function get_posts() {

        $sites = explode("\n", get_option('mb_api_urls'));
        $all_posts = [];

        foreach ($sites as $site) {

            $site = trim($site);
            if (!$site) continue;

            $site = rtrim($site, '/');

            $endpoint = $site . '/wp-json/wp/v2/posts?per_page=5&_embed';

            $response = wp_remote_get($endpoint);

            if (is_wp_error($response)) continue;

            $body = json_decode(wp_remote_retrieve_body($response));

            if (!is_array($body)) continue;

            foreach ($body as $post) {
                $all_posts[] = $post;
            }
        }

        usort($all_posts, function($a, $b) {
            return strtotime($b->date) - strtotime($a->date);
        });

        return $all_posts;
    }

}
  // latest news
    add_action('wp_ajax_msb_get_posts', 'msb_get_posts_callback');
add_action('wp_ajax_nopriv_msb_get_posts', 'msb_get_posts_callback');

function msb_get_posts_callback() {

    $posts = MSB_Fetcher::get_posts();
    $data = [];

    foreach (array_slice($posts, 0, 5) as $post) {
         $category = 'News';

        if (!empty($post->_embedded->{'wp:term'}[0][0]->name)) {
            $category = $post->_embedded->{'wp:term'}[0][0]->name;
        }

        $data[] = [
            'title' => wp_strip_all_tags($post->title->rendered),
            'link'  => $post->link,
            'time'  => $post->date,
            'category'=> $category
        ];
    }

    wp_send_json($data);
}

// mlb rumors
add_action('wp_ajax_msb_get_mlb_rumors', 'msb_get_mlb_rumors');
add_action('wp_ajax_nopriv_msb_get_mlb_rumors', 'msb_get_mlb_rumors');

function msb_get_mlb_rumors() {

    $posts = MSB_Fetcher::get_posts();
    $data = [];

    foreach ($posts as $post) {

        $is_mlb = false;

        //  Filter by tag slug
        if (!empty($post->_embedded->{'wp:term'})) {
            foreach ($post->_embedded->{'wp:term'} as $tax) {
                foreach ($tax as $term) {
                    if ($term->taxonomy === 'post_tag' && $term->slug === 'mlb-rumors') {
                        $is_mlb = true;
                    }
                }
            }
        }

        if (!$is_mlb) continue;

        // Image
        $image = '';
        if (!empty($post->_embedded->{'wp:featuredmedia'}[0]->source_url)) {
            $image = $post->_embedded->{'wp:featuredmedia'}[0]->source_url;
        }

        //  Excerpt
        $excerpt = '';
        if (!empty($post->excerpt->rendered)) {
            $excerpt = wp_strip_all_tags($post->excerpt->rendered);
        }

        $data[] = [
            'title'   => wp_strip_all_tags($post->title->rendered),
            'link'    => $post->link,
            'image'   => $image,
            'excerpt' => $excerpt
        ];
    }

    // limit 5
    $data = array_slice($data, 0, 5);

    wp_send_json($data);
}