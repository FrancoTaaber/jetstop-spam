<?php
/**
 * WordPress Registration integration.
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
 * WP_Registration class.
 */
class WP_Registration extends Abstract_Integration {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	protected $id = 'wp_registration';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = 'WordPress Registration';

	/**
	 * Check if available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return get_option( 'users_can_register' );
	}

	/**
	 * Initialize integration.
	 */
	public function init() {
		// Add honeypot to registration form.
		add_action( 'register_form', array( $this, 'add_honeypot_fields' ) );

		// Validate registration.
		add_filter( 'registration_errors', array( $this, 'validate_registration' ), 10, 3 );
	}

	/**
	 * Add honeypot fields.
	 */
	public function add_honeypot_fields() {
		$this->render_honeypot();
	}

	/**
	 * Validate registration.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login User login.
	 * @param string    $user_email          User email.
	 * @return \WP_Error
	 */
	public function validate_registration( $errors, $sanitized_user_login, $user_email ) {
		$post_data = $this->get_post_data();

		$check_data = array_merge( $post_data, array(
			'user_login' => $sanitized_user_login,
			'user_email' => $user_email,
		) );

		$spam = $this->check_spam( $check_data );

		if ( $spam ) {
			$errors->add( 'jetstop_spam', $spam['message'] );
		}

		return $errors;
	}
}
