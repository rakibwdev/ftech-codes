<?php
/**
 * Plugin Name: Course Management
 * Description: ACF-based course modules, lessons and video player.
 * Version: 1.0.0
 * Author: Rakib_ForaziTech
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Enqueue CSS + JS
 */
function course_learning_enqueue_assets() {

    wp_enqueue_style(
        'course-learning',
        plugin_dir_url(__FILE__) . 'assets/course-learning.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'course-learning',
        plugin_dir_url(__FILE__) . 'assets/course-learning.js',
        array('jquery'),
        '1.0.0',
        true
    );
}

add_action(
    'wp_enqueue_scripts',
    'course_learning_enqueue_assets'
);


/**
 * Course Learning Shortcode
 *
 * Usage:
 * [course_lessons]
 */
function course_lessons_shortcode() {

    if (!function_exists('get_field')) {
        return '<p>ACF is not available.</p>';
    }


    /*
     * Get ACF Modules
     */
    $modules = get_field('module');


    if (empty($modules) || !is_array($modules)) {
        return '<p>No course modules found.</p>';
    }


    /*
     * Prepare Course Data
     */
    $course_data = array();


    foreach ($modules as $module_index => $module) {

        $lessons = array();


        /*
         * Get Lessons
         */
        if (
            !empty($module['lesson']) &&
            is_array($module['lesson'])
        ) {

            foreach (
                $module['lesson']
                as $lesson_index => $lesson
            ) {

                $lessons[] = array(

                    'title' => $lesson['lesson_title'] ?? '',

                    'description' =>
                        $lesson['lesson_description'] ?? '',

                    'duration' =>
                        $lesson['lesson_duration'] ?? '',

                    'preview' =>
                        $lesson['lesson_preview'] ?? '',

                    'type' =>
                        $lesson['video_type'] ?? '',

                    'youtube' =>
                        $lesson['youtube_link'] ?? '',

                    'upload' =>
                        $lesson['upload_video'] ?? '',

                    'external' =>
                        $lesson['external_link'] ?? '',

                );
            }
        }


        /*
         * Module Data
         */
        $course_data[] = array(

            'title' =>
                $module['module_title'] ?? '',

            'image' =>
                $module['module_image'] ?? '',

            'description' =>
                $module['module_description'] ?? '',

            'duration' =>
                $module['module_duration'] ?? '',

            'lessons' =>
                $lessons,

        );
    }


    /*
     * Start Output
     */
    ob_start();

    ?>

    <div class="course-learning-wrapper">


        <!-- =====================================
             MAIN CONTENT
        ====================================== -->

        <div class="course-main-content">


            <!-- VIDEO -->

            <div class="course-video-wrapper">

                <div class="course-video">

                    <div class="course-video-placeholder">
                        Select a lesson
                    </div>

                </div>

            </div>


            <!-- LESSON INFORMATION -->

            <div class="course-lesson-info">

                <div class="course-current-module">

                    <span class="current-module-name"></span>

                    <span>•</span>

                    <span class="current-lesson-number"></span>

                </div>


                <h1 class="current-lesson-title">
                    Select a lesson
                </h1>


                <p class="current-lesson-description"></p>

            </div>

        </div>



        <!-- =====================================
             SIDEBAR
        ====================================== -->

        <aside class="course-sidebar">

            <div class="course-modules">


                <?php foreach (
                    $course_data
                    as $module_index => $module
                ) : ?>


                    <div
                        class="course-module"
                        style="background-image: url('<?php echo esc_url($module['image']); ?>');"
                        data-module="<?php echo esc_attr($module_index); ?>"
                    >


                        <!-- MODULE HEADER -->

                        <div class="course-module-header">


                            <div class="module-number">

                                <?php
                                echo esc_html(
                                    $module_index + 1
                                );
                                ?>

                            </div>


                            <div class="module-info">

                                <h3>

                                    <?php
                                    echo esc_html(
                                        $module['title']
                                    );
                                    ?>

                                </h3>


                                <span>

                                    <?php
                                    echo count(
                                        $module['lessons']
                                    );
                                    ?>

                                    Lessons


                                    <?php if (
                                        !empty($module['duration'])
                                    ) : ?>

                                        <span class="module-separator">
                                            •
                                        </span>

                                        <?php
                                        echo esc_html(
                                            $module['duration']
                                        );
                                        ?>

                                    <?php endif; ?>

                                </span>

                            </div>


                            <div class="module-arrow">

                                <span class="module-arrow-icon">
                                    +
                                </span>

                            </div>


                        </div>



                        <!-- =====================================
                             LESSONS
                        ====================================== -->

                        <div class="course-lessons">


                            <?php foreach (
                                $module['lessons']
                                as $lesson_index => $lesson
                            ) : ?>


                                <div
                                    class="course-lesson"
                                    data-module="<?php echo esc_attr($module_index); ?>"
                                    data-lesson="<?php echo esc_attr($lesson_index); ?>"
                                >


                                    <!-- ICON -->

                                    <div class="lesson-icon">


                                        <?php

                                        if (
                                            strtolower(
                                                trim(
                                                    $lesson['preview']
                                                )
                                            ) === 'yes'
                                        ) :

                                        ?>

                                            <svg
                                                class="lesson-icon-play"
                                                width="18"
                                                height="18"
                                                viewBox="8 3 14.2175 17.6364"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                                aria-hidden="true"
                                            >

                                                <path
                                                    d="M8 5.14V18.86C8 20.42 9.74 21.35 11.03 20.49L21.31 13.63C22.52 12.82 22.52 11.18 21.31 10.37L11.03 3.51C9.74 2.65 8 3.58 8 5.14Z"
                                                    fill="currentColor"
                                                />

                                            </svg>


                                        <?php else : ?>


                                            <svg
                                                class="lesson-icon-lock"
                                                width="18"
                                                height="18"
                                                viewBox="5 3 14 18"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                                aria-hidden="true"
                                            >

                                                <path
                                                    d="M7 10V8C7 5.24 9.24 3 12 3C14.76 3 17 5.24 17 8V10"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                />

                                                <rect
                                                    x="5"
                                                    y="10"
                                                    width="14"
                                                    height="11"
                                                    rx="2"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                />

                                                <circle
                                                    cx="12"
                                                    cy="15.5"
                                                    r="1.2"
                                                    fill="currentColor"
                                                />

                                            </svg>


                                        <?php endif; ?>


                                    </div>


                                    <!-- LESSON NAME -->

                                    <div class="lesson-name">

                                        <?php
                                        echo esc_html(
                                            $lesson['title']
                                        );
                                        ?>

                                    </div>


                                    <!-- LESSON DURATION -->

                                    <?php if (
                                        !empty($lesson['duration'])
                                    ) : ?>

                                        <div class="lesson-duration">

                                            <?php
                                            echo esc_html(
                                                $lesson['duration']
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>

        </aside>


    </div>


    <!-- Course data for JavaScript -->

    <script type="application/json" class="course-data">

        <?php
        echo wp_json_encode($course_data);
        ?>

    </script>


    <?php

    return ob_get_clean();
}


add_shortcode(
    'course_lessons',
    'course_lessons_shortcode'
);