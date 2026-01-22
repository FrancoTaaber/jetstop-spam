<?php
/**
 * Honeypot protection.
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
 * Honeypot class.
 *
 * Uses JavaScript to inject honeypot field - bots don't execute JS.
 */
class Honeypot {

	/**
	 * Honeypot field name.
	 *
	 * @var string
	 */
	private $field_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->field_name = get_option( 'jetstop_honeypot_field', 'website_url' );
	}

	/**
	 * Check for honeypot spam.
	 *
	 * @param array  $data       Submission data.
	 * @param string $ip_address IP address.
	 * @return true|\WP_Error
	 */
	public function check( $data, $ip_address ) {
		// Check 1: If honeypot field doesn't exist, JS wasn't executed = bot.
		if ( ! isset( $data[ $this->field_name ] ) && ! isset( $data['jetstop_hp'] ) ) {
			return new \WP_Error(
				'honeypot_missing',
				__( 'Security validation failed. Please ensure JavaScript is enabled.', 'jetstop-spam' )
			);
		}

		// Check 2: If honeypot field has value = bot filled it.
		$honeypot_value = '';
		if ( isset( $data[ $this->field_name ] ) ) {
			$honeypot_value = $data[ $this->field_name ];
		} elseif ( isset( $data['jetstop_hp'] ) ) {
			$honeypot_value = $data['jetstop_hp'];
		}

		if ( ! empty( $honeypot_value ) ) {
			return new \WP_Error(
				'honeypot_filled',
				__( 'Your submission has been flagged as spam.', 'jetstop-spam' )
			);
		}

		// Check 3: Verify JS token if present.
		if ( isset( $data['jetstop_js'] ) && 'verified' !== $data['jetstop_js'] ) {
			return new \WP_Error(
				'js_verification_failed',
				__( 'JavaScript verification failed.', 'jetstop-spam' )
			);
		}

		return true;
	}

	/**
	 * Get honeypot field name.
	 *
	 * @return string
	 */
	public function get_field_name() {
		return $this->field_name;
	}

	/**
	 * Render honeypot HTML (for forms that don't use JS injection).
	 *
	 * @return string
	 */
	public function render_html() {
		$html = sprintf(
			'<div class="jetstop-hp-wrap" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">
				<label for="jetstop_%1$s">%2$s</label>
				<input type="text" name="%1$s" id="jetstop_%1$s" value="" tabindex="-1" autocomplete="off" />
			</div>
			<input type="hidden" name="jetstop_js" value="" class="jetstop-js-check" />
			<input type="hidden" name="jetstop_ts" value="%3$s" />',
			esc_attr( $this->field_name ),
			esc_html__( 'Leave empty', 'jetstop-spam' ),
			esc_attr( base64_encode( (string) time() ) )
		);

		return $html;
	}
}
