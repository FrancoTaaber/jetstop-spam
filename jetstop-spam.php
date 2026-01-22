<?php
/**
 * Plugin Name: Jetstop Spam
 * Plugin URI: https://github.com/FrancoTaaber/jetstop-spam
 * Description: The most comprehensive anti-spam solution for WordPress. Protect comments, registrations, logins, and 10+ form plugins with honeypot, rate limiting, blacklists, and more. No CAPTCHA required.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author: Franco Taaber
 * Author URI: https://francotaaber.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jetstop-spam
 * Domain Path: /languages
 *
 * @package Jetstop_Spam
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'JETSTOP_VERSION', '1.0.0' );
define( 'JETSTOP_FILE', __FILE__ );
define( 'JETSTOP_DIR', plugin_dir_path( __FILE__ ) );
define( 'JETSTOP_URL', plugin_dir_url( __FILE__ ) );
define( 'JETSTOP_BASENAME', plugin_basename( __FILE__ ) );
define( 'JETSTOP_MIN_PHP', '7.4' );
define( 'JETSTOP_MIN_WP', '5.8' );

/**
 * Check PHP version.
 *
 * @return bool
 */
function jetstop_php_version_check() {
	return version_compare( PHP_VERSION, JETSTOP_MIN_PHP, '>=' );
}

/**
 * Check WordPress version.
 *
 * @return bool
 */
function jetstop_wp_version_check() {
	return version_compare( get_bloginfo( 'version' ), JETSTOP_MIN_WP, '>=' );
}

/**
 * Display admin notice for PHP version.
 */
function jetstop_php_version_notice() {
	printf(
		'<div class="notice notice-error"><p><strong>Jetstop Spam:</strong> %s</p></div>',
		sprintf(
			/* translators: 1: Required PHP version 2: Current PHP version */
			esc_html__( 'Requires PHP %1$s or higher. Your current version is %2$s.', 'jetstop-spam' ),
			JETSTOP_MIN_PHP,
			PHP_VERSION
		)
	);
}

/**
 * Display admin notice for WordPress version.
 */
function jetstop_wp_version_notice() {
	printf(
		'<div class="notice notice-error"><p><strong>Jetstop Spam:</strong> %s</p></div>',
		sprintf(
			/* translators: 1: Required WordPress version 2: Current WordPress version */
			esc_html__( 'Requires WordPress %1$s or higher. Your current version is %2$s.', 'jetstop-spam' ),
			JETSTOP_MIN_WP,
			get_bloginfo( 'version' )
		)
	);
}

/**
 * Initialize the plugin.
 */
