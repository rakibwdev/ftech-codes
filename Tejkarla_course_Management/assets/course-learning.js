(function ($) {

    'use strict';


    /*
     * ==========================================
     * INITIALIZE COURSE
     * ==========================================
     */

    function initCourse(wrapper) {

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
         * ==========================================
         * COURSE DATA
         * ==========================================
         */

        const dataElement =
            wrapper.parentElement
                ? wrapper.parentElement.querySelector('.course-data')
                : null;


        let courseData = [];


        if (dataElement) {

            try {

                courseData =
                    JSON.parse(
                        dataElement.textContent
                    );

            } catch (error) {

                console.error(
                    'Course: Invalid course data.',
                    error
                );

                return;
            }
        }


        /*
         * ==========================================
         * ELEMENTS
         * ==========================================
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
         * ==========================================
         * CHECK ELEMENTS
         * ==========================================
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
         * ==========================================
         * MODULE CLICK
         * ==========================================
         */

        modules.forEach(function (module) {

            const header =
                module.querySelector(
                    '.course-module-header'
                );


            if (!header) {
                return;
            }


            header.addEventListener(
                'click',
                function () {

                    const wasActive =
                        module.classList.contains(
                            'active'
                        );


                    /*
                     * Close all modules
                     */

                    modules.forEach(
                        function (item) {

                            item.classList.remove(
                                'active'
                            );

                        }
                    );


                    /*
                     * Open clicked module
                     */

                    if (!wasActive) {

                        module.classList.add(
                            'active'
                        );

                    }

                }
            );

        });


        /*
         * ==========================================
         * LESSON CLICK
         * ==========================================
         */

        lessons.forEach(function (lesson) {

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
                     * Validate module
                     */

                    if (
                        !courseData[moduleIndex]
                    ) {

                        console.error(
                            'Course module not found:',
                            moduleIndex
                        );

                        return;
                    }


                    /*
                     * Validate lesson
                     */

                    if (
                        !courseData[moduleIndex].lessons[
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
                        courseData[moduleIndex];


                    /*
                     * Selected lesson
                     */

                    const selectedLesson =
                        selectedModule.lessons[
                            lessonIndex
                        ];


                    /*
                     * ==================================
                     * OPEN MODULE
                     * ==================================
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
                     * ==================================
                     * ACTIVE LESSON
                     * ==================================
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
                     * ==================================
                     * LESSON INFORMATION
                     * ==================================
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
                     * ==================================
                     * VIDEO TYPE
                     * ==================================
                     */

                    const videoType =
                        String(
                            selectedLesson.type || ''
                        )
                        .trim()
                        .toLowerCase();


                    /*
                     * Clear previous video
                     */

                    videoContainer.innerHTML = '';


                    /*
                     * ==================================
                     * YOUTUBE
                     * ==================================
                     */

                    if (
                        videoType === 'youtube' &&
                        selectedLesson.youtube
                    ) {

                        const videoUrl =
                            String(
                                selectedLesson.youtube
                            ).trim();


                        let videoId = '';


                        /*
                         * Watch URL
                         */

                        if (
                            videoUrl.includes(
                                'watch?v='
                            )
                        ) {

                            videoId =
                                videoUrl
                                    .split('watch?v=')[1]
                                    .split('&')[0];

                        }


                        /*
                         * Short URL
                         */

                        else if (
                            videoUrl.includes(
                                'youtu.be/'
                            )
                        ) {

                            videoId =
                                videoUrl
                                    .split('youtu.be/')[1]
                                    .split('?')[0];

                        }


                        /*
                         * Embed URL
                         */

                        else if (
                            videoUrl.includes(
                                'youtube.com/embed/'
                            )
                        ) {

                            videoId =
                                videoUrl
                                    .split(
                                        'youtube.com/embed/'
                                    )[1]
                                    .split('?')[0];

                        }


                        /*
                         * Invalid URL
                         */

                        if (!videoId) {

                            videoContainer.innerHTML =
                                '<div class="course-video-placeholder">' +
                                'Invalid YouTube URL' +
                                '</div>';

                            return;
                        }


                        /*
                         * Create iframe
                         */

                        const iframe =
                            document.createElement(
                                'iframe'
                            );


                        iframe.src =
                            'https://www.youtube.com/embed/' +
                            encodeURIComponent(
                                videoId
                            );


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


                    /*
                     * ==================================
                     * UPLOADED VIDEO
                     * ==================================
                     */

                    else if (
                        videoType === 'upload' &&
                        selectedLesson.upload
                    ) {

                        const videoUrl =
                            String(
                                selectedLesson.upload
                            ).trim();


                        const video =
                            document.createElement(
                                'video'
                            );


                        video.className =
                            'course-upload-video';


                        video.controls = true;

                        video.playsInline = true;

                        video.preload = 'metadata';


                        const source =
                            document.createElement(
                                'source'
                            );


                        source.src =
                            videoUrl;


                        source.type =
                            'video/mp4';


                        video.appendChild(
                            source
                        );


                        videoContainer.appendChild(
                            video
                        );

                    }


                    /*
                     * ==================================
                     * EXTERNAL LINK
                     * ==================================
                     */

                    else if (
                        videoType === 'external_link' &&
                        selectedLesson.external
                    ) {

                        const externalUrl =
                            String(
                                selectedLesson.external
                            ).trim();


                        const externalWrapper =
                            document.createElement(
                                'div'
                            );


                        externalWrapper.className =
                            'course-external-video';


                        const text =
                            document.createElement(
                                'p'
                            );


                        text.textContent =
                            'This lesson is available on an external website.';


                        const link =
                            document.createElement(
                                'a'
                            );


                        link.href =
                            externalUrl;


                        link.target =
                            '_blank';


                        link.rel =
                            'noopener noreferrer';


                        link.className =
                            'course-external-button';


                        link.textContent =
                            'Open Lesson';


                        externalWrapper.appendChild(
                            text
                        );


                        externalWrapper.appendChild(
                            link
                        );


                        videoContainer.appendChild(
                            externalWrapper
                        );

                    }


                    /*
                     * ==================================
                     * NO VIDEO
                     * ==================================
                     */

                    else {

                        videoContainer.innerHTML =
                            '<div class="course-video-placeholder">' +
                            'Video unavailable' +
                            '</div>';

                    }

                }
            );

        });


        /*
         * ==========================================
         * OPEN FIRST MODULE
         * ==========================================
         */

        if (modules.length > 0) {

            modules[0].classList.add(
                'active'
            );

        }

    }



    /*
     * ==========================================
     * NORMAL WORDPRESS PAGE
     * ==========================================
     */

    $(document).ready(function () {

        document
            .querySelectorAll(
                '.course-learning-wrapper'
            )
            .forEach(function (wrapper) {

                initCourse(wrapper);

            });

    });



    /*
     * ==========================================
     * ELEMENTOR
     * ==========================================
     */

    $(window).on(
        'elementor/frontend/init',
        function () {

            if (
                typeof elementorFrontend === 'undefined'
            ) {
                return;
            }


            elementorFrontend.hooks.addAction(
                'frontend/element_ready/shortcode.default',
                function ($scope) {

                    const wrapper =
                        $scope.find(
                            '.course-learning-wrapper'
                        )[0];


                    initCourse(wrapper);

                }
            );

        }
    );


})(jQuery);