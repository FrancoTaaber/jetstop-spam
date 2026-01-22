<?php
/**
 * Formidable Forms integration.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Formidable extends Abstract_Integration {

	protected $id   = 'formidable';
	protected $name = 'Formidable Forms';

	public function is_available() {
		return class_exists( 'FrmAppHelper' );
	}

	public function init() {
		add_filter( 'frm_validate_entry', array( $this, 'validate' ), 10, 2 );
	}

	public function validate( $errors, $values ) {
		$spam = $this->check_spam( $values );

		if ( $spam ) {
			$errors['jetstop_spam'] = $spam['message'];
		}

		return $errors;
	}
}
