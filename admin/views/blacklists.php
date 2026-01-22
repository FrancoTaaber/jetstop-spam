<?php
/**
 * Blacklists view.
 *
 * @package Jetstop_Spam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blacklists = get_option( 'jetstop_blacklists', array() );
?>

<div class="wrap jetstop-wrap">
	<h1><?php esc_html_e( 'Jetstop Spam Blacklists', 'jetstop-spam' ); ?></h1>

	<form id="jetstop-blacklists-form" class="jetstop-form">
		<div class="jetstop-blacklist-grid">
			<div class="jetstop-card">
				<h2><?php esc_html_e( 'IP Blacklist', 'jetstop-spam' ); ?></h2>
				<p class="description"><?php esc_html_e( 'One IP per line. Supports CIDR (192.168.1.0/24) and wildcards (192.168.1.*).', 'jetstop-spam' ); ?></p>
				<textarea name="ips" rows="12" class="large-text code"><?php echo esc_textarea( $blacklists['ips'] ?? '' ); ?></textarea>
			</div>

			<div class="jetstop-card">
				<h2><?php esc_html_e( 'Email Blacklist', 'jetstop-spam' ); ?></h2>
				<p class="description"><?php esc_html_e( 'One email per line. Use @domain.com to block domains. Wildcards supported.', 'jetstop-spam' ); ?></p>
				<textarea name="emails" rows="12" class="large-text code"><?php echo esc_textarea( $blacklists['emails'] ?? '' ); ?></textarea>
			</div>

			<div class="jetstop-card">
				<h2><?php esc_html_e( 'Keyword Blacklist', 'jetstop-spam' ); ?></h2>
				<p class="description"><?php esc_html_e( 'One word/phrase per line. Case-insensitive. Wrap in /regex/ for patterns.', 'jetstop-spam' ); ?></p>
				<textarea name="keywords" rows="12" class="large-text code"><?php echo esc_textarea( $blacklists['keywords'] ?? '' ); ?></textarea>
			</div>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save Blacklists', 'jetstop-spam' ); ?>
			</button>
			<span class="jetstop-save-status"></span>
		</p>
	</form>
</div>
