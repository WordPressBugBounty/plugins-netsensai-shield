<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hide the default Site Health REST API test for guest users,
 * while still enforcing REST API blocking per Netsensai Shield settings.
 */
add_filter( 'site_status_tests', function ( $tests ) {
    // Remove the default REST API availability test
    if ( isset( $tests['direct']['rest_availability'] ) ) {
        unset( $tests['direct']['rest_availability'] );
    }

    // Add our custom REST API test
    $tests['direct']['ns_shield_rest_availability'] = array(
        'label' => __( 'REST API availability (Netsensai Shield)', 'netsensai-shield' ),
        'test'  => 'ns_shield_custom_rest_test',
    );

    return $tests;
} );

function ns_shield_custom_rest_test() {
    if ( ! is_user_logged_in() ) {
        return array(
            'label'       => __( 'REST API availability restricted for security', 'netsensai-shield' ),
            'status'      => 'good',
            'badge'       => array(
                'label' => __( 'Security', 'netsensai-shield' ),
                'color' => 'blue',
            ),
            'description' => __( 'REST API is restricted for non-authenticated users to increase security. This may show as an error in Site Health, but it is intentional.', 'netsensai-shield' ),
            'actions'     => '',
            'test'        => 'ns_shield_custom_rest_test',
        );
    }

    return array(
        'label'       => __( 'REST API is available.', 'netsensai-shield' ),
        'status'      => 'good',
        'badge'       => array(
            'label' => __( 'Security', 'netsensai-shield' ),
            'color' => 'blue',
        ),
        'description' => __( 'The REST API is working as expected.', 'netsensai-shield' ),
        'actions'     => '',
        'test'        => 'ns_shield_custom_rest_test',
    );
}
