<?php
/**
 * Logs view.
 *
 * @package Jetstop_Spam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Jetstop_Spam\Admin;

$sources = Admin::get_integrations_status();
?>

<div class="wrap jetstop-wrap">
	<h1><?php esc_html_e( 'Blocked Submissions Log', 'jetstop-spam' ); ?></h1>

	<div class="jetstop-log-controls">
		<select id="jetstop-filter-source">
			<option value=""><?php esc_html_e( 'All Sources', 'jetstop-spam' ); ?></option>
			<?php foreach ( $sources as $key => $source ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $source['name'] ); ?></option>
			<?php endforeach; ?>
		</select>

		<select id="jetstop-filter-reason">
			<option value=""><?php esc_html_e( 'All Reasons', 'jetstop-spam' ); ?></option>
			<option value="honeypot"><?php esc_html_e( 'Honeypot', 'jetstop-spam' ); ?></option>
			<option value="time_check"><?php esc_html_e( 'Too Fast', 'jetstop-spam' ); ?></option>
			<option value="rate_limit"><?php esc_html_e( 'Rate Limited', 'jetstop-spam' ); ?></option>
			<option value="blacklist"><?php esc_html_e( 'Blacklist', 'jetstop-spam' ); ?></option>
			<option value="disposable"><?php esc_html_e( 'Disposable Email', 'jetstop-spam' ); ?></option>
			<option value="link_checker"><?php esc_html_e( 'Too Many Links', 'jetstop-spam' ); ?></option>
		</select>

		<input type="text" id="jetstop-filter-ip" placeholder="<?php esc_attr_e( 'Filter by IP...', 'jetstop-spam' ); ?>">

		<button type="button" class="button" id="jetstop-refresh-log">
			<?php esc_html_e( 'Refresh', 'jetstop-spam' ); ?>
		</button>

		<button type="button" class="button" id="jetstop-clear-log">
			<?php esc_html_e( 'Clear All', 'jetstop-spam' ); ?>
		</button>
	</div>

	<table class="wp-list-table widefat fixed striped" id="jetstop-log-table">
		<thead>
			<tr>
				<th class="column-date"><?php esc_html_e( 'Date', 'jetstop-spam' ); ?></th>
				<th class="column-source"><?php esc_html_e( 'Source', 'jetstop-spam' ); ?></th>
				<th class="column-reason"><?php esc_html_e( 'Reason', 'jetstop-spam' ); ?></th>
				<th class="column-ip"><?php esc_html_e( 'IP Address', 'jetstop-spam' ); ?></th>
				<th class="column-actions"><?php esc_html_e( 'Actions', 'jetstop-spam' ); ?></th>
			</tr>
		</thead>
		<tbody id="jetstop-log-body">
			<tr><td colspan="5"><?php esc_html_e( 'Loading...', 'jetstop-spam' ); ?></td></tr>
		</tbody>
	</table>

	<div class="jetstop-pagination" id="jetstop-pagination"></div>
</div>
