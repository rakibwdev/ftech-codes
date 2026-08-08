<?php

add_shortcode('guest_bios', function () {
    $guest_bios = get_field('guest_bios');
    $guest_bios = is_array($guest_bios) ? $guest_bios : [];

    if (empty($guest_bios)) return '';

    $output = '<div class="guest-bios-grid">';

    foreach ($guest_bios as $guest_bio) {
        $name = isset($guest_bio['name']) ? $guest_bio['name'] : '';
        $bio  = isset($guest_bio['bio']) ? $guest_bio['bio'] : '';
        $photo = isset($guest_bio['photo']) ? $guest_bio['photo'] : '';


        $output .= '<div class="guest-bio-card">';
        $output .= '<img src="' . esc_url($photo) . '" alt="' . esc_attr($name) . '">';
        $output .= '<div class="guest-bio-content">';
            $output .= '<h3>' . esc_html($name) . '</h3>';
            $output .= '<p>' . $bio . '</p>';
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
});