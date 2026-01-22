<?php
/**
 * Statistics tracking.
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
 * Statistics class.
 */
class Statistics {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'jetstop_stats';
	}

	/**
	 * Record a blocked submission.
	 *
	 * @param string $source Source identifier.
	 * @param string $reason Block reason.
	 * @return bool
	 */
	public function record( $source, $reason ) {
		global $wpdb;

		$date_key = gmdate( 'Y-m-d' );

		// Try to update existing record.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET count = count + 1 WHERE date_key = %s AND source = %s AND reason = %s",
				$date_key,
				$source,
				$reason
			)
		);

		// If no existing record, insert new one.
		if ( 0 === $updated ) {
			$wpdb->insert(
				$this->table,
				array(
					'date_key' => $date_key,
					'source'   => $source,
					'reason'   => $reason,
					'count'    => 1,
				),
				array( '%s', '%s', '%s', '%d' )
			);
		}

		return true;
	}

	/**
	 * Get summary statistics.
	 *
	 * @param int $days Number of days.
	 * @return array
	 */
	public function get_summary( $days = 30 ) {
		global $wpdb;

		$since = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// Total blocked.
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(count) FROM {$this->table} WHERE date_key >= %s",
				$since
			)
		);

		// By source.
		$by_source_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT source, SUM(count) as total FROM {$this->table} WHERE date_key >= %s GROUP BY source ORDER BY total DESC",
				$since
			),
			ARRAY_A
		);

		$by_source = array();
		foreach ( $by_source_results as $row ) {
			$by_source[ $row['source'] ] = (int) $row['total'];
		}

		// By reason.
		$by_reason_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT reason, SUM(count) as total FROM {$this->table} WHERE date_key >= %s GROUP BY reason ORDER BY total DESC",
				$since
			),
			ARRAY_A
		);

		$by_reason = array();
		foreach ( $by_reason_results as $row ) {
			$by_reason[ $row['reason'] ] = (int) $row['total'];
		}

		// Daily trend.
		$daily = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date_key, SUM(count) as total FROM {$this->table} WHERE date_key >= %s GROUP BY date_key ORDER BY date_key ASC",
				$since
			),
			ARRAY_A
		);

		return array(
			'total'     => (int) $total,
			'by_source' => $by_source,
			'by_reason' => $by_reason,
			'daily'     => $daily,
		);
	}

	/**
	 * Get today's statistics.
	 *
	 * @return array
	 */
	public function get_today() {
		return $this->get_summary( 1 );
	}

	/**
	 * Get this week's statistics.
	 *
	 * @return array
	 */
	public function get_week() {
		return $this->get_summary( 7 );
	}

	/**
	 * Get detailed statistics.
	 *
	 * @param int    $days   Number of days.
	 * @param string $source Filter by source (optional).
	 * @return array
	 */
	public function get_detailed( $days = 30, $source = '' ) {
		global $wpdb;

		$since = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		$where = "date_key >= %s";
		$args  = array( $since );

		if ( ! empty( $source ) ) {
			$where .= " AND source = %s";
			$args[] = $source;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date_key, source, reason, count FROM {$this->table} WHERE {$where} ORDER BY date_key DESC, count DESC",
				$args
			),
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Clean up old statistics.
	 *
	 * @param int $days Delete records older than this.
	 * @return int Number of deleted rows.
	 */
	public function cleanup( $days = 365 ) {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE date_key < %s",
				$cutoff
			)
		);
	}

	/**
	 * Get all-time total.
	 *
	 * @return int
	 */
	public function get_all_time_total() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT SUM(count) FROM {$this->table}" );
	}
}
