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

        if (!empty($module['lesson']) && is_array($module['lesson'])) {

            foreach ($module['lesson'] as $lesson_index => $lesson) {

                $lessons[] = [
                    'title'    => $lesson['lesson_title'] ?? '',
                    'video'    => $lesson['youtube_link'] ?? '',
                    'type'     => $lesson['video_type'] ?? '',
                    'duration' => $lesson['lesson_duration'] ?? '',
                    'preview'  => $lesson['lesson_preview'] ?? '',
                ];
            }
        }

        $course_data[] = [
            'title'       => $module['module_title'] ?? '',
            'description' => $module['module_description'] ?? '',
            'duration'    => $module['module_duration'] ?? '',
            'lessons'     => $lessons,
        ];
    }

    ob_start();
    ?>

    <div class="course-learning-wrapper">

        <!-- Main Content -->
        <div class="course-main-content">

            <div class="course-video-wrapper">

                <div class="course-video" id="course-video">
                    <div class="course-video-placeholder">
                        Select a lesson
                    </div>
                </div>

            </div>

            <div class="course-lesson-info">

                <div class="course-current-module">
                    <span id="current-module-name"></span>
                    <span>•</span>
                    <span id="current-lesson-number"></span>
                </div>

                <h1 id="current-lesson-title">
                    Select a lesson
                </h1>

                <p id="current-lesson-description"></p>

            </div>

        </div>


        <!-- Sidebar -->
        <aside class="course-sidebar">

            <div class="course-sidebar-title">
                KURSÜBERSICHT
            </div>

            <div class="course-modules">

                <?php foreach ($course_data as $module_index => $module) : ?>

                    <div
                        class="course-module"
                        data-module="<?php echo esc_attr($module_index); ?>"
                    >

                        <div class="course-module-header">

                            <div class="module-number">
                                <?php echo esc_html($module_index + 1); ?>
                            </div>

                            <div class="module-info">

                                <h3>
                                    <?php echo esc_html($module['title']); ?>
                                </h3>

                                <span>
                                    <?php
                                    echo count($module['lessons']);
                                    ?>
                                    Lessons
                                    <?php if (!empty($module['duration'])) : ?>
                                        • <?php echo esc_html($module['duration']); ?>
                                    <?php endif; ?>
                                </span>

                            </div>

                            <div class="module-arrow">
                                +
                            </div>

                        </div>


                        <div class="course-lessons">

                            <?php foreach ($module['lessons'] as $lesson_index => $lesson) : ?>

                                <div
                                    class="course-lesson"
                                    data-module="<?php echo esc_attr($module_index); ?>"
                                    data-lesson="<?php echo esc_attr($lesson_index); ?>"
                                >

                                    <div class="lesson-icon">
                                        <?php
                                        if (
                                            strtolower($lesson['preview']) === 'yes'
                                        ) {
                                            echo '▶';
                                        } else {
                                            echo '🔒';
                                        }
                                        ?>
                                    </div>

                                    <div class="lesson-name">
                                        <?php echo esc_html($lesson['title']); ?>
                                    </div>

                                    <?php if (!empty($lesson['duration'])) : ?>

                                        <div class="lesson-duration">
                                            <?php echo esc_html($lesson['duration']); ?>
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
        jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
        'frontend/element_ready/shortcode.default',
        function ($scope) {

    document.addEventListener("DOMContentLoaded", function () {

        const courseData = <?php echo wp_json_encode($course_data); ?>;

        const modules = document.querySelectorAll(".course-module");
        const lessons = document.querySelectorAll(".course-lesson");

        const videoContainer =
            document.getElementById("course-video");

        const lessonTitle =
            document.getElementById("current-lesson-title");

        const lessonDescription =
            document.getElementById("current-lesson-description");

        const moduleName =
            document.getElementById("current-module-name");

        const lessonNumber =
            document.getElementById("current-lesson-number");


        /*
         * MODULE CLICK
         */
        modules.forEach(function (module) {

            const header =
                module.querySelector(".course-module-header");

            header.addEventListener("click", function () {

                modules.forEach(function (item) {

                    if (item !== module) {
                        item.classList.remove("active");
                    }

                });

                module.classList.toggle("active");

            });

        });


        /*
         * LESSON CLICK
         */
        lessons.forEach(function (lesson) {

            lesson.addEventListener("click", function (event) {

                event.stopPropagation();

                const moduleIndex =
                    parseInt(lesson.dataset.module);

                const lessonIndex =
                    parseInt(lesson.dataset.lesson);

                const selectedModule =
                    courseData[moduleIndex];

                const selectedLesson =
                    selectedModule.lessons[lessonIndex];


                /*
                 * Open current module
                 */
                modules.forEach(function (item) {
                    item.classList.remove("active");
                });

                const currentModule =
                    document.querySelector(
                        '.course-module[data-module="' +
                        moduleIndex +
                        '"]'
                    );

                if (currentModule) {
                    currentModule.classList.add("active");
                }


                /*
                 * Active lesson
                 */
                lessons.forEach(function (item) {
                    item.classList.remove("active");
                });

                lesson.classList.add("active");


                /*
                 * Lesson information
                 */
                moduleName.textContent =
                    selectedModule.title;

                lessonNumber.textContent =
                    "Lesson " + (lessonIndex + 1);

                lessonTitle.textContent =
                    selectedLesson.title;


                /*
                 * YouTube video
                 */
                if (
                    selectedLesson.type === "youtube" &&
                    selectedLesson.video
                ) {

                    let videoUrl =
                        selectedLesson.video;

                    /*
                     * Convert normal YouTube URL
                     * to embed URL
                     */
                    if (videoUrl.includes("watch?v=")) {

                        videoUrl =
                            videoUrl.replace(
                                "https://www.youtube.com/watch?v=",
                                "https://www.youtube.com/embed/"
                            );

                    }

                    if (videoUrl.includes("youtu.be/")) {

                        videoUrl =
                            videoUrl.replace(
                                "https://youtu.be/",
                                "https://www.youtube.com/embed/"
                            );

                    }

                    videoContainer.innerHTML = `
                        <iframe
                            src="${videoUrl}"
                            title="${selectedLesson.title}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen>
                        </iframe>
                    `;

                } else {

                    videoContainer.innerHTML =
                        "<div class='course-video-placeholder'>Video unavailable</div>";

                }

            });

        });


        /*
         * Open first module automatically
         */
        if (modules.length > 0) {
            modules[0].classList.add("active");
        }

    });

     }
    );

});
    </script>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'course_lessons',
    'course_lessons_shortcode'
);


