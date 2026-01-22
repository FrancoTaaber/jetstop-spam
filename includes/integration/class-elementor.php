<?php
/**
 * Elementor Forms integration.
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
 * Elementor class.
 */
class Elementor extends Abstract_Integration {

	protected $id   = 'elementor';
	protected $name = 'Elementor Forms';

	public function is_available() {
		return defined( 'ELEMENTOR_VERSION' ) && defined( 'ELEMENTOR_PRO_VERSION' );
	}

	public function init() {
		// Validate submission.
		add_action( 'elementor_pro/forms/validation', array( $this, 'validate_submission' ), 10, 2 );
	}

	public function validate_submission( $record, $ajax_handler ) {
		$post_data = $this->get_post_data();
		$spam      = $this->check_spam( $post_data );

		if ( $spam ) {
			$ajax_handler->add_error( 'jetstop_spam', $spam['message'] );
		}
	}
}
