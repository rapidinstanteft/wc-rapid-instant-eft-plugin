<?php
/*
 * Plugin Name: WC Rapid Instant EFT Plugin
 * Description: This plugin allows for Instant EFT Payments.
 * Author: The Rapid Group of Companies
 * Author URI: https://rapidinstanteft.co.za/
 * Version: 1.0.0
 * Text Domain: wc-gateway-rapidpayment
 * Domain Path: /languages
 *
 * Copyright (c) 2020 Rapid Payment Gateway
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required minimums and constants
 */
define( 'WC_RAPIDPAYMENT_VERSION', '1.0.0' );
define( 'WC_RAPIDPAYMENT_MIN_WC_VER', '2.2.0' );
define( 'WC_RAPIDPAYMENT_MAIN_FILE', __FILE__ );
define( 'WC_RAPIDPAYMENT_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_RAPID_LOGO' , WC_RAPIDPAYMENT_PLUGIN_URL . '/assets/img/Rapid-Instant-EFT-Plugin-Logo.png');

if ( ! class_exists( 'WC_RapidPayment' ) ) :

class WC_RapidPayment {

	/**
	 * @var Singleton The reference the *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * @var Reference to logging class.
	 */
	private static $log;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup() {}

	/**
	 * Notices (array)
	 * @var array
	 */
	public $notices = array();

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init the plugin after plugins_loaded so environment variables are set.
	 */
	public function init() {
		// Don't hook anything else in the plugin if we're in an incompatible environment
		if ( self::get_environment_warning() ) {
			return;
		}

		// Init the gateway itself
		$this->init_gateways();

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication)
	 */
	public function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message
		);
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation.
	 */
	public function check_environment() {
		$environment_warning = self::get_environment_warning();

		if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			$this->add_admin_notice( 'bad_environment', 'error', $environment_warning );
		}

		// Check if secret key present. Otherwise prompt, via notice, to go to
		// setting.
		if ( ! class_exists( 'WC_RapidPayment_API' ) ) {
			include_once( dirname( __FILE__ ) . '/includes/class-wc-rapidpayment-api.php' );
		}

		$username = WC_RapidPayment_API::get_username();
        $password = WC_RapidPayment_API::get_password();

		if ( (empty( $username ) || empty($password)) && ! ( isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && 'rapidpayment' === $_GET['section'] ) ) {
			$setting_link = $this->get_setting_link();
			$this->add_admin_notice( 'prompt_connect', 'notice notice-warning', sprintf( __( 'Rapid Payment is almost ready. To get started, <a href="%s">set your api credentials</a>.', 'wc-gateway-rapidpayment' ), $setting_link ) );
		}
	}

	/**
	 * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
	 * found or false if the environment has no problems.
	 */
	static function get_environment_warning() {

		if ( ! defined( 'WC_VERSION' ) ) {
			return __( 'WooCommerce Rapid Payment requires WooCommerce to be activated to work.', 'wc-gateway-rapidpayment' );
		} 

		if ( version_compare( WC_VERSION, WC_RAPIDPAYMENT_MIN_WC_VER, '<' ) ) {
			$message = __( 'WooCommerce Rapid Payment - The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'wc-gateway-rapidpayment', 'wc-gateway-rapidpayment' );

			return sprintf( $message, WC_RAPIDPAYMENT_MIN_WC_VER, WC_VERSION );
		}

        if (get_woocommerce_currency() != 'ZAR') {
            return __( 'WooCommerce Rapid Payment - Only available for South Africa, unsupported currency detected.' );
        }

		if ( ! function_exists( 'curl_init' ) ) {
			return __( 'WooCommerce Rapid Payment - cURL is not installed.', 'wc-gateway-rapidpayment' );
		}

		return false;
	}

	/**
	 * Adds plugin action links
	 *
	 * @since 1.0.0
	 */
	public function plugin_action_links( $links ) {
		$setting_link = $this->get_setting_link();

		$plugin_links = array(
			'<a href="' . $setting_link . '">' . __( 'Settings', 'wc-gateway-rapidpayment' ) . '</a>'
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Get setting link.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting link
	 */
	public function get_setting_link() {
		$use_id_as_section = version_compare( WC()->version, '2.6', '>=' );

		$section_slug = $use_id_as_section ? 'rapidpayment' : strtolower( 'WC_Gateway_RapidPayment' );

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}

	/**
	 * Display any notices we've collected thus far (e.g. for connection, disconnection)
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
			echo "</p></div>";
		}
	}

	/**
	 * Initialize the gateway. Called very early - in the context of the plugins_loaded action
	 *
	 * @since 1.0.0
	 */
	public function init_gateways() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

        include_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-rapidpayment.php' );

		load_plugin_textdomain( 'wc-gateway-rapidpayment', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
	}

	/**
	 * Add the gateways to WooCommerce
	 *
	 * @since 1.0.0
	 */
	public function add_gateways( $methods ) {
        $methods[] = 'WC_Gateway_RapidPayment';
		return $methods;
	}

	public static function log( $message ) {
		if ( empty( self::$log ) ) {
			self::$log = new WC_Logger();
		}

		self::$log->add( 'wc-gateway-rapidpayment', $message );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $message );
		}
	}
}

$GLOBALS['wc_rapidpayment'] = WC_RapidPayment::get_instance();

endif;
