<?php
add_shortcode('back_button_url', function() {
    if ( get_post_type() === 'lessons' )
    $lesson_id = get_the_ID();
    $section = get_posts([
        'post_type'      => 'section',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => 'lessons',
                'value'   => '"' . $lesson_id . '"',
                'compare' => 'LIKE',
            ],
        ],
    ]);
    if (empty($section)) return '';
    $section = $section[0];
    $section_id = $section->ID;

    $section_lessons = get_post_meta($section_id, 'lessons', true);
    var_export($section_lessons);
});

function get_current_section_data() {

    if ( get_post_type() !== 'lessons' ) {
        return false;
    }

    $lesson_id = get_the_ID();

    $sections = get_posts([
        'post_type'      => 'section',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => 'lessons',
                'value'   => '"' . $lesson_id . '"',
                'compare' => 'LIKE',
            ],
        ],
    ]);

    if ( empty( $sections ) ) {
        return false;
    }

    $section_id = $sections[0]->ID;

    $lessons = get_post_meta( $section_id, 'lessons', true );

    if ( ! is_array( $lessons ) ) {
        return false;
    }

    return [
        'lesson_id'  => $lesson_id,
        'section_id' => $section_id,
        'lessons'    => $lessons,
    ];
}

add_shortcode('section_url', function () {

    $data = get_current_section_data();

    if ( ! $data ) {
        return '';
    }

    return get_permalink( $data['section_id'] );
});
add_shortcode('previous_lesson_url', function () {

    $data = get_current_section_data();

    if ( ! $data ) {
        return '';
    }

    $index = array_search( $data['lesson_id'], $data['lessons'] );

    if ( $index === false || ! isset( $data['lessons'][ $index - 1 ] ) ) {
        return '';
    }

    return get_permalink( $data['lessons'][ $index - 1 ] );
});

add_shortcode('next_lesson_url', function () {

    $data = get_current_section_data();

    if ( ! $data ) {
        return '';
    }

    $index = array_search( $data['lesson_id'], $data['lessons'] );

    if ( $index === false || ! isset( $data['lessons'][ $index + 1 ] ) ) {
        return '';
    }

    return get_permalink( $data['lessons'][ $index + 1 ] );
});


add_shortcode('first_section_url', function () {

    if ( get_post_type() !== 'design-class' ) {
        return '';
    }

    $design_class_id = get_the_ID();

    $sections = get_post_meta( $design_class_id, 'sections', true );

    if ( ! is_array( $sections ) || empty( $sections ) ) {
        return '';
    }

    return get_permalink( $sections[0] );
});

add_shortcode('first_lesson_url', function () {

    if ( get_post_type() !== 'section' ) {
        return '';
    }

    $section_id = get_the_ID();

    $lessons = get_post_meta( $section_id, 'lessons', true );

    if ( ! is_array( $lessons ) || empty( $lessons ) ) {
        return '';
    }

    return get_permalink( $lessons[0] );
});