<?php
/**
 * bbPress integration.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBPress extends Abstract_Integration {

	protected $id   = 'bbpress';
	protected $name = 'bbPress';

	public function is_available() {
		return class_exists( 'bbPress' );
	}

	public function init() {
		// Topic validation.
		add_action( 'bbp_new_topic_pre_extras', array( $this, 'validate_topic' ) );
		
		// Reply validation.
		add_action( 'bbp_new_reply_pre_extras', array( $this, 'validate_reply' ) );
	}

	public function validate_topic() {
		if ( current_user_can( 'moderate' ) ) {
			return;
		}

		$post_data = $this->get_post_data();
		$spam      = $this->check_spam( $post_data );

		if ( $spam ) {
			bbp_add_error( 'jetstop_spam', $spam['message'] );
		}
	}

	public function validate_reply() {
		$this->validate_topic();
	}
}
