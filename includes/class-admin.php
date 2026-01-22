<?php
/**
 * Admin class.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class Admin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Jetstop Spam', 'jetstop-spam' ),
			__( 'Jetstop Spam', 'jetstop-spam' ),
			'manage_options',
			'jetstop-spam',
			array( $this, 'render_page' ),
			'dashicons-shield-alt',
			80
		);

		add_submenu_page(
			'jetstop-spam',
			__( 'Dashboard', 'jetstop-spam' ),
			__( 'Dashboard', 'jetstop-spam' ),
			'manage_options',
			'jetstop-spam',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'jetstop-spam',
			__( 'Settings', 'jetstop-spam' ),
			__( 'Settings', 'jetstop-spam' ),
			'manage_options',
			'jetstop-spam-settings',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'jetstop-spam',
			__( 'Blacklists', 'jetstop-spam' ),
			__( 'Blacklists', 'jetstop-spam' ),
			'manage_options',
			'jetstop-spam-blacklists',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'jetstop-spam',
			__( 'Blocked Log', 'jetstop-spam' ),
			__( 'Blocked Log', 'jetstop-spam' ),
			'manage_options',
			'jetstop-spam-log',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'jetstop-spam' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'jetstop-admin',
			JETSTOP_URL . 'admin/css/admin.css',
			array(),
			JETSTOP_VERSION
		);

		wp_enqueue_script(
			'jetstop-admin',
			JETSTOP_URL . 'admin/js/admin.js',
			array( 'jquery', 'wp-util' ),
			JETSTOP_VERSION,
			true
		);

		wp_localize_script(
			'jetstop-admin',
			'jetstopAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'jetstop_admin' ),
				'i18n'    => array(
					'saving'       => __( 'Saving...', 'jetstop-spam' ),
					'saved'        => __( 'Saved!', 'jetstop-spam' ),
					'error'        => __( 'Error occurred', 'jetstop-spam' ),
					'confirmClear' => __( 'Clear all log entries?', 'jetstop-spam' ),
				),
			)
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'jetstop-spam';

		switch ( $page ) {
			case 'jetstop-spam-settings':
				include JETSTOP_DIR . 'admin/views/settings.php';
				break;

			case 'jetstop-spam-blacklists':
				include JETSTOP_DIR . 'admin/views/blacklists.php';
				break;

			case 'jetstop-spam-log':
				include JETSTOP_DIR . 'admin/views/logs.php';
				break;

			default:
				include JETSTOP_DIR . 'admin/views/dashboard.php';
				break;
		}
	}

	/**
	 * Get available integrations with status.
	 *
	 * @return array
	 */
	public static function get_integrations_status() {
		$integrations = array(
			'wp_comments'     => array(
				'name'      => __( 'WordPress Comments', 'jetstop-spam' ),
				'available' => true,
			),
			'wp_registration' => array(
				'name'      => __( 'WordPress Registration', 'jetstop-spam' ),
				'available' => get_option( 'users_can_register' ),
			),
			'wp_login'        => array(
				'name'      => __( 'WordPress Login', 'jetstop-spam' ),
				'available' => true,
			),
			'contact_form_7'  => array(
				'name'      => __( 'Contact Form 7', 'jetstop-spam' ),
				'available' => defined( 'WPCF7_VERSION' ),
			),
			'wpforms'         => array(
				'name'      => __( 'WPForms', 'jetstop-spam' ),
				'available' => defined( 'WPFORMS_VERSION' ),
			),
			'gravity_forms'   => array(
				'name'      => __( 'Gravity Forms', 'jetstop-spam' ),
				'available' => class_exists( 'GFForms' ),
			),
			'forminator'      => array(
				'name'      => __( 'Forminator', 'jetstop-spam' ),
				'available' => class_exists( 'Forminator' ),
			),
			'elementor'       => array(
				'name'      => __( 'Elementor Forms', 'jetstop-spam' ),
				'available' => defined( 'ELEMENTOR_PRO_VERSION' ),
			),
			'fluent_forms'    => array(
				'name'      => __( 'Fluent Forms', 'jetstop-spam' ),
				'available' => defined( 'FLUENTFORM_VERSION' ),
			),
			'ninja_forms'     => array(
				'name'      => __( 'Ninja Forms', 'jetstop-spam' ),
				'available' => class_exists( 'Ninja_Forms' ),
			),
			'formidable'      => array(
				'name'      => __( 'Formidable Forms', 'jetstop-spam' ),
				'available' => class_exists( 'FrmAppHelper' ),
			),
			'woocommerce'     => array(
				'name'      => __( 'WooCommerce', 'jetstop-spam' ),
				'available' => class_exists( 'WooCommerce' ),
			),
			'bbpress'         => array(
				'name'      => __( 'bbPress', 'jetstop-spam' ),
				'available' => class_exists( 'bbPress' ),
			),
		);

		return $integrations;
	}

	/**
	 * Get reason label.
	 *
	 * @param string $reason Reason key.
	 * @return string
	 */
	public static function get_reason_label( $reason ) {
		$labels = array(
			'honeypot'      => __( 'Honeypot', 'jetstop-spam' ),
			'time_check'    => __( 'Too Fast', 'jetstop-spam' ),
			'rate_limit'    => __( 'Rate Limited', 'jetstop-spam' ),
			'blacklist'     => __( 'Blacklist', 'jetstop-spam' ),
			'disposable'    => __( 'Disposable Email', 'jetstop-spam' ),
			'link_checker'  => __( 'Too Many Links', 'jetstop-spam' ),
			'ip_blacklisted'      => __( 'IP Blocked', 'jetstop-spam' ),
			'email_blacklisted'   => __( 'Email Blocked', 'jetstop-spam' ),
			'keyword_blacklisted' => __( 'Keyword Blocked', 'jetstop-spam' ),
		);

		return $labels[ $reason ] ?? ucwords( str_replace( '_', ' ', $reason ) );
	}

	/**
	 * Get source label.
	 *
	 * @param string $source Source key.
	 * @return string
	 */
	public static function get_source_label( $source ) {
		$labels = array(
			'wp_comments'     => __( 'Comments', 'jetstop-spam' ),
			'wp_registration' => __( 'Registration', 'jetstop-spam' ),
			'wp_login'        => __( 'Login', 'jetstop-spam' ),
			'contact_form_7'  => __( 'Contact Form 7', 'jetstop-spam' ),
			'wpforms'         => __( 'WPForms', 'jetstop-spam' ),
			'gravity_forms'   => __( 'Gravity Forms', 'jetstop-spam' ),
			'forminator'      => __( 'Forminator', 'jetstop-spam' ),
			'elementor'       => __( 'Elementor', 'jetstop-spam' ),
			'fluent_forms'    => __( 'Fluent Forms', 'jetstop-spam' ),
			'ninja_forms'     => __( 'Ninja Forms', 'jetstop-spam' ),
			'formidable'      => __( 'Formidable', 'jetstop-spam' ),
			'woocommerce'     => __( 'WooCommerce', 'jetstop-spam' ),
			'bbpress'         => __( 'bbPress', 'jetstop-spam' ),
		);

		return $labels[ $source ] ?? ucwords( str_replace( '_', ' ', $source ) );
	}
}
