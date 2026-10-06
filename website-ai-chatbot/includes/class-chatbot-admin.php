<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class WAC_Admin {


    /**
     * Admin menu
     */
    public static function menu() {

        add_menu_page(

            'Website AI Chatbot',

            'AI Chatbot',

            'manage_options',

            'website-ai-chatbot',

            array(
                __CLASS__,
                'page',
            ),

            'dashicons-format-chat',

            30

        );
    }


    /**
     * Admin page
     */
    public static function page() {

        if (
            ! current_user_can(
                'manage_options'
            )
        ) {
            return;
        }


        ?>

        <div class="wrap">

            <h1>
                Website AI Chatbot
            </h1>


            <h2>
                Ollama Settings
            </h2>


            <form method="post">

                <?php
                wp_nonce_field(
                    'wac_save_settings'
                );
                ?>


                <table class="form-table">


                    <tr>

                        <th>
                            Ollama URL
                        </th>

                        <td>

                            <input
                                type="text"
                                name="wac_ollama_url"
                                value="<?php

                                echo esc_attr(

                                    get_option(
                                        'wac_ollama_url',
                                        'http://127.0.0.1:11434'
                                    )

                                );

                                ?>"
                                class="regular-text"
                            >

                        </td>

                    </tr>


                    <tr>

                        <th>
                            AI Model
                        </th>

                        <td>

                            <input
                                type="text"
                                name="wac_model"
                                value="<?php

                                echo esc_attr(

                                    get_option(
                                        'wac_model',
                                        'llama3.2:3b'
                                    )

                                );

                                ?>"
                                class="regular-text"
                            >

                        </td>

                    </tr>


                </table>


                <p>

                    <button
                        type="submit"
                        name="wac_save"
                        class="button button-primary"
                    >
                        Save Settings
                    </button>

                </p>


            </form>


            <hr>


            <h2>
                Website Knowledge
            </h2>


            <p>
                Index your website content so the chatbot
                can answer questions about your website.
            </p>


            <form method="post">

                <?php

                wp_nonce_field(
                    'wac_index_content'
                );

                ?>


                <button
                    type="submit"
                    name="wac_index"
                    class="button button-primary"
                >
                    Index Website Content
                </button>


            </form>


        </div>

        <?php
    }
}


/**
 * Admin menu
 */
add_action(
    'admin_menu',
    array(
        'WAC_Admin',
        'menu',
    )
);


/**
 * Save settings
 */
add_action(
    'admin_init',
    function () {

        if (
            isset(
                $_POST['wac_save']
            )
        ) {

            check_admin_referer(
                'wac_save_settings'
            );


            if (
                ! current_user_can(
                    'manage_options'
                )
            ) {
                return;
            }


            update_option(

                'wac_ollama_url',

                esc_url_raw(
                    $_POST['wac_ollama_url']
                )

            );


            update_option(

                'wac_model',

                sanitize_text_field(
                    $_POST['wac_model']
                )

            );

        }


        if (
            isset(
                $_POST['wac_index']
            )
        ) {

            check_admin_referer(
                'wac_index_content'
            );


            if (
                ! current_user_can(
                    'manage_options'
                )
            ) {
                return;
            }


            WAC_Indexer::index_content();


            add_action(
                'admin_notices',
                function () {

                    ?>

                    <div class="notice notice-success">

                        <p>
                            Website content indexed successfully.
                        </p>

                    </div>

                    <?php

                }
            );

        }

    }
);