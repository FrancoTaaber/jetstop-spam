<?php
/**
 * Gravity Forms integration.
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
 * Gravity_Forms class.
 */
class Gravity_Forms extends Abstract_Integration {

	protected $id   = 'gravity_forms';
	protected $name = 'Gravity Forms';

	public function is_available() {
		return class_exists( 'GFForms' );
	}

	public function init() {
		// Add honeypot.
		add_filter( 'gform_form_tag', array( $this, 'add_honeypot' ), 10, 2 );

		// Validate submission.
		add_filter( 'gform_validation', array( $this, 'validate_submission' ), 1 );
	}

	public function add_honeypot( $form_tag, $form ) {
		$honeypot = $this->spam_engine->get_protection( 'honeypot' );
		if ( $honeypot ) {
			$form_tag .= $honeypot->render_html();
		}
		return $form_tag;
	}

	public function validate_submission( $validation_result ) {
		$post_data = $this->get_post_data();
		$spam      = $this->check_spam( $post_data );

		if ( $spam ) {
			$validation_result['is_valid'] = false;
			$form                          = $validation_result['form'];

			if ( ! empty( $form['fields'] ) ) {
				$form['fields'][0]->failed_validation  = true;
				$form['fields'][0]->validation_message = $spam['message'];
				$validation_result['form']             = $form;
			}
		}

		return $validation_result;
	}
}
