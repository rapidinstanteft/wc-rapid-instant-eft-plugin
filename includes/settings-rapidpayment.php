<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_rapidpayment_settings',
	array(
		'enabled' => array(
			'title'       => __( 'Enable/Disable', 'wc-gateway-rapidpayment' ),
			'label'       => __( 'Enable Rapid Instant EFT Gateway', 'wc-gateway-rapidpayment' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => __( 'Title', 'wc-gateway-rapidpayment' ),
			'type'        => 'text',
			'description' => __( 'This allows Instant EFT Payments.', 'wc-gateway-rapidpayment' ),
			'default'     => __( 'WC Rapid Instant EFT Plugin', 'wc-gateway-rapidpayment' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'wc-gateway-rapidpayment' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout.', 'wc-gateway-rapidpayment' ),
			'default'     => __( 'Pay using your internet banking login.', 'wc-gateway-rapidpayment'),
			'desc_tip'    => true,
		),
		'username' => array(
			'title'       => __( 'API Username', 'wc-gateway-rapidpayment' ),
			'type'        => 'text',
			'description' => __( 'Get your API username from your Rapid Payment account.', 'wc-gateway-rapidpayment' ),
			'default'     => '',
			'desc_tip'    => true,
		),
        'password' => array(
            'title'       => __( 'API Password', 'wc-gateway-rapidpayment' ),
            'type'        => 'password',
            'description' => __( 'Get your API password from your Rapid Payment account.', 'wc-gateway-rapidpayment' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
		'logging' => array(
			'title'       => __( 'Logging', 'wc-gateway-rapidpayment' ),
			'label'       => __( 'Log debug messages', 'wc-gateway-rapidpayment' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'wc-gateway-rapidpayment' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
	)
);
