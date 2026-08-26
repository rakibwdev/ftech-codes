<?php
function course_lessons_shortcode() {

    if (!function_exists('get_field')) {
        return '<p>ACF is not available.</p>';
    }

    $modules = get_field('module');

    if (empty($modules) || !is_array($modules)) {
        return '<p>No course modules found.</p>';
    }

    /*
     * Prepare course data for JavaScript
     */
    $course_data = [];

    foreach ($modules as $module_index => $module) {

        $lessons = [];

        if (
            !empty($module['lesson']) &&
            is_array($module['lesson'])
        ) {

            foreach ($module['lesson'] as $lesson_index => $lesson) {

                $lessons[] = [
                    'title'       => $lesson['lesson_title'] ?? '',
                    'description' => $lesson['lesson_description'] ?? '',
                    'video'       => $lesson['youtube_link'] ?? '',
                    'type'        => $lesson['video_type'] ?? '',
                    'duration'    => $lesson['lesson_duration'] ?? '',
                    'preview'     => $lesson['lesson_preview'] ?? '',
                ];
            }
        }

        $course_data[] = [
            'title'       => $module['module_title'] ?? '',
            'image'       => $module['module_image'] ?? '',
            'description' => $module['module_description'] ?? '',
            'duration'    => $module['module_duration'] ?? '',
            'lessons'     => $lessons,
        ];
    }

    ob_start();
    ?>

    <div class="course-learning-wrapper">

        <!-- =========================
             MAIN CONTENT
        ========================== -->

        <div class="course-main-content">

            <div class="course-video-wrapper">

                <div class="course-video">

                    <div class="course-video-placeholder">
                        Select a lesson
                    </div>

                </div>

            </div>


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


        <!-- =========================
             SIDEBAR
        ========================== -->

        <aside class="course-sidebar">

            <div class="course-modules">

                <?php foreach (
                    $course_data
                    as $module_index => $module
                ) : ?>

                    <div
                        class="course-module"
                        style="background-image: url('<?php echo esc_url($module['image'] ?? ''); ?>')";
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

                                    <?php
                                    if (
                                        !empty(
                                            $module['duration']
                                        )
                                    ) :
                                    ?>

                                        •
                                        <?php
                                        echo esc_html(
                                            $module['duration']
                                        );
                                        ?>

                                    <?php endif; ?>

                                </span>

                            </div>


                            <div class="module-arrow">
                                +
                            </div>

                        </div>


                        <!-- LESSONS -->

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

                                    <div class="lesson-icon">

                                        <?php

                                        if (
                                            strtolower(
                                                trim(
                                                    $lesson['preview']
                                                )
                                            ) === 'yes'
                                        ) {

                                            echo '▶';

                                        } else {

                                            echo '🔓';

                                        }

                                        ?>

                                    </div>


                                    <div class="lesson-name">

                                        <?php
                                        echo esc_html(
                                            $lesson['title']
                                        );
                                        ?>

                                    </div>


                                    <?php
                                    if (
                                        !empty(
                                            $lesson['duration']
                                        )
                                    ) :
                                    ?>

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


    <script>

    jQuery(window).on(
        'elementor/frontend/init',
        function () {

            elementorFrontend.hooks.addAction(
                'frontend/element_ready/shortcode.default',
                function ($scope) {

                    /*
                     * Find THIS shortcode only
                     */

                    const wrapper =
                        $scope.find(
                            '.course-learning-wrapper'
                        )[0];


                    if (!wrapper) {
                        return;
                    }


                    /*
                     * Prevent duplicate initialization
                     */

                    if (
                        wrapper.dataset.courseInitialized === 'true'
                    ) {
                        return;
                    }


                    wrapper.dataset.courseInitialized = 'true';


                    /*
                     * COURSE DATA
                     */

                    const courseData =
                        <?php echo wp_json_encode($course_data); ?>;


                    /*
                     * ELEMENTS
                     */

                    const modules =
                        wrapper.querySelectorAll(
                            '.course-module'
                        );


                    const lessons =
                        wrapper.querySelectorAll(
                            '.course-lesson'
                        );


                    const videoContainer =
                        wrapper.querySelector(
                            '.course-video'
                        );


                    const lessonTitle =
                        wrapper.querySelector(
                            '.current-lesson-title'
                        );


                    const lessonDescription =
                        wrapper.querySelector(
                            '.current-lesson-description'
                        );


                    const moduleName =
                        wrapper.querySelector(
                            '.current-module-name'
                        );


                    const lessonNumber =
                        wrapper.querySelector(
                            '.current-lesson-number'
                        );


                    /*
                     * CHECK REQUIRED ELEMENTS
                     */

                    if (
                        !videoContainer ||
                        !lessonTitle ||
                        !lessonDescription ||
                        !moduleName ||
                        !lessonNumber
                    ) {

                        console.error(
                            'Course: Required elements are missing.'
                        );

                        return;
                    }


                    /*
                     * =========================
                     * MODULE CLICK
                     * =========================
                     */

                    modules.forEach(
                        function (module) {

                            const header =
                                module.querySelector(
                                    '.course-module-header'
                                );


                            /*
                             * IMPORTANT
                             * Prevent null.addEventListener
                             */

                            if (!header) {

                                console.warn(
                                    'Course: Module header not found.',
                                    module
                                );

                                return;

                            }


                            header.addEventListener(
                                'click',
                                function () {

                                    modules.forEach(
                                        function (item) {

                                            if (
                                                item !== module
                                            ) {

                                                item.classList.remove(
                                                    'active'
                                                );

                                            }

                                        }
                                    );


                                    module.classList.toggle(
                                        'active'
                                    );

                                }
                            );

                        }
                    );


                    /*
                     * =========================
                     * LESSON CLICK
                     * =========================
                     */

                    lessons.forEach(
                        function (lesson) {

                            lesson.addEventListener(
                                'click',
                                function (event) {

                                    event.stopPropagation();


                                    /*
                                     * Get indexes
                                     */

                                    const moduleIndex =
                                        parseInt(
                                            lesson.dataset.module,
                                            10
                                        );


                                    const lessonIndex =
                                        parseInt(
                                            lesson.dataset.lesson,
                                            10
                                        );


                                    /*
                                     * Validate data
                                     */

                                    if (
                                        !courseData[
                                            moduleIndex
                                        ]
                                    ) {

                                        console.error(
                                            'Course module not found:',
                                            moduleIndex
                                        );

                                        return;

                                    }


                                    if (
                                        !courseData[
                                            moduleIndex
                                        ].lessons[
                                            lessonIndex
                                        ]
                                    ) {

                                        console.error(
                                            'Course lesson not found:',
                                            moduleIndex,
                                            lessonIndex
                                        );

                                        return;

                                    }


                                    /*
                                     * Selected module
                                     */

                                    const selectedModule =
                                        courseData[
                                            moduleIndex
                                        ];


                                    /*
                                     * Selected lesson
                                     */

                                    const selectedLesson =
                                        selectedModule.lessons[
                                            lessonIndex
                                        ];


                                    /*
                                     * =========================
                                     * OPEN MODULE
                                     * =========================
                                     */

                                    modules.forEach(
                                        function (item) {

                                            item.classList.remove(
                                                'active'
                                            );

                                        }
                                    );


                                    const currentModule =
                                        wrapper.querySelector(
                                            '.course-module[data-module="' +
                                            moduleIndex +
                                            '"]'
                                        );


                                    if (currentModule) {

                                        currentModule.classList.add(
                                            'active'
                                        );

                                    }


                                    /*
                                     * =========================
                                     * ACTIVE LESSON
                                     * =========================
                                     */

                                    lessons.forEach(
                                        function (item) {

                                            item.classList.remove(
                                                'active'
                                            );

                                        }
                                    );


                                    lesson.classList.add(
                                        'active'
                                    );


                                    /*
                                     * =========================
                                     * LESSON INFORMATION
                                     * =========================
                                     */

                                    moduleName.textContent =
                                        selectedModule.title || '';


                                    lessonNumber.textContent =
                                        'Lesson ' +
                                        (lessonIndex + 1);


                                    lessonTitle.textContent =
                                        selectedLesson.title || '';


                                    lessonDescription.textContent =
                                        selectedLesson.description || '';


                                    /*
                                     * =========================
                                     * VIDEO
                                     * =========================
                                     */

                                    let videoUrl =
                                        String(
                                            selectedLesson.video || ''
                                        ).trim();


                                    /*
                                     * No video
                                     */

                                    if (!videoUrl) {

                                        videoContainer.innerHTML =
                                            '<div class="course-video-placeholder">' +
                                            'Video unavailable' +
                                            '</div>';

                                        return;

                                    }


                                    /*
                                     * YouTube WATCH URL
                                     *
                                     * https://www.youtube.com/watch?v=ABC123
                                     */

                                    if (
                                        videoUrl.includes(
                                            'watch?v='
                                        )
                                    ) {

                                        const videoId =
                                            videoUrl
                                                .split(
                                                    'watch?v='
                                                )[1]
                                                .split('&')[0];


                                        videoUrl =
                                            'https://www.youtube.com/embed/' +
                                            videoId;

                                    }


                                    /*
                                     * YouTube SHORT URL
                                     *
                                     * https://youtu.be/ABC123
                                     */

                                    else if (
                                        videoUrl.includes(
                                            'youtu.be/'
                                        )
                                    ) {

                                        const videoId =
                                            videoUrl
                                                .split(
                                                    'youtu.be/'
                                                )[1]
                                                .split('?')[0];


                                        videoUrl =
                                            'https://www.youtube.com/embed/' +
                                            videoId;

                                    }


                                    /*
                                     * Already embed URL
                                     */

                                    else if (
                                        videoUrl.includes(
                                            'youtube.com/embed/'
                                        )
                                    ) {

                                        // Nothing to change

                                    }


                                    /*
                                     * =========================
                                     * CREATE IFRAME
                                     * =========================
                                     */

                                    videoContainer.innerHTML =
                                        '';


                                    const iframe =
                                        document.createElement(
                                            'iframe'
                                        );


                                    iframe.src =
                                        videoUrl;


                                    iframe.title =
                                        selectedLesson.title ||
                                        'Course video';


                                    iframe.setAttribute(
                                        'frameborder',
                                        '0'
                                    );


                                    iframe.setAttribute(
                                        'allow',
                                        'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
                                    );


                                    iframe.setAttribute(
                                        'allowfullscreen',
                                        ''
                                    );


                                    videoContainer.appendChild(
                                        iframe
                                    );

                                }
                            );

                        }
                    );


                    /*
                     * =========================
                     * OPEN FIRST MODULE
                     * =========================
                     */

                    if (modules.length > 0) {

                        modules[0].classList.add(
                            'active'
                        );

                    }

                }
            );

        }
    );

    </script>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'course_lessons',
    'course_lessons_shortcode'
);