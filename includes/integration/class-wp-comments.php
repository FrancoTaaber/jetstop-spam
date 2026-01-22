<?php
/**
 * WordPress Comments integration.
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
 * WP_Comments class.
 */
class WP_Comments extends Abstract_Integration {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	protected $id = 'wp_comments';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = 'WordPress Comments';

	/**
	 * Check if available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return true; // Always available in WordPress.
	}

	/**
	 * Initialize integration.
	 */
	public function init() {
		// Add honeypot to comment form.
		add_action( 'comment_form_after_fields', array( $this, 'add_honeypot_fields' ) );
		add_action( 'comment_form_logged_in_after', array( $this, 'add_honeypot_fields' ) );

		// Validate comment submission.
		add_filter( 'preprocess_comment', array( $this, 'validate_comment' ), 1 );
	}

	/**
	 * Add honeypot fields to comment form.
	 */
	public function add_honeypot_fields() {
		$this->render_honeypot();
	}

	/**
	 * Validate comment submission.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public function validate_comment( $commentdata ) {
		// Skip for admins and logged-in users with capability.
		if ( current_user_can( 'moderate_comments' ) ) {
			return $commentdata;
		}

		// Skip for trackbacks/pingbacks.
		if ( ! empty( $commentdata['comment_type'] ) && 'comment' !== $commentdata['comment_type'] ) {
			return $commentdata;
		}

		$post_data = $this->get_post_data();

		// Add comment data to check.
		$check_data = array_merge( $post_data, array(
			'comment_author'       => $commentdata['comment_author'] ?? '',
			'comment_author_email' => $commentdata['comment_author_email'] ?? '',
			'comment_author_url'   => $commentdata['comment_author_url'] ?? '',
			'comment_content'      => $commentdata['comment_content'] ?? '',
		) );

		$spam = $this->check_spam( $check_data );

		if ( $spam ) {
			wp_die(
				esc_html( $spam['message'] ),
				esc_html__( 'Comment Blocked', 'jetstop-spam' ),
				array( 'response' => 403, 'back_link' => true )
			);
		}

		return $commentdata;
	}
}