function jetstop_init() {
	// Check PHP version.
	if ( ! jetstop_php_version_check() ) {
		add_action( 'admin_notices', 'jetstop_php_version_notice' );
		return;
	}

	// Check WordPress version.
	if ( ! jetstop_wp_version_check() ) {
		add_action( 'admin_notices', 'jetstop_wp_version_notice' );
		return;
	}

	// Load text domain.
	load_plugin_textdomain(
		'jetstop-spam',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Load main plugin class.
	require_once JETSTOP_DIR . 'includes/class-plugin.php';
	Jetstop_Spam\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'jetstop_init', 5 );

/**
 * Plugin activation.
 */
function jetstop_activate() {
	// Check PHP version.
	if ( ! jetstop_php_version_check() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			sprintf(
				/* translators: %s: Required PHP version */
				esc_html__( 'Jetstop Spam requires PHP %s or higher.', 'jetstop-spam' ),
				JETSTOP_MIN_PHP
			)
		);
	}

	// Default protection settings.
	$default_protection = array(
		'honeypot'          => true,
		'time_check'        => true,
		'min_time'          => 3,
		'js_check'          => true,
		'rate_limit'        => true,
		'rate_limit_count'  => 5,
		'rate_limit_period' => 60,
		'blacklist'         => true,
		'disposable_emails' => true,
		'link_limit'        => true,
		'max_links'         => 3,
	);

	// Default integration settings (auto-detect enabled).
	$default_integrations = array(
		'wp_comments'     => true,
		'wp_registration' => true,
		'wp_login'        => true,
		'contact_form_7'  => true,
		'wpforms'         => true,
		'gravity_forms'   => true,
		'forminator'      => true,
		'elementor'       => true,
		'fluent_forms'    => true,
		'ninja_forms'     => true,
		'formidable'      => true,
		'woocommerce'     => true,
		'bbpress'         => true,
	);

	// Default blacklists.
	$default_blacklists = array(
		'emails'   => '',
		'ips'      => '',
		'keywords' => '',
	);

	// Save defaults if not exists.
	if ( false === get_option( 'jetstop_protection' ) ) {
		add_option( 'jetstop_protection', $default_protection );
	}

	if ( false === get_option( 'jetstop_integrations' ) ) {
		add_option( 'jetstop_integrations', $default_integrations );
	}

	if ( false === get_option( 'jetstop_blacklists' ) ) {
		add_option( 'jetstop_blacklists', $default_blacklists );
	}

	if ( false === get_option( 'jetstop_log_enabled' ) ) {
		add_option( 'jetstop_log_enabled', true );
	}

	// Generate unique honeypot field name.
	if ( false === get_option( 'jetstop_honeypot_field' ) ) {
		$chars = 'abcdefghijklmnopqrstuvwxyz';
		$name  = substr( str_shuffle( $chars ), 0, 6 ) . wp_rand( 100, 999 );
		add_option( 'jetstop_honeypot_field', $name );
	}

	// Create database tables.
	jetstop_create_tables();

	// Clear rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'jetstop_activate' );

/**
 * Create database tables.
 */
function jetstop_create_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Blocked log table.
	$table_log = $wpdb->prefix . 'jetstop_log';
	$sql_log   = "CREATE TABLE IF NOT EXISTS $table_log (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		source varchar(50) NOT NULL,
		reason varchar(50) NOT NULL,
		ip_address varchar(45) NOT NULL,
		user_agent text,
		data longtext,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY source (source),
		KEY reason (reason),
		KEY ip_address (ip_address),
		KEY created_at (created_at)
	) $charset_collate;";

	// Statistics table.
	$table_stats = $wpdb->prefix . 'jetstop_stats';
	$sql_stats   = "CREATE TABLE IF NOT EXISTS $table_stats (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		date_key date NOT NULL,
		source varchar(50) NOT NULL,
		reason varchar(50) NOT NULL,
		count int(11) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY date_source_reason (date_key, source, reason),
		KEY date_key (date_key)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_log );
	dbDelta( $sql_stats );

	// Store database version.
	update_option( 'jetstop_db_version', '1.0.0' );
}

/**
 * Plugin deactivation.
 */
function jetstop_deactivate() {
	// Clean up transients.
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_jetstop_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_jetstop_%'" );

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'jetstop_deactivate' );

/**
 * Add settings link to plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function jetstop_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		admin_url( 'admin.php?page=jetstop-spam' ),
		esc_html__( 'Settings', 'jetstop-spam' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . JETSTOP_BASENAME, 'jetstop_plugin_action_links' );

/**
 * Add plugin meta links.
 *
 * @param array  $links Plugin meta links.
 * @param string $file  Plugin file.
 * @return array
 */
function jetstop_plugin_row_meta( $links, $file ) {
	if ( JETSTOP_BASENAME === $file ) {
		$links[] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			'https://github.com/FrancoTaaber/jetstop-spam',
			esc_html__( 'Documentation', 'jetstop-spam' )
		);
		$links[] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			'https://github.com/FrancoTaaber/jetstop-spam/issues',
			esc_html__( 'Support', 'jetstop-spam' )
		);
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'jetstop_plugin_row_meta', 10, 2 );
