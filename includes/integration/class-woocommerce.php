<?php
/**
 * WooCommerce integration.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerce extends Abstract_Integration {

	protected $id   = 'woocommerce';
	protected $name = 'WooCommerce';

	public function is_available() {
		return class_exists( 'WooCommerce' );
	}

	public function init() {
		// Registration validation.
		add_filter( 'woocommerce_registration_errors', array( $this, 'validate_registration' ), 10, 3 );

		// Product review validation.
		add_filter( 'preprocess_comment', array( $this, 'validate_review' ), 1 );
	}

	public function validate_registration( $errors, $username, $email ) {
		$post_data = $this->get_post_data();

		$check_data = array_merge( $post_data, array(
			'user_login' => $username,
			'user_email' => $email,
		) );

		$spam = $this->check_spam( $check_data );

		if ( $spam ) {
			$errors->add( 'jetstop_spam', $spam['message'] );
		}

		return $errors;
	}

	public function validate_review( $commentdata ) {
		if ( empty( $commentdata['comment_type'] ) || 'review' !== $commentdata['comment_type'] ) {
			return $commentdata;
		}

		$post_data = $this->get_post_data();
		$spam      = $this->check_spam( $post_data );

		if ( $spam ) {
			wp_die( esc_html( $spam['message'] ), '', array( 'response' => 403, 'back_link' => true ) );
		}

		return $commentdata;
	}
}
