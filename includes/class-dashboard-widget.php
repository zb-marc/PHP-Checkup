<?php
/**
 * Dashboard Widget Handler
 *
 * @package AS_PHP_Checkup
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_PHP_Checkup_Dashboard_Widget class
 *
 * @since 1.1.0
 */
class AS_PHP_Checkup_Dashboard_Widget {

	/**
	 * Instance of this class
	 *
	 * @since 1.1.0
	 * @var AS_PHP_Checkup_Dashboard_Widget|null
	 */
	private static $instance = null;

	/**
	 * Widget ID
	 *
	 * @since 1.1.0
	 * @var string
	 */
	private $widget_id = 'as_php_checkup_dashboard_widget';

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	private function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_widget_assets' ) );
		add_action( 'wp_ajax_as_php_checkup_widget_refresh', array( $this, 'ajax_widget_refresh' ) );
		add_action( 'wp_ajax_as_php_checkup_widget_details', array( $this, 'ajax_widget_details' ) );
	}

	/**
	 * Get singleton instance
	 *
	 * @since 1.1.0
	 * @return AS_PHP_Checkup_Dashboard_Widget
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add dashboard widget
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function add_dashboard_widget() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			$this->widget_id,
			__( 'PHP Configuration Status', 'as-php-checkup' ),
			array( $this, 'render_widget' ),
			array( $this, 'render_widget_config' )
		);

		// Move widget to top
		global $wp_meta_boxes;
		if ( isset( $wp_meta_boxes['dashboard']['normal']['core'][ $this->widget_id ] ) ) {
			$widget_backup = $wp_meta_boxes['dashboard']['normal']['core'][ $this->widget_id ];
			unset( $wp_meta_boxes['dashboard']['normal']['core'][ $this->widget_id ] );
			
			// Add to side column for better visibility
			$wp_meta_boxes['dashboard']['side']['high'][ $this->widget_id ] = $widget_backup;
		}
	}

	/**
	 * Render dashboard widget
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function render_widget() {
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		
		// Calculate status counts
		$status_counts = array(
			'optimal'    => 0,
			'acceptable' => 0,
			'warning'    => 0,
		);
		
		$critical_issues = array();
		$recommendations = array();
		
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $key => $item ) {
				$status_counts[ $item['status'] ]++;
				
				// Collect critical issues
				if ( 'warning' === $item['status'] ) {
					$critical_issues[] = array(
						'label'   => $item['label'],
						'current' => $item['current'],
						'needed'  => $item['recommended'],
					);
				} elseif ( 'acceptable' === $item['status'] ) {
					$recommendations[] = array(
						'label'   => $item['label'],
						'current' => $item['current'],
						'optimal' => $item['recommended'],
					);
				}
			}
		}
		
		// Calculate health score
		$total_checks = array_sum( $status_counts );
		$health_score = $total_checks > 0 ? 
		               round( ( ( $status_counts['optimal'] * 100 ) + ( $status_counts['acceptable'] * 50 ) ) / $total_checks ) : 0;
		
		// Get last check time
		$last_check = get_option( 'as_php_checkup_last_check', current_time( 'timestamp' ) );
		$time_diff = human_time_diff( $last_check, current_time( 'timestamp' ) );
		
		// Get plugin analysis info
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$analyzed_plugins = $analyzer->get_analyzed_data();
		$plugins_with_requirements = count( $analyzed_plugins );
		
		// Determine overall status
		$overall_status = 'optimal';
		if ( $status_counts['warning'] > 0 ) {
			$overall_status = 'warning';
		} elseif ( $status_counts['acceptable'] > 2 ) {
			$overall_status = 'acceptable';
		}
		
		?>
		<div class="as-php-checkup-widget" data-status="<?php echo esc_attr( $overall_status ); ?>">
			<div class="widget-header">
				<div class="health-score-mini" data-score="<?php echo esc_attr( $health_score ); ?>">
					<svg viewBox="0 0 36 36" class="circular-chart <?php echo esc_attr( $overall_status ); ?>">
						<path class="circle-bg"
							d="M18 2.0845
							a 15.9155 15.9155 0 0 1 0 31.831
							a 15.9155 15.9155 0 0 1 0 -31.831"
						/>
						<path class="circle"
							stroke-dasharray="<?php echo esc_attr( $health_score ); ?>, 100"
							d="M18 2.0845
							a 15.9155 15.9155 0 0 1 0 31.831
							a 15.9155 15.9155 0 0 1 0 -31.831"
						/>
						<text x="18" y="21.5" class="percentage"><?php echo esc_html( $health_score ); ?>%</text>
					</svg>
				</div>
				
				<div class="widget-summary">
					<h3 class="health-title">
						<?php
						if ( 'optimal' === $overall_status ) {
							esc_html_e( 'Excellent Configuration', 'as-php-checkup' );
						} elseif ( 'acceptable' === $overall_status ) {
							esc_html_e( 'Good Configuration', 'as-php-checkup' );
						} else {
							esc_html_e( 'Needs Attention', 'as-php-checkup' );
						}
						?>
					</h3>
					<p class="health-subtitle">
						<?php
						printf(
							/* translators: %s: time difference */
							esc_html__( 'Last checked %s ago', 'as-php-checkup' ),
							esc_html( $time_diff )
						);
						?>
					</p>
				</div>
			</div>
			
			<div class="widget-stats">
				<div class="stat-item optimal">
					<span class="stat-icon">✓</span>
					<span class="stat-number"><?php echo esc_html( $status_counts['optimal'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Optimal', 'as-php-checkup' ); ?></span>
				</div>
				<div class="stat-item acceptable">
					<span class="stat-icon">!</span>
					<span class="stat-number"><?php echo esc_html( $status_counts['acceptable'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Acceptable', 'as-php-checkup' ); ?></span>
				</div>
				<div class="stat-item warning">
					<span class="stat-icon">✗</span>
					<span class="stat-number"><?php echo esc_html( $status_counts['warning'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Issues', 'as-php-checkup' ); ?></span>
				</div>
			</div>
			
			<?php if ( ! empty( $critical_issues ) ) : ?>
				<div class="widget-issues">
					<h4><?php esc_html_e( 'Critical Issues:', 'as-php-checkup' ); ?></h4>
					<ul class="issue-list">
						<?php foreach ( array_slice( $critical_issues, 0, 3 ) as $issue ) : ?>
							<li>
								<span class="issue-label"><?php echo esc_html( $issue['label'] ); ?>:</span>
								<span class="issue-values">
									<span class="current"><?php echo esc_html( $issue['current'] ?: __( 'Not set', 'as-php-checkup' ) ); ?></span>
									<span class="arrow">→</span>
									<span class="needed"><?php echo esc_html( $issue['needed'] ); ?></span>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( count( $critical_issues ) > 3 ) : ?>
						<p class="more-issues">
							<?php
							printf(
								/* translators: %d: number of additional issues */
								esc_html__( '... and %d more issues', 'as-php-checkup' ),
								count( $critical_issues ) - 3
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			<?php elseif ( ! empty( $recommendations ) ) : ?>
				<div class="widget-recommendations">
					<h4><?php esc_html_e( 'Optimization Opportunities:', 'as-php-checkup' ); ?></h4>
					<ul class="recommendation-list">
						<?php foreach ( array_slice( $recommendations, 0, 3 ) as $recommendation ) : ?>
							<li>
								<span class="rec-label"><?php echo esc_html( $recommendation['label'] ); ?></span>
								<span class="rec-status"><?php esc_html_e( 'Can be improved', 'as-php-checkup' ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php else : ?>
				<div class="widget-success">
					<p class="success-message">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'All PHP settings are optimally configured!', 'as-php-checkup' ); ?>
					</p>
				</div>
			<?php endif; ?>
			
			<div class="widget-plugins-info">
				<p>
					<span class="dashicons dashicons-admin-plugins"></span>
					<?php
					printf(
						/* translators: %d: number of plugins */
						esc_html__( 'Requirements from %d plugin(s) analyzed', 'as-php-checkup' ),
						$plugins_with_requirements
					);
					?>
				</p>
			</div>
			
			<div class="widget-actions">
				<button type="button" class="button button-primary widget-refresh" id="widget-refresh-check">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Refresh', 'as-php-checkup' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=as-php-checkup' ) ); ?>" class="button">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'View Details', 'as-php-checkup' ); ?>
				</a>
			</div>
			
			<div class="widget-footer">
				<small>
					<?php
					printf(
						/* translators: %s: plugin name with version */
						esc_html__( 'Powered by %s', 'as-php-checkup' ),
						'AS PHP Checkup v' . AS_PHP_CHECKUP_VERSION
					);
					?>
				</small>
			</div>
		</div>
		<?php
	}

	/**
	 * Render widget configuration form
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function render_widget_config() {
		// Get widget options
		$options = get_option( 'as_php_checkup_widget_options', array(
			'auto_refresh'      => true,
			'show_critical'     => true,
			'show_plugin_info'  => true,
			'compact_mode'      => false,
		) );
		
		// Handle form submission
		if ( isset( $_POST['as_php_checkup_widget_submit'] ) ) {
			// Verify nonce
			if ( ! isset( $_POST['as_php_checkup_widget_nonce'] ) || 
			     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['as_php_checkup_widget_nonce'] ) ), 'as_php_checkup_widget_config' ) ) {
				wp_die( esc_html__( 'Security check failed', 'as-php-checkup' ) );
			}
			
			// Update options
			$options['auto_refresh'] = isset( $_POST['auto_refresh'] );
			$options['show_critical'] = isset( $_POST['show_critical'] );
			$options['show_plugin_info'] = isset( $_POST['show_plugin_info'] );
			$options['compact_mode'] = isset( $_POST['compact_mode'] );
			
			update_option( 'as_php_checkup_widget_options', $options );
		}
		
		?>
		<p>
			<label>
				<input type="checkbox" name="auto_refresh" value="1" <?php checked( $options['auto_refresh'] ); ?> />
				<?php esc_html_e( 'Enable auto-refresh (every 5 minutes)', 'as-php-checkup' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="show_critical" value="1" <?php checked( $options['show_critical'] ); ?> />
				<?php esc_html_e( 'Show critical issues', 'as-php-checkup' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="show_plugin_info" value="1" <?php checked( $options['show_plugin_info'] ); ?> />
				<?php esc_html_e( 'Show plugin analysis info', 'as-php-checkup' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="compact_mode" value="1" <?php checked( $options['compact_mode'] ); ?> />
				<?php esc_html_e( 'Compact display mode', 'as-php-checkup' ); ?>
			</label>
		</p>
		<?php wp_nonce_field( 'as_php_checkup_widget_config', 'as_php_checkup_widget_nonce' ); ?>
		<input type="hidden" name="as_php_checkup_widget_submit" value="1" />
		<?php
	}

	/**
	 * Enqueue widget assets
	 *
	 * @since 1.1.0
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_widget_assets( $hook_suffix ) {
		// Only load on dashboard
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}
		
		// Check if styles are already registered
		if ( ! wp_style_is( 'as-php-checkup-widget', 'registered' ) ) {
			wp_register_style(
				'as-php-checkup-widget',
				AS_PHP_CHECKUP_PLUGIN_URL . 'assets/css/widget-style.css',
				array(),
				AS_PHP_CHECKUP_VERSION,
				'all'
			);
		}
		
		// Check if scripts are already registered
		if ( ! wp_script_is( 'as-php-checkup-widget', 'registered' ) ) {
			wp_register_script(
				'as-php-checkup-widget',
				AS_PHP_CHECKUP_PLUGIN_URL . 'assets/js/widget-script.js',
				array( 'jquery', 'wp-util' ),
				AS_PHP_CHECKUP_VERSION,
				true
			);
		}
		
		wp_enqueue_style( 'as-php-checkup-widget' );
		wp_enqueue_script( 'as-php-checkup-widget' );
		
		// Get widget options
		$options = get_option( 'as_php_checkup_widget_options', array(
			'auto_refresh' => true,
		) );
		
		// Localize script
		wp_localize_script(
			'as-php-checkup-widget',
			'asPhpCheckupWidget',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'as_php_checkup_widget_nonce' ),
				'refreshing'   => __( 'Refreshing...', 'as-php-checkup' ),
				'error'        => __( 'An error occurred. Please try again.', 'as-php-checkup' ),
				'autoRefresh'  => $options['auto_refresh'],
				'detailsUrl'   => admin_url( 'tools.php?page=as-php-checkup' ),
			)
		);
	}

	/**
	 * AJAX handler for widget refresh
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function ajax_widget_refresh() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_widget_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		// Clear cache
		wp_cache_flush();
		
		// Update last check time
		update_option( 'as_php_checkup_last_check', current_time( 'timestamp' ) );
		
		// Get fresh results
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		
		// Calculate status counts
		$status_counts = array(
			'optimal'    => 0,
			'acceptable' => 0,
			'warning'    => 0,
		);
		
		$critical_issues = array();
		
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				$status_counts[ $item['status'] ]++;
				
				if ( 'warning' === $item['status'] ) {
					$critical_issues[] = array(
						'label'   => $item['label'],
						'current' => $item['current'],
						'needed'  => $item['recommended'],
					);
				}
			}
		}
		
		// Calculate health score
		$total_checks = array_sum( $status_counts );
		$health_score = $total_checks > 0 ? 
		               round( ( ( $status_counts['optimal'] * 100 ) + ( $status_counts['acceptable'] * 50 ) ) / $total_checks ) : 0;
		
		wp_send_json_success( array(
			'health_score'    => $health_score,
			'status_counts'   => $status_counts,
			'critical_issues' => array_slice( $critical_issues, 0, 3 ),
			'overall_status'  => $status_counts['warning'] > 0 ? 'warning' : ( $status_counts['acceptable'] > 2 ? 'acceptable' : 'optimal' ),
			'time'            => current_time( 'mysql' ),
		) );
	}

	/**
	 * AJAX handler for widget details
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function ajax_widget_details() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_widget_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		$system_info = $checkup->get_system_info();
		
		// Prepare detailed data
		$details = array(
			'php_version'   => PHP_VERSION,
			'memory_limit'  => ini_get( 'memory_limit' ),
			'max_exec_time' => ini_get( 'max_execution_time' ),
			'upload_size'   => ini_get( 'upload_max_filesize' ),
			'server'        => $system_info['server']['software'],
			'results'       => $results,
		);
		
		wp_send_json_success( $details );
	}
}