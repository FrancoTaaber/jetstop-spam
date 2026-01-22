<?php
/**
 * Abstract integration class.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Integration;

use Jetstop_Spam\Spam_Engine;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract_Integration class.
 */
abstract class Abstract_Integration {

	/**
	 * Integration identifier.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Spam engine instance.
	 *
	 * @var Spam_Engine
	 */
	protected $spam_engine;

	/**
	 * Constructor.
	 *
	 * @param Spam_Engine $spam_engine Spam engine instance.
	 */
	public function __construct( Spam_Engine $spam_engine ) {
		$this->spam_engine = $spam_engine;
	}

	/**
	 * Check if the integration is available.
	 *
	 * @return bool
	 */
	abstract public function is_available();

	/**
	 * Initialize the integration.
	 *
	 * @return void
	 */
	abstract public function init();

	/**
	 * Get integration ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get integration name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Check submission and return error if spam.
	 *
	 * @param array $data Submission data.
	 * @return array|null Null if not spam, error array if spam.
	 */
	protected function check_spam( $data ) {
		$result = $this->spam_engine->check( $data, $this->id );

		if ( $result['is_spam'] ) {
			return array(
				'message' => $result['message'],
				'reason'  => $result['reason'],
			);
		}

		return null;
	}

	/**
	 * Get default error message.
	 *
	 * @return string
	 */
	protected function get_default_error() {
		return __( 'Your submission has been flagged as spam.', 'jetstop-spam' );
	}

	/**
	 * Render honeypot fields.
	 *
	 * @return void
	 */
	protected function render_honeypot() {
		$honeypot = $this->spam_engine->get_protection( 'honeypot' );
		if ( $honeypot ) {
			echo $honeypot->render_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Get POST data.
	 *
	 * @return array
	 */
	protected function get_post_data() {
		return $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
}
