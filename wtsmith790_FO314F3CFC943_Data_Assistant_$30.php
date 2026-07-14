<?php

add_filter('woocommerce_checkout_fields', function ($fields) {

    // Industry Dropdown
    $fields['billing']['billing_industry'] = [
        'type'        => 'select',
        'label'       => __('Industry', 'woocommerce'),
        'required'    => true,
        'class'       => ['form-row-wide'],
        'options'     => [
            '' => 'Select your industry',
            'university' => 'University',
            'laboratory_rnd' => 'Laboratory R&D',
            'licensed_researcher' => 'Licensed Researcher',
            'research_institution' => 'Research Institution',
        ],
        'priority' => 110,
    ];

    // Checkbox 1 (Terms confirmation)
    $fields['billing']['billing_lab_terms'] = [
        'type'     => 'checkbox',
        'label'    => 'I confirm that I am 21+ years old and agree to the Terms & Conditions',
        'required' => true,
        'class'    => ['form-row-wide'],
        'priority' => 120,
    ];

    // Checkbox 2 (Usage confirmation)
    $fields['billing']['billing_lab_use'] = [
        'type'     => 'checkbox',
        'label'    => 'This purchase is for laboratory research use only (not for human use)',
        'required' => true,
        'class'    => ['form-row-wide'],
        'priority' => 130,
    ];

    return $fields;
});


// validate the custom fields on checkout
add_action('woocommerce_checkout_process', function () {

    if (empty($_POST['billing_industry'])) {
        wc_add_notice('Please select your industry.', 'error');
    }

    if (empty($_POST['billing_lab_terms'])) {
        wc_add_notice('You must confirm the terms.', 'error');
    }

    if (empty($_POST['billing_lab_use'])) {
        wc_add_notice('You must confirm research usage.', 'error');
    }

});

// save the custom fields to order meta
add_action('woocommerce_checkout_create_order', function ($order, $data) {

    if (isset($_POST['billing_industry'])) {
        $order->update_meta_data('Industry', sanitize_text_field($_POST['billing_industry']));
    }

    if (isset($_POST['billing_lab_terms'])) {
        $order->update_meta_data('Lab Terms Accepted', 'Yes');
    }

    if (isset($_POST['billing_lab_use'])) {
        $order->update_meta_data('Lab Use Confirmed', 'Yes');
    }

}, 10, 2);

// display the custom fields in the admin order details
add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {

    echo '<p><strong>Industry:</strong> ' . esc_html($order->get_meta('Industry')) . '</p>';
    echo '<p><strong>Lab Terms 21+ :</strong> ' . esc_html($order->get_meta('Lab Terms Accepted')) . '</p>';
    echo '<p><strong>Lab Use:</strong> ' . esc_html($order->get_meta('Lab Use Confirmed')) . '</p>';

});