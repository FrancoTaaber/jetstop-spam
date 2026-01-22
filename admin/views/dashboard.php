<?php
/**
 * Dashboard view.
 *
 * @package Jetstop_Spam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Jetstop_Spam\Plugin;
use Jetstop_Spam\Admin;

$plugin = Plugin::get_instance();
$stats  = $plugin->get_statistics()->get_summary( 30 );
$today  = $plugin->get_statistics()->get_summary( 1 );
$week   = $plugin->get_statistics()->get_summary( 7 );
$top_ips = $plugin->get_logger()->get_top_ips( 5, 30 );
?>

<div class="wrap jetstop-wrap">
	<h1>
		<span class="dashicons dashicons-shield-alt"></span>
		<?php esc_html_e( 'Jetstop Spam Dashboard', 'jetstop-spam' ); ?>
	</h1>

	<div class="jetstop-stats-grid">
		<div class="jetstop-stat-card">
			<div class="jetstop-stat-number"><?php echo esc_html( number_format_i18n( $today['total'] ?? 0 ) ); ?></div>
			<div class="jetstop-stat-label"><?php esc_html_e( 'Blocked Today', 'jetstop-spam' ); ?></div>
		</div>
		<div class="jetstop-stat-card">
			<div class="jetstop-stat-number"><?php echo esc_html( number_format_i18n( $week['total'] ?? 0 ) ); ?></div>
			<div class="jetstop-stat-label"><?php esc_html_e( 'This Week', 'jetstop-spam' ); ?></div>
		</div>
		<div class="jetstop-stat-card">
			<div class="jetstop-stat-number"><?php echo esc_html( number_format_i18n( $stats['total'] ?? 0 ) ); ?></div>
			<div class="jetstop-stat-label"><?php esc_html_e( 'Last 30 Days', 'jetstop-spam' ); ?></div>
		</div>
		<div class="jetstop-stat-card">
			<div class="jetstop-stat-number"><?php echo esc_html( number_format_i18n( $plugin->get_statistics()->get_all_time_total() ) ); ?></div>
			<div class="jetstop-stat-label"><?php esc_html_e( 'All Time', 'jetstop-spam' ); ?></div>
		</div>
	</div>

	<div class="jetstop-dashboard-grid">
		<div class="jetstop-card">
			<h2><?php esc_html_e( 'Blocked by Source', 'jetstop-spam' ); ?></h2>
			<?php if ( ! empty( $stats['by_source'] ) ) : ?>
				<table class="widefat striped">
					<tbody>
						<?php foreach ( $stats['by_source'] as $source => $count ) : ?>
							<tr>
								<td><?php echo esc_html( Admin::get_source_label( $source ) ); ?></td>
								<td class="jetstop-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="jetstop-empty"><?php esc_html_e( 'No spam blocked yet.', 'jetstop-spam' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="jetstop-card">
			<h2><?php esc_html_e( 'Blocked by Reason', 'jetstop-spam' ); ?></h2>
			<?php if ( ! empty( $stats['by_reason'] ) ) : ?>
				<table class="widefat striped">
					<tbody>
						<?php foreach ( $stats['by_reason'] as $reason => $count ) : ?>
							<tr>
								<td><?php echo esc_html( Admin::get_reason_label( $reason ) ); ?></td>
								<td class="jetstop-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="jetstop-empty"><?php esc_html_e( 'No data available.', 'jetstop-spam' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="jetstop-card">
			<h2><?php esc_html_e( 'Top Blocked IPs', 'jetstop-spam' ); ?></h2>
			<?php if ( ! empty( $top_ips ) ) : ?>
				<table class="widefat striped">
					<tbody>
						<?php foreach ( $top_ips as $ip ) : ?>
							<tr>
								<td><code><?php echo esc_html( $ip['ip_address'] ); ?></code></td>
								<td class="jetstop-count"><?php echo esc_html( number_format_i18n( $ip['count'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="jetstop-empty"><?php esc_html_e( 'No blocked IPs.', 'jetstop-spam' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="jetstop-card">
			<h2><?php esc_html_e( 'Active Integrations', 'jetstop-spam' ); ?></h2>
			<?php
			$integrations = Admin::get_integrations_status();
			$enabled      = get_option( 'jetstop_integrations', array() );
			$active       = 0;
			?>
			<ul class="jetstop-integrations-list">
				<?php foreach ( $integrations as $key => $integration ) : ?>
					<?php
					$is_enabled = ! empty( $enabled[ $key ] );
					$is_active  = $is_enabled && $integration['available'];
					if ( $is_active ) {
						$active++;
					}
					?>
					<li class="<?php echo $is_active ? 'active' : ( $is_enabled ? 'inactive' : 'disabled' ); ?>">
						<span class="dashicons <?php echo $is_active ? 'dashicons-yes-alt' : ( $integration['available'] ? 'dashicons-minus' : 'dashicons-no' ); ?>"></span>
						<?php echo esc_html( $integration['name'] ); ?>
						<?php if ( ! $integration['available'] ) : ?>
							<small>(<?php esc_html_e( 'not installed', 'jetstop-spam' ); ?>)</small>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="jetstop-integration-summary">
				<?php
				printf(
					/* translators: %d: number of active integrations */
					esc_html__( '%d integrations active', 'jetstop-spam' ),
					$active
				);
				?>
			</p>
		</div>
	</div>

	<div class="jetstop-quick-links">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=jetstop-spam-settings' ) ); ?>" class="button">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Settings', 'jetstop-spam' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=jetstop-spam-blacklists' ) ); ?>" class="button">
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'Blacklists', 'jetstop-spam' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=jetstop-spam-log' ) ); ?>" class="button">
			<span class="dashicons dashicons-database"></span>
			<?php esc_html_e( 'View Log', 'jetstop-spam' ); ?>
		</a>
	</div>
</div>
