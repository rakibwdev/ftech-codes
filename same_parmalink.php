<?php
/**
 * Allow the same page slug for different Polylang languages.
 *
 * Example:
 * English: /about/
 * French:  /fr/about/
 */
add_filter('wp_unique_post_slug', function ($slug, $post_ID, $post_status, $post_type, $post_parent) {

    // Only pages
    if ($post_type !== 'page') {
        return $slug;
    }

    // Polylang must be available
    if (!function_exists('pll_get_post_language')) {
        return $slug;
    }

    // Get current page language
    $current_language = pll_get_post_language($post_ID);

    if (!$current_language) {
        return $slug;
    }

    // Find pages using the same slug
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'name'           => $slug,
        'posts_per_page' => -1,
        'post__not_in'   => [$post_ID],
        'fields'         => 'ids',
    ]);

    // If no conflict, keep the slug
    if (empty($pages)) {
        return $slug;
    }

    // Check whether the conflicting page belongs to another language
    foreach ($pages as $page_id) {

        $existing_language = pll_get_post_language($page_id);

        // Same slug is allowed when languages are different
        if ($existing_language && $existing_language !== $current_language) {
            return $slug;
        }
    }

    return $slug;

}, 10, 5);