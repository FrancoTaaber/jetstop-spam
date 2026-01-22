<?php
/**
 * Main plugin class.
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
 * Plugin class - Singleton.
 */
class Plugin {

	/**
	 * Single instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Protection settings.
	 *
	 * @var array
	 */
	private $protection = array();

	/**
	 * Integration settings.
	 *
	 * @var array
	 */
	private $integrations = array();

	/**
	 * Spam engine instance.
	 *
	 * @var Spam_Engine|null
	 */
	private $spam_engine = null;

	/**
	 * Statistics instance.
	 *
	 * @var Statistics|null
	 */
	private $statistics = null;

	/**
	 * Logger instance.
	 *
	 * @var Logger|null
	 */
	private $logger = null;

	/**
	 * Admin instance.
	 *
	 * @var Admin|null
	 */
	private $admin = null;

	/**
	 * Get single instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_settings();
		$this->load_dependencies();
		$this->init_components();
		$this->init_integrations();
	}

	/**
	 * Load settings from database.
	 */
	private function load_settings() {
		$default_protection = array(
			'honeypot'          => true,
			'time_check'        => true,
			'min_time'          => 3,
			'js_check'          => true,
			'rate_limit'        => true,
			'rate_limit_count'  => 5,
			'rate_limit_period' => 60,
			'blacklist'         => true,
			'disposable_emails' => true,
			'link_limit'        => true,
			'max_links'         => 3,
		);

		$default_integrations = array(
			'wp_comments'     => true,
			'wp_registration' => true,
			'wp_login'        => true,
			'contact_form_7'  => true,
			'wpforms'         => true,
			'gravity_forms'   => true,
			'forminator'      => true,
			'elementor'       => true,
			'fluent_forms'    => true,
			'ninja_forms'     => true,
			'formidable'      => true,
			'woocommerce'     => true,
			'bbpress'         => true,
		);

		$this->protection   = wp_parse_args( get_option( 'jetstop_protection', array() ), $default_protection );
		$this->integrations = wp_parse_args( get_option( 'jetstop_integrations', array() ), $default_integrations );
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		// Core classes.
		require_once JETSTOP_DIR . 'includes/class-spam-engine.php';
		require_once JETSTOP_DIR . 'includes/class-statistics.php';
		require_once JETSTOP_DIR . 'includes/class-logger.php';

		// Protection classes.
		require_once JETSTOP_DIR . 'includes/protection/class-honeypot.php';
		require_once JETSTOP_DIR . 'includes/protection/class-time-check.php';
		require_once JETSTOP_DIR . 'includes/protection/class-rate-limiter.php';
		require_once JETSTOP_DIR . 'includes/protection/class-blacklist.php';
		require_once JETSTOP_DIR . 'includes/protection/class-disposable.php';
		require_once JETSTOP_DIR . 'includes/protection/class-link-checker.php';

		// Integration base.
		require_once JETSTOP_DIR . 'includes/integration/abstract-integration.php';

		// Admin.
		if ( is_admin() ) {
			require_once JETSTOP_DIR . 'includes/class-admin.php';
			require_once JETSTOP_DIR . 'includes/class-ajax-handler.php';
		}
	}

