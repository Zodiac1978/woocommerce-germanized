<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_DOI extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Extend the WooCommerce registration process with a double opt in.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Double Opt In', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'double_opt_in';
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'doi_options' ),

			array(
				'title'   => __( 'Enable', 'woocommerce-germanized' ),
				'desc'    => __( 'Enable customer double opt in during registration.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'If customer chooses to create a customer account an email with an activation link will be sent by mail. Customer account will be marked as activated if user clicks on the link within the email. More information on this topic can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://t3n.de/news/urteil-anmeldebestatigungen-double-opt-in-pflicht-592304/' ) . '</div>',
				'id'      => 'woocommerce_gzd_customer_activation',
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),
			array(
				'title'             => __( 'Disable', 'woocommerce-germanized' ),
				'desc'              => __( 'Disable login and checkout for unactivated customers.', 'woocommerce-germanized' ),
				'desc_tip'          => __( 'Customers that did not click on the activation link will not be able to complete checkout nor login to their account.', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_customer_activation_login_disabled',
				'default'           => 'no',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_customer_activation' => '',
				),
				'type'              => 'gzd_toggle',
			),
			array(
				'title'             => __( 'Delete unactivated after', 'woocommerce-germanized' ),
				'desc_tip'          => __( 'This will make sure unactivated customer accounts will be deleted after X days. Set to 0 if you don\'t want to automatically delete unactivated customers.', 'woocommerce-germanized' ),
				'desc'              => __( 'days', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_customer_cleanup_interval',
				'type'              => 'number',
				'css'               => 'width: 80px;',
				'custom_attributes' => array( 'min'                                              => 0,
				                              'step'                                             => 1,
				                              'data-show_if_woocommerce_gzd_customer_activation' => ''
				),
				'default'           => 7,
			),

			array( 'type' => 'sectionend', 'id' => 'doi_options' ),
		);
	}

	protected function get_enable_option_name() {
		return 'woocommerce_gzd_customer_activation';
	}

	public function supports_disabling() {
		return true;
	}
}