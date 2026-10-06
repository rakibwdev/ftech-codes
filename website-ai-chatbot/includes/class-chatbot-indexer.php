<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class WAC_Indexer {


    /**
     * Index website content
     */
    public static function index_content() {

        global $wpdb;

        $table = $wpdb->prefix . 'website_ai_knowledge';


        /*
         * Clear old data
         */
        $wpdb->query(
            "TRUNCATE TABLE {$table}"
        );


        /*
         * Get public post types
         */
        $post_types = get_post_types(
            array(
                'public' => true,
            )
        );


        /*
         * Get posts/pages
         */
        $posts = get_posts(
            array(
                'post_type'      => $post_types,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
            )
        );


        foreach ( $posts as $post ) {

            $title = get_the_title(
                $post->ID
            );

            $url = get_permalink(
                $post->ID
            );


            /*
             * Remove HTML
             */
            $content = wp_strip_all_tags(
                strip_shortcodes(
                    $post->post_content
                )
            );


            /*
             * Clean spaces
             */
            $content = preg_replace(
                '/\s+/',
                ' ',
                $content
            );


            $content = trim(
                $content
            );


            if ( empty( $content ) ) {
                continue;
            }


            /*
             * Split content into chunks
             */
            $chunks = self::split_content(
                $content
            );


            foreach ( $chunks as $chunk ) {

                $wpdb->insert(

                    $table,

                    array(
                        'post_id' => $post->ID,
                        'title'   => $title,
                        'url'     => $url,
                        'content' => $chunk,
                    ),

                    array(
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                    )

                );

            }

        }


        return true;
    }


    /**
     * Split long content
     */
    private static function split_content(
        $content
    ) {

        $length = 1200;

        $chunks = array();

        $words = preg_split(
            '/\s+/',
            $content
        );

        $current = '';

        foreach ( $words as $word ) {

            if (
                strlen( $current . ' ' . $word )
                > $length
            ) {

                $chunks[] = trim(
                    $current
                );

                $current = $word;

            } else {

                $current .= ' ' . $word;

            }

        }


        if ( ! empty( $current ) ) {

            $chunks[] = trim(
                $current
            );

        }


        return $chunks;
    }


    /**
     * Search knowledge base
     */
    public static function search(
        $question,
        $limit = 5
    ) {

        global $wpdb;

        $table = $wpdb->prefix .
            'website_ai_knowledge';


        /*
         * Extract words
         */
        $words = preg_split(
            '/\s+/',
            strtolower(
                sanitize_text_field(
                    $question
                )
            )
        );


        $words = array_filter(
            $words,
            function ( $word ) {

                return strlen(
                    $word
                ) >= 3;

            }
        );


        if ( empty( $words ) ) {
            return array();
        }


        $conditions = array();

        $values = array();


        foreach ( $words as $word ) {

            $like = '%' .
                $wpdb->esc_like(
                    $word
                ) .
                '%';


            $conditions[] =
                '(title LIKE %s OR content LIKE %s)';


            $values[] = $like;
            $values[] = $like;

        }


        $sql = "
            SELECT *
            FROM {$table}
            WHERE
        ";


        $sql .= implode(
            ' OR ',
            $conditions
        );


        $sql .= "
            LIMIT 50
        ";


        $results = $wpdb->get_results(

            $wpdb->prepare(
                $sql,
                $values
            ),

            ARRAY_A

        );


        /*
         * Basic relevance score
         */
        foreach (
            $results as &$result
        ) {

            $score = 0;

            $text = strtolower(
                $result['title'] .
                ' ' .
                $result['content']
            );


            foreach (
                $words as $word
            ) {

                $score += substr_count(
                    $text,
                    $word
                );

            }


            $result['score'] = $score;

        }


        /*
         * Highest score first
         */
        usort(
            $results,
            function (
                $a,
                $b
            ) {

                return
                    $b['score']
                    <=>
                    $a['score'];

            }
        );


        return array_slice(
            $results,
            0,
            $limit
        );
    }
}