// ============================
//  module and lesson
// ===============================
function course_modules_shortcode() {

    if (!function_exists('get_field')) {
        return '';
    }

    $modules = get_field('module');

    if (empty($modules)) {
        return '<p>No modules found.</p>';
    }

    ob_start();
    ?>

    <div class="course-modules">

        <?php foreach ($modules as $module_index => $module) : ?>

            <!-- <div class="course-module">

                <div class="module-header">

                    <h3 class="module-title">
                        <?php echo esc_html($module['module_title'] ?? ''); ?>
                    </h3>

                    <?php if (!empty($module['module_description'])) : ?>
                        <div class="module-description">
                            <?php echo esc_html($module['module_description']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($module['module_duration'])) : ?>
                        <span class="module-duration">
                            <?php echo esc_html($module['module_duration']); ?>
                        </span>
                    <?php endif; ?>

                </div> -->

                <div class="course-module"
     style="background-image: url('<?php echo esc_url($module['module_image'] ?? ''); ?>');">

    <a href="<?php echo esc_url($module['module_url'] ?? '#'); ?>" class="module-link">

        <div class="module-overlay">

            <div class="module-header">

                <h3 class="module-title">
                    <?php echo esc_html($module['module_title'] ?? ''); ?>
                </h3>

                <?php if (!empty($module['module_description'])) : ?>
                    <div class="module-description">
                        <?php echo esc_html($module['module_description']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($module['module_duration'])) : ?>
                    <span class="module-duration">
                        <?php echo esc_html($module['module_duration']); ?>
                    </span>
                <?php endif; ?>

            </div>

        </div>

    </a>

</div>


                <?php if (!empty($module['lesson'])) : ?>

                    <div class="course-lessons">

                        <?php foreach ($module['lesson'] as $lesson_index => $lesson) : ?>

                            <!-- <div class="course-lesson">

                                <span class="lesson-number">
                                    <?php echo esc_html($lesson_index + 1); ?>
                                </span>

                                <div class="lesson-content">

                                    <h4 class="lesson-title">
                                        <?php echo esc_html($lesson['lesson_title'] ?? ''); ?>
                                    </h4>

                                    <?php if (!empty($lesson['lesson_duration'])) : ?>
                                        <span class="lesson-duration">
                                            <?php echo esc_html($lesson['lesson_duration']); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>

                            </div> -->

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode('course_modules', 'course_modules_shortcode');