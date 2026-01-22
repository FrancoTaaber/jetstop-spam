<?php
/**
 * Rate limiter protection.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Protection;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate_Limiter class.
 *
 * Limits submissions per IP address.
 */
class Rate_Limiter {

	/**
	 * Maximum submissions allowed.
	 *
	 * @var int
	 */
	private $max_count;

	/**
	 * Time period in seconds.
	 *
	 * @var int
	 */
	private $period;

	/**
	 * Constructor.
	 *
	 * @param int $max_count Maximum submissions.
	 * @param int $period    Time period in seconds.
	 */
	public function __construct( $max_count = 5, $period = 60 ) {
		$this->max_count = max( 1, (int) $max_count );
		$this->period    = max( 10, (int) $period );
	}

	/**
	 * Check rate limit.
	 *
	 * @param array  $data       Submission data.
	 * @param string $ip_address IP address.
	 * @return true|\WP_Error
	 */
	public function check( $data, $ip_address ) {
		if ( empty( $ip_address ) || '0.0.0.0' === $ip_address ) {
			return true;
		}

		$transient_key = 'jetstop_rate_' . md5( $ip_address );
		$current_count = (int) get_transient( $transient_key );

		if ( $current_count >= $this->max_count ) {
			$wait_time = $this->get_wait_time( $ip_address );

			return new \WP_Error(
				'rate_limited',
				sprintf(
					/* translators: %d: seconds to wait */
					__( 'Too many submissions. Please wait %d seconds and try again.', 'jetstop-spam' ),
					$wait_time
				)
			);
		}

		// Increment counter.
		if ( 0 === $current_count ) {
			set_transient( $transient_key, 1, $this->period );
		} else {
			// Get remaining TTL and update.
			$ttl = $this->get_transient_ttl( $transient_key );
			if ( $ttl > 0 ) {
				set_transient( $transient_key, $current_count + 1, $ttl );
			} else {
				set_transient( $transient_key, 1, $this->period );
			}
		}

		return true;
	}

	/**
	 * Get remaining wait time for an IP.
	 *
	 * @param string $ip_address IP address.
	 * @return int Seconds to wait.
	 */
	private function get_wait_time( $ip_address ) {
		$transient_key = 'jetstop_rate_' . md5( $ip_address );
		return $this->get_transient_ttl( $transient_key );
	}

	/**
	 * Get transient TTL.
	 *
	 * @param string $key Transient key.
	 * @return int TTL in seconds.
	 */
	private function get_transient_ttl( $key ) {
		$timeout = get_option( '_transient_timeout_' . $key );

		if ( false === $timeout ) {
			return 0;
		}

		$ttl = (int) $timeout - time();
		return max( 0, $ttl );
	}

	/**
	 * Reset rate limit for IP.
	 *
	 * @param string $ip_address IP address.
	 * @return bool
	 */
	public function reset( $ip_address ) {
		$transient_key = 'jetstop_rate_' . md5( $ip_address );
		return delete_transient( $transient_key );
	}

	/**
	 * Get current count for IP.
	 *
	 * @param string $ip_address IP address.
	 * @return int
	 */
	public function get_count( $ip_address ) {
		$transient_key = 'jetstop_rate_' . md5( $ip_address );
		return (int) get_transient( $transient_key );
	}
}
