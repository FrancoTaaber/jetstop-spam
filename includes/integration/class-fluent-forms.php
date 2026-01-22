<?php
/**
 * Fluent Forms integration.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fluent_Forms extends Abstract_Integration {

	protected $id   = 'fluent_forms';
	protected $name = 'Fluent Forms';

	public function is_available() {
		return defined( 'FLUENTFORM_VERSION' );
	}

	public function init() {
		add_filter( 'fluentform/validate_input_item_email', array( $this, 'validate' ), 10, 5 );
		add_action( 'fluentform/before_insert_submission', array( $this, 'before_insert' ), 10, 3 );
	}

	public function validate( $error, $field, $form_data, $fields, $form ) {
		$spam = $this->check_spam( $form_data );
		if ( $spam ) {
			$error = array( $spam['message'] );
		}
		return $error;
	}

	public function before_insert( $insert_data, $data, $form ) {
		$spam = $this->check_spam( $data );
		if ( $spam ) {
			wp_send_json_error( array( 'message' => $spam['message'] ), 422 );
		}
	}
}
