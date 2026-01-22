<?php
/**
 * Blocked submission logger.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class.
 */
class Logger {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Sensitive field patterns to redact.
	 *
	 * @var array
	 */
	private $sensitive_patterns = array(
		'password',
		'pass',
		'pwd',
		'secret',
		'credit_card',
		'card_number',
		'cvv',
		'cvc',
		'ssn',
		'social_security',
		'pin',
		'token',
		'api_key',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'jetstop_log';
	}

	/**
	 * Log a blocked submission.
	 *
	 * @param string $source     Source identifier.
	 * @param string $reason     Block reason.
	 * @param string $ip_address IP address.
	 * @param array  $data       Submission data.
	 * @return int|false Insert ID or false.
	 */
	public function log( $source, $reason, $ip_address, $data = array() ) {
		global $wpdb;

		// Sanitize and redact sensitive data.
		$sanitized_data = $this->sanitize_data( $data );

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		$result = $wpdb->insert(
			$this->table,
			array(
				'source'     => sanitize_key( $source ),
				'reason'     => sanitize_key( $reason ),
				'ip_address' => sanitize_text_field( $ip_address ),
				'user_agent' => $user_agent,
				'data'       => wp_json_encode( $sanitized_data ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get log entries.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_entries( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'source'   => '',
			'reason'   => '',
			'ip'       => '',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args   = wp_parse_args( $args, $defaults );
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['source'] ) ) {
			$where[]  = 'source = %s';
			$values[] = $args['source'];
		}

		if ( ! empty( $args['reason'] ) ) {
			$where[]  = 'reason = %s';
			$values[] = $args['reason'];
		}

		if ( ! empty( $args['ip'] ) ) {
			$where[]  = 'ip_address LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['ip'] ) . '%';
		}

		$where_clause = implode( ' AND ', $where );

		$allowed_orderby = array( 'created_at', 'source', 'reason', 'ip_address' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$limit  = (int) $args['per_page'];

		$sql = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT {$offset}, {$limit}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Decode JSON data.
		foreach ( $results as &$row ) {
			$row['data'] = json_decode( $row['data'], true );
		}

		return $results;
	}

	/**
	 * Get total count.
	 *
	 * @param array $args Query arguments.
	 * @return int
	 */
	public function get_total( $args = array() ) {
		global $wpdb;

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['source'] ) ) {
			$where[]  = 'source = %s';
			$values[] = $args['source'];
		}

		if ( ! empty( $args['reason'] ) ) {
			$where[]  = 'reason = %s';
			$values[] = $args['reason'];
		}

		if ( ! empty( $args['ip'] ) ) {
			$where[]  = 'ip_address LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['ip'] ) . '%';
		}

		$where_clause = implode( ' AND ', $where );
		$sql          = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get top blocked IPs.
	 *
	 * @param int $limit Number of results.
	 * @param int $days  Number of days.
	 * @return array
	 */
	public function get_top_ips( $limit = 10, $days = 30 ) {
		global $wpdb;

		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ip_address, COUNT(*) as count FROM {$this->table} WHERE created_at >= %s GROUP BY ip_address ORDER BY count DESC LIMIT %d",
				$since,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Delete a log entry.
	 *
	 * @param int $id Entry ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->table,
			array( 'id' => (int) $id ),
			array( '%d' )
		);
	}

	/**
	 * Clear all entries.
	 *
	 * @return int|false
	 */
	public function clear_all() {
		global $wpdb;

		return $wpdb->query( "TRUNCATE TABLE {$this->table}" );
	}

	/**
	 * Cleanup old entries.
	 *
	 * @param int $days Delete entries older than this.
	 * @return int Number of deleted rows.
	 */
	public function cleanup( $days = 90 ) {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE created_at < %s",
				$cutoff
			)
		);
	}

	/**
	 * Sanitize and redact sensitive data.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	private function sanitize_data( $data ) {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			// Skip internal Jetstop fields.
			if ( strpos( $key, 'jetstop_' ) === 0 ) {
				continue;
			}

			// Check for sensitive fields.
			$key_lower = strtolower( $key );
			foreach ( $this->sensitive_patterns as $pattern ) {
				if ( strpos( $key_lower, $pattern ) !== false ) {
					$sanitized[ $key ] = '[REDACTED]';
					continue 2;
				}
			}

			// Sanitize value.
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( substr( $value, 0, 500 ) );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = array_map( 'sanitize_text_field', array_slice( $value, 0, 10 ) );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}
}
