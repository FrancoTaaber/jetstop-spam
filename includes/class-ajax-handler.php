<?php
/**
 * AJAX handler class.
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
 * Ajax_Handler class.
 */
class Ajax_Handler {

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
		add_action( 'wp_ajax_jetstop_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_jetstop_save_blacklists', array( $this, 'save_blacklists' ) );
		add_action( 'wp_ajax_jetstop_get_log', array( $this, 'get_log' ) );
		add_action( 'wp_ajax_jetstop_delete_log', array( $this, 'delete_log' ) );
		add_action( 'wp_ajax_jetstop_clear_log', array( $this, 'clear_log' ) );
		add_action( 'wp_ajax_jetstop_get_stats', array( $this, 'get_stats' ) );
	}

	/**
	 * Verify nonce and capability.
	 *
	 * @return bool
	 */
	private function verify_request() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		return wp_verify_nonce( $nonce, 'jetstop_admin' ) && current_user_can( 'manage_options' );
	}

	/**
	 * Save protection settings.
	 */
	public function save_settings() {
		if ( ! $this->verify_request() ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'jetstop-spam' ) ) );
		}

		$settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();

		$protection = array(
			'honeypot'          => ! empty( $settings['honeypot'] ),
			'time_check'        => ! empty( $settings['time_check'] ),
			'min_time'          => absint( $settings['min_time'] ?? 3 ),
			'js_check'          => ! empty( $settings['js_check'] ),
			'rate_limit'        => ! empty( $settings['rate_limit'] ),
			'rate_limit_count'  => absint( $settings['rate_limit_count'] ?? 5 ),
			'rate_limit_period' => absint( $settings['rate_limit_period'] ?? 60 ),
			'blacklist'         => ! empty( $settings['blacklist'] ),
			'disposable_emails' => ! empty( $settings['disposable_emails'] ),
			'link_limit'        => ! empty( $settings['link_limit'] ),
			'max_links'         => absint( $settings['max_links'] ?? 3 ),
		);

		$integrations = array();
		$integration_keys = array(
			'wp_comments', 'wp_registration', 'wp_login',
			'contact_form_7', 'wpforms', 'gravity_forms', 'forminator',
			'elementor', 'fluent_forms', 'ninja_forms', 'formidable',
			'woocommerce', 'bbpress',
		);

		foreach ( $integration_keys as $key ) {
			$integrations[ $key ] = ! empty( $settings['integration_' . $key] );
		}

		update_option( 'jetstop_protection', $protection );
		update_option( 'jetstop_integrations', $integrations );
		update_option( 'jetstop_log_enabled', ! empty( $settings['log_enabled'] ) );

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'jetstop-spam' ) ) );
	}

	/**
	 * Save blacklists.
	 */
	public function save_blacklists() {
		if ( ! $this->verify_request() ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'jetstop-spam' ) ) );
		}

		$blacklists = array(
			'emails'   => sanitize_textarea_field( wp_unslash( $_POST['emails'] ?? '' ) ),
			'ips'      => sanitize_textarea_field( wp_unslash( $_POST['ips'] ?? '' ) ),
			'keywords' => sanitize_textarea_field( wp_unslash( $_POST['keywords'] ?? '' ) ),
		);

		update_option( 'jetstop_blacklists', $blacklists );

		wp_send_json_success( array( 'message' => __( 'Blacklists saved.', 'jetstop-spam' ) ) );
	}

	/**
	 * Get log entries.
	 */
	public function get_log() {
		if ( ! $this->verify_request() ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'jetstop-spam' ) ) );
		}

		$args = array(
			'source'   => sanitize_key( $_POST['source'] ?? '' ),
			'reason'   => sanitize_key( $_POST['reason'] ?? '' ),
			'ip'       => sanitize_text_field( $_POST['ip'] ?? '' ),
			'per_page' => absint( $_POST['per_page'] ?? 20 ),
			'page'     => absint( $_POST['page'] ?? 1 ),
		);

		$logger  = $this->plugin->get_logger();
		$entries = $logger->get_entries( $args );
		$total   = $logger->get_total( $args );

		// Format entries.
		foreach ( $entries as &$entry ) {
			$entry['source_label'] = Admin::get_source_label( $entry['source'] );
			$entry['reason_label'] = Admin::get_reason_label( $entry['reason'] );
			$entry['created_at']   = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry['created_at'] ) );
		}

		wp_send_json_success(
			array(
				'entries' => $entries,
				'total'   => $total,
				'pages'   => ceil( $total / $args['per_page'] ),
			)
		);
	}

	/**
	 * Delete log entry.
	 */
	public function delete_log() {
		if ( ! $this->verify_request() ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'jetstop-spam' ) ) );
		}

		$id = absint( $_POST['id'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'jetstop-spam' ) ) );
		}

		$this->plugin->get_logger()->delete( $id );

		wp_send_json_success();
	}

	/**
	 * Clear all logs.
	 */
	public function clear_log() {
		if ( ! $this->verify_request() ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'jetstop-spam' ) ) );
		}

		$this->plugin->get_logger()->clear_all();

		wp_send_json_success( array( 'message' => __( 'Log cleared.', 'jetstop-spam' ) ) );
	}

	/**
	 * Get statistics.
	 */
	public function get_stats() {
		if ( ! $this->verify_request() ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'jetstop-spam' ) ) );
		}

		$days  = absint( $_POST['days'] ?? 30 );
		$stats = $this->plugin->get_statistics()->get_summary( $days );

		// Add labels.
		$by_source = array();
		foreach ( $stats['by_source'] as $key => $count ) {
			$by_source[] = array(
				'key'   => $key,
				'label' => Admin::get_source_label( $key ),
				'count' => $count,
			);
		}

		$by_reason = array();
		foreach ( $stats['by_reason'] as $key => $count ) {
			$by_reason[] = array(
				'key'   => $key,
				'label' => Admin::get_reason_label( $key ),
				'count' => $count,
			);
		}

		$stats['by_source'] = $by_source;
		$stats['by_reason'] = $by_reason;

		wp_send_json_success( $stats );
	}
}
