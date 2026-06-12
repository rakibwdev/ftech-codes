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