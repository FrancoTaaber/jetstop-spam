<?php
/**
 * Link checker protection.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Protection;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Link_Checker class.
 *
 * Limits the number of links in submissions.
 */
class Link_Checker {

	/**
	 * Maximum links allowed.
	 *
	 * @var int
	 */
	private $max_links;

	/**
	 * Constructor.
	 *
	 * @param int $max_links Maximum links allowed.
	 */
	public function __construct( $max_links = 3 ) {
		$this->max_links = max( 0, (int) $max_links );
	}

	/**
	 * Check link count.
	 *
	 * @param array  $data       Submission data.
	 * @param string $ip_address IP address.
	 * @return true|\WP_Error
	 */
	public function check( $data, $ip_address ) {
		// Skip if no limit.
		if ( 0 === $this->max_links ) {
			return true;
		}

		$content    = $this->extract_content( $data );
		$link_count = $this->count_links( $content );

		if ( $link_count > $this->max_links ) {
			return new \WP_Error(
				'too_many_links',
				sprintf(
					/* translators: %d: maximum allowed links */
					__( 'Too many links in your submission. Maximum allowed: %d', 'jetstop-spam' ),
					$this->max_links
				)
			);
		}

		return true;
	}

	/**
	 * Count links in content.
	 *
	 * @param string $content Text content.
	 * @return int
	 */
	public function count_links( $content ) {
		// Match URLs.
		$url_pattern = '/https?:\/\/[^\s<>"\']+/i';
		preg_match_all( $url_pattern, $content, $url_matches );

		// Match HTML links.
		$html_pattern = '/<a\s[^>]*href\s*=\s*["\'][^"\']+["\']/i';
		preg_match_all( $html_pattern, $content, $html_matches );

		// Match BBCode links.
		$bbcode_pattern = '/\[url[^\]]*\]/i';
		preg_match_all( $bbcode_pattern, $content, $bbcode_matches );

		// Count unique links.
		$all_links = array_merge(
			$url_matches[0] ?? array(),
			$html_matches[0] ?? array(),
			$bbcode_matches[0] ?? array()
		);

		return count( array_unique( $all_links ) );
	}

	/**
	 * Extract text content from data.
	 *
	 * @param array $data Submission data.
	 * @return string
	 */
	private function extract_content( $data ) {
		$content = array();

		foreach ( $data as $key => $value ) {
			// Skip internal fields.
			if ( strpos( $key, 'jetstop_' ) === 0 || strpos( $key, '_wp' ) === 0 ) {
				continue;
			}

			if ( is_string( $value ) ) {
				$content[] = $value;
			} elseif ( is_array( $value ) ) {
				$content[] = implode( ' ', array_filter( $value, 'is_string' ) );
			}
		}

		return implode( ' ', $content );
	}

	/**
	 * Get maximum links.
	 *
	 * @return int
	 */
	public function get_max_links() {
		return $this->max_links;
	}
}
