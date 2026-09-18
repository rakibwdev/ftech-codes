<?php


if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'Final Round',
        'Final Round',
        'manage_options',
        'final-round-manager',
        'frm_admin_page',
        'dashicons-list-view',
        30
    );
});

function frm_admin_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['frm_save'])) {

        check_admin_referer('frm_save_final_round');

        $value = isset($_POST['final_round_index'])
            ? sanitize_text_field(wp_unslash($_POST['final_round_index']))
            : '';

        $numbers = preg_split('/[\s,]+/', trim($value));

        $numbers = array_filter($numbers, function ($number) {
            return ctype_digit($number) && intval($number) > 0;
        });

        $numbers = array_map('intval', $numbers);

        update_option('frm_final_round_index', implode(',', $numbers));

        echo '<div class="notice notice-success is-dismissible"><p>Final Round order saved.</p></div>';
    }

    $saved_value = get_option('frm_final_round_index', '');

    $display_value = str_replace(',', ' ', $saved_value);
    ?>

    <div class="wrap">

        <h1>Final Round</h1>

        <form method="post">

            <?php wp_nonce_field('frm_save_final_round'); ?>

            <table class="form-table">

                <tr>
                    <th scope="row">
                        <label for="final_round_index">
                            Final Round Index
                        </label>
                    </th>

                    <td>
                        <textarea
                            id="final_round_index"
                            name="final_round_index"
                            class="regular-text"
                            rows="8"
                            placeholder="Example: 2 3 1 5 4 8 7 6..."
                        ><?php echo esc_textarea($display_value); ?></textarea>

                        <p class="description">
                            Enter participant indexes separated by spaces or commas.
                            Example: 2 3 1
                        </p>
                    </td>
                </tr>

            </table>

            <p>
                <button type="submit" name="frm_save" class="button button-primary">
                    Save Final Round
                </button>
            </p>

        </form>

    </div>

    <?php
}

add_action('wp_enqueue_scripts', function () {

    wp_register_script(
        'frm-final-round-data',
        false,
        [],
        null,
        true
    );

    wp_enqueue_script('frm-final-round-data');

    wp_add_inline_script(
        'frm-final-round-data',
        'window.frmAjaxUrl = ' . wp_json_encode(admin_url('admin-ajax.php')) . ';
         window.frmFinalRoundNonce = ' . wp_json_encode(wp_create_nonce('frm_get_final_round')) . ';',
        'before'
    );

});

add_action('wp_ajax_frm_get_final_round', 'frm_get_final_round');
add_action('wp_ajax_nopriv_frm_get_final_round', 'frm_get_final_round');

function frm_get_final_round() {

    check_ajax_referer('frm_get_final_round', 'nonce');

    $saved_value = get_option('frm_final_round_index', '');

    $numbers = preg_split(
        '/[\s,]+/',
        trim($saved_value)
    );

    $numbers = array_filter($numbers, function ($number) {
        return ctype_digit($number) && intval($number) > 0;
    });

    $numbers = array_map('intval', $numbers);

    wp_send_json_success([
        'indexes' => array_values($numbers),
    ]);
}