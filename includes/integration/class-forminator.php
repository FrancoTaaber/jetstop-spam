<?php
/**
 * Forminator integration.
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
 * Forminator class.
 */
class Forminator extends Abstract_Integration {

	protected $id   = 'forminator';
	protected $name = 'Forminator';

	public function is_available() {
		return class_exists( 'Forminator' );
	}

	public function init() {
		// Add honeypot.
		add_action( 'forminator_before_form_render', array( $this, 'add_honeypot' ) );

		// Validate submission.
		add_filter( 'forminator_custom_form_submit_errors', array( $this, 'validate_submission' ), 1, 3 );
	}

	public function add_honeypot( $form_id ) {
		$this->render_honeypot();
	}

	public function validate_submission( $errors, $form_id, $field_data ) {
		$post_data = $this->get_post_data();
		$spam      = $this->check_spam( $post_data );

		if ( $spam ) {
			$errors[] = array(
				'field'   => '',
				'message' => $spam['message'],
			);
		}

		return $errors;
	}
}
