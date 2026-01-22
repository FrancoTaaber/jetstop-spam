<?php
/**
 * Blacklist protection.
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
 * Blacklist class.
 *
 * Blocks IPs, emails, and keywords.
 */
class Blacklist {

	/**
	 * Blacklist data.
	 *
	 * @var array
	 */
	private $lists;

	/**
	 * Constructor.
	 *
	 * @param array $lists Blacklist data.
	 */
	public function __construct( $lists = array() ) {
		$this->lists = wp_parse_args(
			$lists,
			array(
				'emails'   => '',
				'ips'      => '',
				'keywords' => '',
			)
		);
	}

	/**
	 * Check against blacklists.
	 *
	 * @param array  $data       Submission data.
	 * @param string $ip_address IP address.
	 * @return true|\WP_Error
	 */
	public function check( $data, $ip_address ) {
		// Check IP.
		$ip_check = $this->check_ip( $ip_address );
		if ( is_wp_error( $ip_check ) ) {
			return $ip_check;
		}

		// Extract email from data.
		$email = $this->extract_email( $data );
		if ( $email ) {
			$email_check = $this->check_email( $email );
			if ( is_wp_error( $email_check ) ) {
				return $email_check;
			}
		}

		// Extract text content and check keywords.
		$content       = $this->extract_content( $data );
		$keyword_check = $this->check_keywords( $content );
		if ( is_wp_error( $keyword_check ) ) {
			return $keyword_check;
		}

		return true;
	}

	/**
	 * Check IP against blacklist.
	 *
	 * @param string $ip_address IP address.
	 * @return true|\WP_Error
	 */
	public function check_ip( $ip_address ) {
		$blacklist = $this->parse_list( $this->lists['ips'] );

		if ( empty( $blacklist ) || empty( $ip_address ) ) {
			return true;
		}

		foreach ( $blacklist as $blocked ) {
			if ( empty( $blocked ) ) {
				continue;
			}

			// Exact match.
			if ( $ip_address === $blocked ) {
				return new \WP_Error( 'ip_blacklisted', __( 'Your IP address has been blocked.', 'jetstop-spam' ) );
			}

			// CIDR match (e.g., 192.168.1.0/24).
			if ( strpos( $blocked, '/' ) !== false && $this->ip_in_cidr( $ip_address, $blocked ) ) {
				return new \WP_Error( 'ip_blacklisted', __( 'Your IP address has been blocked.', 'jetstop-spam' ) );
			}

			// Wildcard match (e.g., 192.168.1.*).
			if ( strpos( $blocked, '*' ) !== false ) {
				$pattern = '/^' . str_replace( array( '.', '*' ), array( '\\.', '\\d+' ), $blocked ) . '$/';
				if ( preg_match( $pattern, $ip_address ) ) {
					return new \WP_Error( 'ip_blacklisted', __( 'Your IP address has been blocked.', 'jetstop-spam' ) );
				}
			}
		}

		return true;
	}

	/**
	 * Check email against blacklist.
	 *
	 * @param string $email Email address.
	 * @return true|\WP_Error
	 */
	public function check_email( $email ) {
		$blacklist = $this->parse_list( $this->lists['emails'] );

		if ( empty( $blacklist ) || empty( $email ) ) {
			return true;
		}

		$email        = strtolower( $email );
		$email_domain = substr( strrchr( $email, '@' ), 1 );

		foreach ( $blacklist as $blocked ) {
			$blocked = strtolower( trim( $blocked ) );

			if ( empty( $blocked ) ) {
				continue;
			}

			// Exact email match.
			if ( $email === $blocked ) {
				return new \WP_Error( 'email_blacklisted', __( 'This email address is not allowed.', 'jetstop-spam' ) );
			}

			// Domain match (@domain.com).
			if ( strpos( $blocked, '@' ) === 0 ) {
				$blocked_domain = substr( $blocked, 1 );
				if ( $email_domain === $blocked_domain ) {
					return new \WP_Error( 'email_blacklisted', __( 'This email domain is not allowed.', 'jetstop-spam' ) );
				}
			}

			// Wildcard match.
			if ( strpos( $blocked, '*' ) !== false ) {
				$pattern = '/^' . str_replace( array( '.', '*' ), array( '\\.', '.*' ), $blocked ) . '$/';
				if ( preg_match( $pattern, $email ) ) {
					return new \WP_Error( 'email_blacklisted', __( 'This email address is not allowed.', 'jetstop-spam' ) );
				}
			}
		}

		return true;
	}

	/**
	 * Check content for blacklisted keywords.
	 *
	 * @param string $content Text content.
	 * @return true|\WP_Error
	 */
	public function check_keywords( $content ) {
		$blacklist = $this->parse_list( $this->lists['keywords'] );

		if ( empty( $blacklist ) || empty( $content ) ) {
			return true;
		}

		$content = strtolower( $content );

		foreach ( $blacklist as $keyword ) {
			$keyword = strtolower( trim( $keyword ) );

			if ( empty( $keyword ) ) {
				continue;
			}

			// Support regex patterns (wrapped in //).
			if ( preg_match( '/^\/.*\/$/', $keyword ) ) {
				$pattern = substr( $keyword, 1, -1 );
				if ( @preg_match( '/' . $pattern . '/i', $content ) ) {
					return new \WP_Error( 'keyword_blacklisted', __( 'Your submission contains prohibited content.', 'jetstop-spam' ) );
				}
			} elseif ( strpos( $content, $keyword ) !== false ) {
				// Simple string match.
				return new \WP_Error( 'keyword_blacklisted', __( 'Your submission contains prohibited content.', 'jetstop-spam' ) );
			}
		}

		return true;
	}

	/**
	 * Check if IP is in CIDR range.
	 *
	 * @param string $ip   IP address.
	 * @param string $cidr CIDR notation.
	 * @return bool
	 */
	private function ip_in_cidr( $ip, $cidr ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return false;
		}

		list( $subnet, $bits ) = explode( '/', $cidr );

		$ip     = ip2long( $ip );
		$subnet = ip2long( $subnet );
		$mask   = -1 << ( 32 - (int) $bits );
		$subnet &= $mask;

		return ( $ip & $mask ) === $subnet;
	}

	/**
	 * Parse newline-separated list.
	 *
	 * @param string $list List string.
	 * @return array
	 */
	private function parse_list( $list ) {
		if ( empty( $list ) ) {
			return array();
		}

		$items = preg_split( '/[\r\n]+/', $list );
		$items = array_map( 'trim', $items );
		$items = array_filter( $items );

		return $items;
	}

	/**
	 * Extract email from data.
	 *
	 * @param array $data Submission data.
	 * @return string|null
	 */
	private function extract_email( $data ) {
		// Common email field names.
		$email_fields = array( 'email', 'user_email', 'email-1', 'your-email', 'customer_email', 'billing_email' );

		foreach ( $data as $key => $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			$key_lower = strtolower( $key );

			// Check if field name contains 'email'.
			if ( strpos( $key_lower, 'email' ) !== false ) {
				$email = sanitize_email( $value );
				if ( is_email( $email ) ) {
					return $email;
				}
			}

			// Check common field names.
			if ( in_array( $key_lower, $email_fields, true ) ) {
				$email = sanitize_email( $value );
				if ( is_email( $email ) ) {
					return $email;
				}
			}
		}

		return null;
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
}
