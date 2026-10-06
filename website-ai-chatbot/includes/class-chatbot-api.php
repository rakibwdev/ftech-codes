<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class WAC_API {


    /**
     * Register REST API
     */
    public static function register_routes() {

        register_rest_route(
            'website-ai/v1',
            '/chat',
            array(

                'methods' =>
                    WP_REST_Server::CREATABLE,

                'callback' =>
                    array(
                        __CLASS__,
                        'chat',
                    ),

                'permission_callback' =>
                    '__return_true',

            )
        );
    }


    /**
     * Chat request
     */
    public static function chat(
        WP_REST_Request $request
    ) {

        $question =
            sanitize_text_field(
                $request->get_param(
                    'message'
                )
            );


        if ( empty( $question ) ) {

            return new WP_Error(
                'empty_question',
                'Please enter a question.',
                array(
                    'status' => 400,
                )
            );

        }


        /*
         * Search website
         */
        $results =
            WAC_Indexer::search(
                $question,
                5
            );


        if ( empty( $results ) ) {

            return array(
                'answer' =>
                    'Sorry, I could not find that information on this website.',
            );

        }


        /*
         * Build context
         */
        $context = '';


        foreach (
            $results as $result
        ) {

            $context .= "\n\n";

            $context .=
                "PAGE: " .
                $result['title'] .
                "\n";

            $context .=
                "URL: " .
                $result['url'] .
                "\n";

            $context .=
                "CONTENT: " .
                $result['content'];

        }


        /*
         * System instruction
         */
        $system_prompt = <<<PROMPT

You are a website assistant.

Answer questions ONLY using the website information provided below.

Do not invent information.

If the website information does not contain
the answer, say:

"I don't have that information from this website."

Keep answers short, clear and helpful.

WEBSITE INFORMATION:

{$context}

PROMPT;


        /*
         * Ollama settings
         */
        $ollama_url =
            untrailingslashit(
                get_option(
                    'wac_ollama_url',
                    'http://127.0.0.1:11434'
                )
            );


        $model =
            get_option(
                'wac_model',
                'llama3.2:3b'
            );


        /*
         * Send request to Ollama
         */
        $response = wp_remote_post(

            $ollama_url . '/api/chat',

            array(

                'timeout' => 60,

                'headers' => array(
                    'Content-Type' =>
                        'application/json',
                ),

                'body' =>
                    wp_json_encode(

                        array(

                            'model' =>
                                $model,

                            'stream' =>
                                false,

                            'messages' => array(

                                array(

                                    'role' =>
                                        'system',

                                    'content' =>
                                        $system_prompt,

                                ),

                                array(

                                    'role' =>
                                        'user',

                                    'content' =>
                                        $question,

                                ),

                            ),

                            'options' => array(

                                'temperature' =>
                                    0.2,

                            ),

                        )

                    ),

            )

        );


        /*
         * Check connection
         */
        if (
            is_wp_error(
                $response
            )
        ) {

            return new WP_Error(

                'ollama_error',

                'Could not connect to Ollama.',

                array(
                    'status' => 503,
                )

            );

        }


        /*
         * Decode response
         */
        $body =
            json_decode(

                wp_remote_retrieve_body(
                    $response
                ),

                true

            );


        if (
            empty(
                $body['message']['content']
            )
        ) {

            return new WP_Error(

                'ai_error',

                'AI did not return an answer.',

                array(
                    'status' => 500,
                )

            );

        }


        return array(

            'answer' =>
                trim(
                    $body['message']['content']
                ),

        );
    }
}


/**
 * Register API routes
 */
add_action(
    'rest_api_init',
    array(
        'WAC_API',
        'register_routes',
    )
);