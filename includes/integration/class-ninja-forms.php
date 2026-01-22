<?php
/**
 * Ninja Forms integration.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ninja_Forms extends Abstract_Integration {

	protected $id   = 'ninja_forms';
	protected $name = 'Ninja Forms';

	public function is_available() {
		return class_exists( 'Ninja_Forms' );
	}

	public function init() {
		add_filter( 'ninja_forms_submit_data', array( $this, 'validate' ), 1 );
	}

	public function validate( $form_data ) {
		$spam = $this->check_spam( $form_data['fields'] ?? array() );

		if ( $spam ) {
			$form_data['errors']['form']['jetstop'] = $spam['message'];
		}

		return $form_data;
	}
}