	/**
	 * Initialize core components.
	 */
	private function init_components() {
		// Initialize logger.
		$this->logger = new Logger();

		// Initialize statistics.
		$this->statistics = new Statistics();

		// Initialize spam engine with all protection methods.
		$this->spam_engine = new Spam_Engine( $this->protection, $this->logger, $this->statistics );

		// Initialize admin.
		if ( is_admin() ) {
			$this->admin = new Admin( $this );
			new Ajax_Handler( $this );
		}

		// Enqueue frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Add dashboard widget.
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

		// Schedule cleanup.
		if ( ! wp_next_scheduled( 'jetstop_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'jetstop_daily_cleanup' );
		}
		add_action( 'jetstop_daily_cleanup', array( $this, 'daily_cleanup' ) );
	}

	/**
	 * Initialize form integrations.
	 */
	private function init_integrations() {
		$available_integrations = array(
			'wp_comments'     => 'WP_Comments',
			'wp_registration' => 'WP_Registration',
			'wp_login'        => 'WP_Login',
			'contact_form_7'  => 'Contact_Form_7',
			'wpforms'         => 'WPForms',
			'gravity_forms'   => 'Gravity_Forms',
			'forminator'      => 'Forminator',
			'elementor'       => 'Elementor',
			'fluent_forms'    => 'Fluent_Forms',
			'ninja_forms'     => 'Ninja_Forms',
			'formidable'      => 'Formidable',
			'woocommerce'     => 'WooCommerce',
			'bbpress'         => 'BBPress',
		);

		foreach ( $available_integrations as $key => $class_name ) {
			// Check if integration is enabled.
			if ( empty( $this->integrations[ $key ] ) ) {
				continue;
			}

			$file = JETSTOP_DIR . 'includes/integration/class-' . str_replace( '_', '-', $key ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
				$full_class = __NAMESPACE__ . '\\Integration\\' . $class_name;

				if ( class_exists( $full_class ) ) {
					$integration = new $full_class( $this->spam_engine );

					// Only init if the target plugin is active.
					if ( $integration->is_available() ) {
						$integration->init();
					}
				}
			}
		}
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		// Only load if honeypot or JS check is enabled.
		if ( ! $this->protection['honeypot'] && ! $this->protection['js_check'] ) {
			return;
		}

		wp_enqueue_script(
			'jetstop-spam',
			JETSTOP_URL . 'assets/js/honeypot.js',
			array(),
			JETSTOP_VERSION,
			true
		);

		$honeypot_field = get_option( 'jetstop_honeypot_field', 'website_url' );

		wp_localize_script(
			'jetstop-spam',
			'jetstopConfig',
			array(
				'field'     => $honeypot_field,
				'jsCheck'   => $this->protection['js_check'],
				'timestamp' => $this->protection['time_check'],
			)
		);

		wp_enqueue_style(
			'jetstop-spam',
			JETSTOP_URL . 'assets/css/honeypot.css',
			array(),
			JETSTOP_VERSION
		);
	}

	/**
	 * Add dashboard widget.
	 */
	public function add_dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'jetstop_spam_widget',
			__( 'Jetstop Spam Protection', 'jetstop-spam' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 */
	public function render_dashboard_widget() {
		$stats = $this->statistics->get_summary( 30 );
		?>
		<div class="jetstop-dashboard-widget">
			<div class="jetstop-stat-row">
				<span class="jetstop-stat-number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
				<span class="jetstop-stat-label"><?php esc_html_e( 'Spam blocked (30 days)', 'jetstop-spam' ); ?></span>
			</div>
			<?php if ( ! empty( $stats['by_source'] ) ) : ?>
				<div class="jetstop-stat-breakdown">
					<?php foreach ( array_slice( $stats['by_source'], 0, 5 ) as $source => $count ) : ?>
						<div class="jetstop-stat-item">
							<span class="jetstop-stat-source"><?php echo esc_html( ucwords( str_replace( '_', ' ', $source ) ) ); ?></span>
							<span class="jetstop-stat-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<p class="jetstop-widget-footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=jetstop-spam' ) ); ?>">
					<?php esc_html_e( 'View Details', 'jetstop-spam' ); ?> →
				</a>
			</p>
		</div>
		<style>
			.jetstop-dashboard-widget { padding: 5px 0; }
			.jetstop-stat-row { text-align: center; margin-bottom: 15px; }
			.jetstop-stat-number { display: block; font-size: 36px; font-weight: 600; color: #1d2327; }
			.jetstop-stat-label { color: #646970; }
			.jetstop-stat-breakdown { border-top: 1px solid #eee; padding-top: 10px; }
			.jetstop-stat-item { display: flex; justify-content: space-between; padding: 5px 0; }
			.jetstop-stat-source { color: #646970; }
			.jetstop-stat-count { font-weight: 500; }
			.jetstop-widget-footer { margin: 15px 0 0; padding-top: 10px; border-top: 1px solid #eee; }
		</style>
		<?php
	}

	/**
	 * Daily cleanup task.
	 */
	public function daily_cleanup() {
		// Clean old logs (90 days).
		$this->logger->cleanup( 90 );

		// Clean old stats (365 days).
		$this->statistics->cleanup( 365 );

		// Clean expired rate limit transients.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_jetstop_rate_%' AND option_value < " . time() );
	}

	/**
	 * Get protection settings.
	 *
	 * @return array
	 */
	public function get_protection() {
		return $this->protection;
	}

	/**
	 * Get integration settings.
	 *
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	/**
	 * Get spam engine.
	 *
	 * @return Spam_Engine
	 */
	public function get_spam_engine() {
		return $this->spam_engine;
	}

	/**
	 * Get statistics.
	 *
	 * @return Statistics
	 */
	public function get_statistics() {
		return $this->statistics;
	}

	/**
	 * Get logger.
	 *
	 * @return Logger
	 */
	public function get_logger() {
		return $this->logger;
	}
}
