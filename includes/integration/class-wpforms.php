<?php
/**
 * WPForms integration.
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
 * WPForms class.
 */
class WPForms extends Abstract_Integration {

	protected $id   = 'wpforms';
	protected $name = 'WPForms';

	public function is_available() {
		return defined( 'WPFORMS_VERSION' );
	}

	public function init() {
		// Add honeypot.
		add_action( 'wpforms_frontend_output_before_fields', array( $this, 'add_honeypot' ) );

		// Validate submission.
		add_filter( 'wpforms_process_before_form_data', array( $this, 'validate_submission' ), 10, 2 );
	}

	public function add_honeypot( $form_data ) {
		$this->render_honeypot();
	}

	public function validate_submission( $form_data, $entry ) {
		$post_data = $this->get_post_data();
		$spam      = $this->check_spam( $post_data );

		if ( $spam ) {
			wpforms()->process->errors[ $form_data['id'] ]['header'] = $spam['message'];
		}

		return $form_data;
	}
}
