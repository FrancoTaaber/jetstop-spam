<?php
/**
 * Settings view.
 *
 * @package Jetstop_Spam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Jetstop_Spam\Admin;

$protection   = get_option( 'jetstop_protection', array() );
$integrations = get_option( 'jetstop_integrations', array() );
$log_enabled  = get_option( 'jetstop_log_enabled', true );
$all_integrations = Admin::get_integrations_status();
?>

<div class="wrap jetstop-wrap">
	<h1><?php esc_html_e( 'Jetstop Spam Settings', 'jetstop-spam' ); ?></h1>

	<form id="jetstop-settings-form" class="jetstop-form">
		<div class="jetstop-card">
			<h2><?php esc_html_e( 'Protection Methods', 'jetstop-spam' ); ?></h2>
			
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Honeypot', 'jetstop-spam' ); ?></th>
					<td>
						<label><input type="checkbox" name="honeypot" value="1" <?php checked( ! empty( $protection['honeypot'] ) ); ?>>
						<?php esc_html_e( 'JavaScript-based honeypot (recommended)', 'jetstop-spam' ); ?></label>
						<p class="description"><?php esc_html_e( 'Bots cannot execute JavaScript, making this highly effective.', 'jetstop-spam' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Time Check', 'jetstop-spam' ); ?></th>
					<td>
						<label><input type="checkbox" name="time_check" value="1" <?php checked( ! empty( $protection['time_check'] ) ); ?>>
						<?php esc_html_e( 'Block fast submissions', 'jetstop-spam' ); ?></label>
						<br><br>
						<label><?php esc_html_e( 'Minimum:', 'jetstop-spam' ); ?>
						<input type="number" name="min_time" value="<?php echo esc_attr( $protection['min_time'] ?? 3 ); ?>" min="1" max="60" class="small-text">
						<?php esc_html_e( 'seconds', 'jetstop-spam' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Rate Limiting', 'jetstop-spam' ); ?></th>
					<td>
						<label><input type="checkbox" name="rate_limit" value="1" <?php checked( ! empty( $protection['rate_limit'] ) ); ?>>
						<?php esc_html_e( 'Limit submissions per IP', 'jetstop-spam' ); ?></label>
						<br><br>
						<input type="number" name="rate_limit_count" value="<?php echo esc_attr( $protection['rate_limit_count'] ?? 5 ); ?>" min="1" max="100" class="small-text">
						<?php esc_html_e( 'submissions per', 'jetstop-spam' ); ?>
						<input type="number" name="rate_limit_period" value="<?php echo esc_attr( $protection['rate_limit_period'] ?? 60 ); ?>" min="10" max="3600" class="small-text">
						<?php esc_html_e( 'seconds', 'jetstop-spam' ); ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Blacklists', 'jetstop-spam' ); ?></th>
					<td>
						<label><input type="checkbox" name="blacklist" value="1" <?php checked( ! empty( $protection['blacklist'] ) ); ?>>
						<?php esc_html_e( 'Enable IP, email, and keyword blacklists', 'jetstop-spam' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Disposable Emails', 'jetstop-spam' ); ?></th>
					<td>
						<label><input type="checkbox" name="disposable_emails" value="1" <?php checked( ! empty( $protection['disposable_emails'] ) ); ?>>
						<?php esc_html_e( 'Block temporary email services', 'jetstop-spam' ); ?></label>
						<p class="description"><?php esc_html_e( 'Blocks 100+ disposable email domains like mailinator, guerrillamail, etc.', 'jetstop-spam' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Link Limit', 'jetstop-spam' ); ?></th>
					<td>
						<label><input type="checkbox" name="link_limit" value="1" <?php checked( ! empty( $protection['link_limit'] ) ); ?>>
						<?php esc_html_e( 'Limit URLs in submissions', 'jetstop-spam' ); ?></label>
						<br><br>
						<label><?php esc_html_e( 'Maximum:', 'jetstop-spam' ); ?>
						<input type="number" name="max_links" value="<?php echo esc_attr( $protection['max_links'] ?? 3 ); ?>" min="0" max="100" class="small-text">
						<?php esc_html_e( 'links', 'jetstop-spam' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Logging', 'jetstop-spam' ); ?></th>
					<td>
						<label><input type="checkbox" name="log_enabled" value="1" <?php checked( $log_enabled ); ?>>
						<?php esc_html_e( 'Log blocked submissions', 'jetstop-spam' ); ?></label>
					</td>
				</tr>
			</table>
		</div>

		<div class="jetstop-card">
			<h2><?php esc_html_e( 'Integrations', 'jetstop-spam' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Enable spam protection for these forms and systems:', 'jetstop-spam' ); ?></p>
			
			<div class="jetstop-integrations-grid">
				<?php foreach ( $all_integrations as $key => $integration ) : ?>
					<label class="jetstop-integration-item <?php echo $integration['available'] ? '' : 'unavailable'; ?>">
						<input type="checkbox" name="integration_<?php echo esc_attr( $key ); ?>" value="1" 
							<?php checked( ! empty( $integrations[ $key ] ) ); ?>
							<?php disabled( ! $integration['available'] ); ?>>
						<span><?php echo esc_html( $integration['name'] ); ?></span>
						<?php if ( ! $integration['available'] ) : ?>
							<small class="unavailable-note"><?php esc_html_e( 'Not installed', 'jetstop-spam' ); ?></small>
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save Settings', 'jetstop-spam' ); ?>
			</button>
			<span class="jetstop-save-status"></span>
		</p>
	</form>
</div>
