<?php
/**
 * Time-based check protection.
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
 * Time_Check class.
 *
 * Blocks submissions that happen too quickly (bots submit instantly).
 */
class Time_Check {

	/**
	 * Minimum time in seconds.
	 *
	 * @var int
	 */
	private $min_time;

	/**
	 * Constructor.
	 *
	 * @param int $min_time Minimum time in seconds.
	 */
	public function __construct( $min_time = 3 ) {
		$this->min_time = max( 1, (int) $min_time );
	}

	/**
	 * Check submission timing.
	 *
	 * @param array  $data       Submission data.
	 * @param string $ip_address IP address.
	 * @return true|\WP_Error
	 */
	public function check( $data, $ip_address ) {
		// Look for timestamp in various possible field names.
		$timestamp_fields = array( 'jetstop_ts', 'jetstop_timestamp', '_jetstop_ts' );
		$form_load_time   = 0;

		foreach ( $timestamp_fields as $field ) {
			if ( isset( $data[ $field ] ) && ! empty( $data[ $field ] ) ) {
				$decoded = base64_decode( $data[ $field ], true );
				if ( false !== $decoded && is_numeric( $decoded ) ) {
					$form_load_time = (int) $decoded;
					break;
				}
			}
		}

		// If no timestamp, skip check (might be form without JS).
		if ( 0 === $form_load_time ) {
			return true;
		}

		$submission_time = time();
		$time_spent      = $submission_time - $form_load_time;

		// Too fast = bot.
		if ( $time_spent < $this->min_time ) {
			return new \WP_Error(
				'too_fast',
				sprintf(
					/* translators: %d: minimum seconds */
					__( 'Please take at least %d seconds to fill out the form.', 'jetstop-spam' ),
					$this->min_time
				)
			);
		}

		// Suspiciously old timestamp (more than 1 hour) - might be replay attack.
		if ( $time_spent > 3600 ) {
			return new \WP_Error(
				'timestamp_expired',
				__( 'Your session has expired. Please refresh the page and try again.', 'jetstop-spam' )
			);
		}

		return true;
	}

	/**
	 * Get minimum time.
	 *
	 * @return int
	 */
	public function get_min_time() {
		return $this->min_time;
	}
}
