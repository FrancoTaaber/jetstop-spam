<?php
/**
 * WordPress Login integration.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Integration;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Login class.
 */
class WP_Login extends Abstract_Integration {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	protected $id = 'wp_login';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = 'WordPress Login';

	/**
	 * Check if available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Initialize integration.
	 */
	public function init() {
		// Add honeypot to login form.
		add_action( 'login_form', array( $this, 'add_honeypot_fields' ) );

		// Validate login (rate limiting mainly).
		add_filter( 'authenticate', array( $this, 'validate_login' ), 5, 3 );
	}

	/**
	 * Add honeypot fields.
	 */
	public function add_honeypot_fields() {
		$this->render_honeypot();
	}

	/**
	 * Validate login attempt.
	 *
	 * @param null|\WP_User|\WP_Error $user     User object or error.
	 * @param string                  $username Username.
	 * @param string                  $password Password.
	 * @return null|\WP_User|\WP_Error
	 */
	public function validate_login( $user, $username, $password ) {
		// Skip if already authenticated or has error.
		if ( $user instanceof \WP_User || empty( $username ) ) {
			return $user;
		}

		$post_data = $this->get_post_data();

		// Only check honeypot and rate limit for login (not blacklists).
		$spam = $this->spam_engine->quick_check( $post_data );

		if ( $spam['is_spam'] ) {
			return new \WP_Error( 'jetstop_spam', $spam['message'] ?? $this->get_default_error() );
		}

		// Check rate limit specifically.
		$rate_limiter = $this->spam_engine->get_protection( 'rate_limit' );
		if ( $rate_limiter ) {
			$ip     = $this->spam_engine->get_client_ip();
			$result = $rate_limiter->check( array(), $ip );

			if ( is_wp_error( $result ) ) {
				return new \WP_Error( 'jetstop_rate_limit', $result->get_error_message() );
			}
		}

		return $user;
	}
}
