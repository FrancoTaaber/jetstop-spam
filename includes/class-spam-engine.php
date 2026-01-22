<?php
/**
 * Spam detection engine.
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
 * Spam_Engine class.
 */
class Spam_Engine {

	/**
	 * Protection settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Statistics instance.
	 *
	 * @var Statistics
	 */
	private $statistics;

	/**
	 * Protection instances.
	 *
	 * @var array
	 */
	private $protections = array();

	/**
	 * Constructor.
	 *
	 * @param array      $settings   Protection settings.
	 * @param Logger     $logger     Logger instance.
	 * @param Statistics $statistics Statistics instance.
	 */
	public function __construct( $settings, $logger, $statistics ) {
		$this->settings   = $settings;
		$this->logger     = $logger;
		$this->statistics = $statistics;

		$this->init_protections();
	}

	/**
	 * Initialize protection methods.
	 */
	private function init_protections() {
		// Honeypot.
		if ( ! empty( $this->settings['honeypot'] ) ) {
			$this->protections['honeypot'] = new Protection\Honeypot();
		}

		// Time check.
		if ( ! empty( $this->settings['time_check'] ) ) {
			$this->protections['time_check'] = new Protection\Time_Check(
				$this->settings['min_time'] ?? 3
			);
		}

		// Rate limiter.
		if ( ! empty( $this->settings['rate_limit'] ) ) {
			$this->protections['rate_limit'] = new Protection\Rate_Limiter(
				$this->settings['rate_limit_count'] ?? 5,
				$this->settings['rate_limit_period'] ?? 60
			);
		}

		// Blacklist.
		if ( ! empty( $this->settings['blacklist'] ) ) {
			$blacklists = get_option( 'jetstop_blacklists', array() );
			$this->protections['blacklist'] = new Protection\Blacklist( $blacklists );
		}

		// Disposable emails.
		if ( ! empty( $this->settings['disposable_emails'] ) ) {
			$this->protections['disposable'] = new Protection\Disposable();
		}

		// Link checker.
		if ( ! empty( $this->settings['link_limit'] ) ) {
			$this->protections['link_checker'] = new Protection\Link_Checker(
				$this->settings['max_links'] ?? 3
			);
		}
	}

	/**
	 * Check submission for spam.
	 *
	 * @param array  $data   Submission data.
	 * @param string $source Source identifier (e.g., 'wp_comments', 'contact_form_7').
	 * @return array Result with 'is_spam' bool and 'reason' if spam.
	 */
	public function check( $data, $source ) {
		$ip_address = $this->get_client_ip();
		$result     = array(
			'is_spam' => false,
			'reason'  => '',
			'message' => '',
		);

		// Allow filtering before checks.
		$pre_check = apply_filters( 'jetstop_pre_check', null, $data, $source );
		if ( null !== $pre_check ) {
			return $pre_check;
		}

		// Run each protection check.
		foreach ( $this->protections as $key => $protection ) {
			$check_result = $protection->check( $data, $ip_address );

			if ( is_wp_error( $check_result ) ) {
				$result = array(
					'is_spam' => true,
					'reason'  => $key,
					'message' => $check_result->get_error_message(),
				);

				// Log and record statistics.
				$this->log_blocked( $source, $key, $ip_address, $data );
				$this->statistics->record( $source, $key );

				// Allow filtering result.
				return apply_filters( 'jetstop_check_result', $result, $data, $source );
			}
		}

		return apply_filters( 'jetstop_check_result', $result, $data, $source );
	}

	/**
	 * Quick check - just honeypot and JS.
	 *
	 * @param array $data POST data.
	 * @return array
	 */
	public function quick_check( $data ) {
		$result = array(
			'is_spam' => false,
			'reason'  => '',
		);

		// Check honeypot.
		if ( isset( $this->protections['honeypot'] ) ) {
			$honeypot_result = $this->protections['honeypot']->check( $data, '' );
			if ( is_wp_error( $honeypot_result ) ) {
				return array(
					'is_spam' => true,
					'reason'  => 'honeypot',
					'message' => $honeypot_result->get_error_message(),
				);
			}
		}

		return $result;
	}

	/**
	 * Log blocked submission.
	 *
	 * @param string $source     Source.
	 * @param string $reason     Reason.
	 * @param string $ip_address IP address.
	 * @param array  $data       Data.
	 */
	private function log_blocked( $source, $reason, $ip_address, $data ) {
		if ( ! get_option( 'jetstop_log_enabled', true ) ) {
			return;
		}

		$this->logger->log( $source, $reason, $ip_address, $data );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public function get_client_ip() {
		$ip = '';

		// Check various headers.
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_REAL_IP',        // Nginx proxy.
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// X-Forwarded-For can contain multiple IPs.
				if ( 'HTTP_X_FORWARDED_FOR' === $header && strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}

				break;
			}
		}

		// Validate IP.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '0.0.0.0';
		}

		return $ip;
	}

	/**
	 * Get protection by key.
	 *
	 * @param string $key Protection key.
	 * @return object|null
	 */
	public function get_protection( $key ) {
		return $this->protections[ $key ] ?? null;
	}

	/**
	 * Get all enabled protections.
	 *
	 * @return array
	 */
	public function get_enabled_protections() {
		return array_keys( $this->protections );
	}

	/**
	 * Get honeypot field name.
	 *
	 * @return string
	 */
	public function get_honeypot_field() {
		return get_option( 'jetstop_honeypot_field', 'website_url' );
	}
}
