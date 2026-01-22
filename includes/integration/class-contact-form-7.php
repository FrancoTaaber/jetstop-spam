<?php
/**
 * Contact Form 7 integration.
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
 * Contact_Form_7 class.
 */
class Contact_Form_7 extends Abstract_Integration {

	protected $id   = 'contact_form_7';
	protected $name = 'Contact Form 7';

	public function is_available() {
		return defined( 'WPCF7_VERSION' );
	}

	public function init() {
		// Add honeypot to forms.
		add_filter( 'wpcf7_form_elements', array( $this, 'add_honeypot' ) );

		// Validate submission.
		add_filter( 'wpcf7_validate', array( $this, 'validate_submission' ), 1, 2 );
	}

	public function add_honeypot( $content ) {
		$honeypot = $this->spam_engine->get_protection( 'honeypot' );
		if ( $honeypot ) {
			$content .= $honeypot->render_html();
		}
		return $content;
	}

	public function validate_submission( $result, $tags ) {
		$post_data = $this->get_post_data();
		$spam      = $this->check_spam( $post_data );

		if ( $spam ) {
			$result->invalidate( '', $spam['message'] );
		}

		return $result;
	}
}
