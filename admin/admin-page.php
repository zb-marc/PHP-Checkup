<?php
/**
 * Admin Page Handler
 *
 * @package AS_PHP_Checkup
 * @since 1.0.0
 * @version 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_PHP_Checkup_Admin class
 *
 * @since 1.0.0
 */
class AS_PHP_Checkup_Admin {

	/**
	 * Instance of this class
	 *
	 * @since 1.0.0
	 * @var AS_PHP_Checkup_Admin|null
	 */
	private static $instance = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_as_php_checkup_refresh', array( $this, 'ajax_refresh_check' ) );
		add_action( 'wp_ajax_as_php_checkup_export', array( $this, 'ajax_export_report' ) );
		add_action( 'wp_ajax_as_php_checkup_analyze_plugins', array( $this, 'ajax_analyze_plugins' ) );
	}

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return AS_PHP_Checkup_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add admin menu
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'tools.php',
			__( 'PHP Checkup', 'as-php-checkup' ),
			__( 'PHP Checkup', 'as-php-checkup' ),
			'manage_options',
			'as-php-checkup',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'tools_page_as-php-checkup' !== $hook_suffix ) {
			return;
		}
		
		// Check if styles are already registered
		if ( ! wp_style_is( 'as-php-checkup-admin', 'registered' ) ) {
			wp_register_style(
				'as-php-checkup-admin',
				AS_PHP_CHECKUP_PLUGIN_URL . 'assets/css/admin-style.css',
				array(),
				AS_PHP_CHECKUP_VERSION,
				'all'
			);
		}
		
		// Check if scripts are already registered
		if ( ! wp_script_is( 'as-php-checkup-admin', 'registered' ) ) {
			wp_register_script(
				'as-php-checkup-admin',
				AS_PHP_CHECKUP_PLUGIN_URL . 'assets/js/admin-script.js',
				array( 'jquery' ),
				AS_PHP_CHECKUP_VERSION,
				true
			);
		}
		
		wp_enqueue_style( 'as-php-checkup-admin' );
		wp_enqueue_script( 'as-php-checkup-admin' );
		
		// Localize script
		wp_localize_script(
			'as-php-checkup-admin',
			'asPhpCheckup',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'as_php_checkup_nonce' ),
				'refreshing'        => __( 'Refreshing...', 'as-php-checkup' ),
				'exportingReport'   => __( 'Exporting report...', 'as-php-checkup' ),
				'analyzingPlugins'  => __( 'Analyzing plugins...', 'as-php-checkup' ),
				'applyingSolution'  => __( 'Applying solution...', 'as-php-checkup' ),
				'downloadingConfig' => __( 'Downloading configuration...', 'as-php-checkup' ),
				'testingWritable'   => __( 'Testing write permissions...', 'as-php-checkup' ),
				'error'             => __( 'An error occurred. Please try again.', 'as-php-checkup' ),
				'confirmApply'      => __( 'This will modify your server configuration. Create a backup first. Continue?', 'as-php-checkup' ),
				'solutionApplied'   => __( 'Solution applied successfully!', 'as-php-checkup' ),
			)
		);
	}

	/**
	 * Render admin page
	 *
	 * @since 1.0.0
	 * @version 1.2.0
	 * @return void
	 */
	public function render_admin_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'as-php-checkup' ) );
		}
		
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		$system_info = $checkup->get_system_info();
		$plugin_requirements = $checkup->get_plugin_requirements();
		
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$analyzed_plugins = $analyzer->get_analyzed_data();
		
		// Get solutions - New in 1.2.0
		$solution_provider = AS_PHP_Checkup_Solution_Provider::get_instance();
		$solutions = $solution_provider->get_solutions( $results );
		$server_type = $solution_provider->get_server_type();
		$hosting_provider = $solution_provider->get_hosting_provider();
		
		// Calculate overall status
		$status_counts = array(
			'optimal'    => 0,
			'acceptable' => 0,
			'warning'    => 0,
		);
		
		$has_issues = false;
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				$status_counts[ $item['status'] ]++;
				if ( in_array( $item['status'], array( 'warning', 'acceptable' ), true ) ) {
					$has_issues = true;
				}
			}
		}
		
		// Calculate health score
		$total_checks = array_sum( $status_counts );
		$health_score = $total_checks > 0 ? 
		               round( ( ( $status_counts['optimal'] * 100 ) + ( $status_counts['acceptable'] * 50 ) ) / $total_checks ) : 0;
		
		?>
		<div class="wrap as-php-checkup-wrap">
			<h1>
				<?php echo esc_html( get_admin_page_title() ); ?>
				<span class="version"><?php echo esc_html( 'v' . AS_PHP_CHECKUP_VERSION ); ?></span>
			</h1>
			
			<div class="as-php-checkup-header">
				<div class="status-summary">
					<h2><?php esc_html_e( 'Overall Status', 'as-php-checkup' ); ?></h2>
					<div class="health-score-container">
						<div class="health-score-circle" data-score="<?php echo esc_attr( $health_score ); ?>">
							<svg viewBox="0 0 100 100">
								<circle cx="50" cy="50" r="45" fill="none" stroke="#e0e0e0" stroke-width="10"/>
								<circle cx="50" cy="50" r="45" fill="none" stroke="<?php echo $health_score >= 75 ? '#46b450' : ( $health_score >= 50 ? '#ffb900' : '#dc3232' ); ?>" stroke-width="10" stroke-dasharray="<?php echo 2.83 * $health_score; ?> 283" stroke-linecap="round" transform="rotate(-90 50 50)"/>
							</svg>
							<div class="health-score-text">
								<span class="score"><?php echo esc_html( $health_score ); ?>%</span>
								<span class="label"><?php esc_html_e( 'Health', 'as-php-checkup' ); ?></span>
							</div>
						</div>
					</div>
					<div class="status-cards">
						<div class="status-card optimal">
							<span class="status-icon">✓</span>
							<span class="status-count"><?php echo esc_html( $status_counts['optimal'] ); ?></span>
							<span class="status-label"><?php esc_html_e( 'Optimal', 'as-php-checkup' ); ?></span>
						</div>
						<div class="status-card acceptable">
							<span class="status-icon">!</span>
							<span class="status-count"><?php echo esc_html( $status_counts['acceptable'] ); ?></span>
							<span class="status-label"><?php esc_html_e( 'Acceptable', 'as-php-checkup' ); ?></span>
						</div>
						<div class="status-card warning">
							<span class="status-icon">✗</span>
							<span class="status-count"><?php echo esc_html( $status_counts['warning'] ); ?></span>
							<span class="status-label"><?php esc_html_e( 'Needs Attention', 'as-php-checkup' ); ?></span>
						</div>
					</div>
				</div>
				
				<div class="action-buttons">
					<button id="refresh-check" class="button button-primary">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Refresh Check', 'as-php-checkup' ); ?>
					</button>
					<button id="analyze-plugins" class="button">
						<span class="dashicons dashicons-admin-plugins"></span>
						<?php esc_html_e( 'Re-Analyze Plugins', 'as-php-checkup' ); ?>
					</button>
					<button id="export-report" class="button">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Export Report', 'as-php-checkup' ); ?>
					</button>
				</div>
			</div>
			
			<?php if ( ! empty( $solutions ) && $has_issues ) : ?>
				<!-- Solutions Section - New in 1.2.0 -->
				<div class="as-php-checkup-solutions">
					<h2>
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Available Solutions', 'as-php-checkup' ); ?>
					</h2>
					<div class="environment-info">
						<p>
							<?php
							printf(
								/* translators: 1: server type, 2: hosting provider */
								esc_html__( 'Server: %1$s | Hosting: %2$s', 'as-php-checkup' ),
								'<strong>' . esc_html( ucfirst( $server_type ) ) . '</strong>',
								'<strong>' . esc_html( ucfirst( str_replace( '_', ' ', $hosting_provider ) ) ) . '</strong>'
							);
							?>
						</p>
					</div>
					<div class="solutions-grid">
						<?php foreach ( $solutions as $solution_key => $solution ) : ?>
							<div class="solution-card" data-solution="<?php echo esc_attr( $solution_key ); ?>">
								<div class="solution-header">
									<span class="dashicons <?php echo esc_attr( $solution['icon'] ); ?>"></span>
									<h3><?php echo esc_html( $solution['title'] ); ?></h3>
								</div>
								<div class="solution-content">
									<p><?php echo esc_html( $solution['description'] ); ?></p>
									
									<?php if ( isset( $solution['instructions'] ) && is_array( $solution['instructions'] ) ) : ?>
										<div class="solution-instructions">
											<h4><?php esc_html_e( 'Instructions:', 'as-php-checkup' ); ?></h4>
											<ol>
												<?php foreach ( $solution['instructions'] as $instruction ) : ?>
													<li><?php echo esc_html( $instruction ); ?></li>
												<?php endforeach; ?>
											</ol>
										</div>
									<?php endif; ?>
									
									<div class="solution-actions">
										<?php if ( $solution['auto_apply'] ) : ?>
											<button class="button button-primary apply-solution" data-type="<?php echo esc_attr( $solution['type'] ); ?>">
												<span class="dashicons dashicons-admin-tools"></span>
												<?php esc_html_e( 'Apply Automatically', 'as-php-checkup' ); ?>
											</button>
										<?php endif; ?>
										
										<?php if ( ! isset( $solution['instructions'] ) || $solution['auto_apply'] ) : ?>
											<button class="button download-config" data-type="<?php echo esc_attr( $solution['type'] ); ?>">
												<span class="dashicons dashicons-download"></span>
												<?php esc_html_e( 'Download Configuration', 'as-php-checkup' ); ?>
											</button>
										<?php endif; ?>
									</div>
									
									<?php if ( $solution['auto_apply'] ) : ?>
										<div class="solution-warning">
											<span class="dashicons dashicons-info"></span>
											<?php esc_html_e( 'Automatic application will modify your server configuration. Create a backup first!', 'as-php-checkup' ); ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					
					<!-- Advanced Configuration Options -->
					<div class="advanced-config-section">
						<h3><?php esc_html_e( 'Advanced Configuration Downloads', 'as-php-checkup' ); ?></h3>
						<div class="config-download-buttons">
							<button class="button download-config" data-type="php_ini">
								<span class="dashicons dashicons-media-code"></span>
								<?php esc_html_e( 'php.ini', 'as-php-checkup' ); ?>
							</button>
							<button class="button download-config" data-type="user_ini">
								<span class="dashicons dashicons-media-code"></span>
								<?php esc_html_e( '.user.ini', 'as-php-checkup' ); ?>
							</button>
							<?php if ( 'nginx' === $server_type ) : ?>
								<button class="button download-config" data-type="nginx">
									<span class="dashicons dashicons-networking"></span>
									<?php esc_html_e( 'NGINX Config', 'as-php-checkup' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( in_array( $server_type, array( 'apache', 'litespeed' ), true ) ) : ?>
								<button class="button download-config" data-type="htaccess">
									<span class="dashicons dashicons-media-code"></span>
									<?php esc_html_e( '.htaccess Snippet', 'as-php-checkup' ); ?>
								</button>
							<?php endif; ?>
							<button class="button download-config" data-type="wp_config">
								<span class="dashicons dashicons-wordpress-alt"></span>
								<?php esc_html_e( 'wp-config Constants', 'as-php-checkup' ); ?>
							</button>
						</div>
					</div>
				</div>
			<?php elseif ( ! $has_issues ) : ?>
				<div class="as-php-checkup-success-banner">
					<span class="dashicons dashicons-awards"></span>
					<h2><?php esc_html_e( 'Perfect Configuration!', 'as-php-checkup' ); ?></h2>
					<p><?php esc_html_e( 'Your PHP configuration is fully optimized. No action needed.', 'as-php-checkup' ); ?></p>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $analyzed_plugins ) ) : ?>
				<div class="as-php-checkup-plugin-analysis">
					<h3><?php esc_html_e( 'Plugin-Based Requirements', 'as-php-checkup' ); ?></h3>
					<div class="plugin-analysis-info">
						<p>
							<?php
							printf(
								/* translators: %d: number of plugins */
								esc_html__( 'Analyzed %d active plugin(s) and adjusted recommendations based on their requirements.', 'as-php-checkup' ),
								count( $analyzed_plugins )
							);
							?>
						</p>
						<div class="analyzed-plugins-list">
							<strong><?php esc_html_e( 'Plugins with requirements:', 'as-php-checkup' ); ?></strong>
							<ul>
								<?php foreach ( $analyzed_plugins as $plugin_data ) : ?>
									<li>
										<?php echo esc_html( $plugin_data['name'] ); ?>
										<?php if ( isset( $plugin_data['php_version'] ) ) : ?>
											<span class="plugin-requirement"><?php echo esc_html( sprintf( 'PHP %s+', $plugin_data['php_version'] ) ); ?></span>
										<?php endif; ?>
										<?php if ( isset( $plugin_data['memory_limit'] ) ) : ?>
											<span class="plugin-requirement"><?php echo esc_html( sprintf( 'Memory: %s', $plugin_data['memory_limit'] ) ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			<?php endif; ?>
			
			<div class="as-php-checkup-results">
				<?php foreach ( $results as $category_key => $category ) : ?>
					<div class="check-category <?php echo esc_attr( $category_key ); ?>">
						<h3><?php echo esc_html( $category['label'] ); ?></h3>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th scope="col" class="column-setting"><?php esc_html_e( 'Setting', 'as-php-checkup' ); ?></th>
									<th scope="col" class="column-current"><?php esc_html_e( 'Current', 'as-php-checkup' ); ?></th>
									<th scope="col" class="column-recommended"><?php esc_html_e( 'Recommended', 'as-php-checkup' ); ?></th>
									<th scope="col" class="column-minimum"><?php esc_html_e( 'Minimum', 'as-php-checkup' ); ?></th>
									<th scope="col" class="column-status"><?php esc_html_e( 'Status', 'as-php-checkup' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $category['items'] as $key => $item ) : ?>
									<tr class="status-<?php echo esc_attr( $item['status'] ); ?>">
										<td class="column-setting">
											<strong><?php echo esc_html( $item['label'] ); ?></strong>
											<p class="description"><?php echo esc_html( $item['description'] ); ?></p>
											<?php if ( ! empty( $item['source'] ) ) : ?>
												<p class="requirement-source">
													<em><?php echo esc_html( sprintf( __( 'Required by: %s', 'as-php-checkup' ), $item['source'] ) ); ?></em>
												</p>
											<?php endif; ?>
										</td>
										<td class="column-current">
											<code><?php echo esc_html( $item['current'] ? $item['current'] : __( 'Not set', 'as-php-checkup' ) ); ?></code>
										</td>
										<td class="column-recommended">
											<code><?php echo esc_html( $item['recommended'] ); ?></code>
										</td>
										<td class="column-minimum">
											<code><?php echo esc_html( $item['minimum'] ); ?></code>
										</td>
										<td class="column-status">
											<span class="status-badge status-<?php echo esc_attr( $item['status'] ); ?>">
												<?php
												switch ( $item['status'] ) {
													case 'optimal':
														esc_html_e( 'Optimal', 'as-php-checkup' );
														break;
													case 'acceptable':
														esc_html_e( 'Acceptable', 'as-php-checkup' );
														break;
													case 'warning':
														esc_html_e( 'Needs Attention', 'as-php-checkup' );
														break;
												}
												?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</div>
			
			<div class="as-php-checkup-system-info">
				<h3><?php esc_html_e( 'System Information', 'as-php-checkup' ); ?></h3>
				<div class="system-info-grid">
					<div class="info-section">
						<h4><?php esc_html_e( 'WordPress', 'as-php-checkup' ); ?></h4>
						<table class="info-table">
							<tr>
								<td><?php esc_html_e( 'Version:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo esc_html( $system_info['wordpress']['version'] ); ?></code></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Multisite:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo $system_info['wordpress']['multisite'] ? esc_html__( 'Yes', 'as-php-checkup' ) : esc_html__( 'No', 'as-php-checkup' ); ?></code></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'WP Memory Limit:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo esc_html( $system_info['wordpress']['memory_limit'] ); ?></code></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Debug Mode:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo $system_info['wordpress']['debug_mode'] ? esc_html__( 'Enabled', 'as-php-checkup' ) : esc_html__( 'Disabled', 'as-php-checkup' ); ?></code></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Language:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo esc_html( $system_info['wordpress']['language'] ); ?></code></td>
							</tr>
						</table>
					</div>
					
					<div class="info-section">
						<h4><?php esc_html_e( 'Server', 'as-php-checkup' ); ?></h4>
						<table class="info-table">
							<tr>
								<td><?php esc_html_e( 'Software:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo esc_html( $system_info['server']['software'] ); ?></code></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'PHP Version:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo esc_html( $system_info['server']['php_version'] ); ?></code></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'MySQL Version:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo esc_html( $system_info['server']['mysql_version'] ); ?></code></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Est. Max Workers:', 'as-php-checkup' ); ?></td>
								<td><code><?php echo esc_html( $system_info['server']['max_workers'] ); ?></code></td>
							</tr>
						</table>
					</div>
					
					<div class="info-section">
						<h4><?php esc_html_e( 'PHP Extensions', 'as-php-checkup' ); ?></h4>
						<table class="info-table">
							<?php foreach ( $system_info['php_extensions'] as $extension => $loaded ) : ?>
								<tr>
									<td><?php echo esc_html( strtoupper( $extension ) ); ?>:</td>
									<td>
										<span class="extension-status <?php echo $loaded ? 'loaded' : 'not-loaded'; ?>">
											<?php echo $loaded ? esc_html__( '✓ Loaded', 'as-php-checkup' ) : esc_html__( '✗ Not loaded', 'as-php-checkup' ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				</div>
			</div>
			
			<div class="as-php-checkup-api-info">
				<h3><?php esc_html_e( 'API & CLI Access', 'as-php-checkup' ); ?></h3>
				<div class="api-info-content">
					<div class="api-section">
						<h4><?php esc_html_e( 'REST API Endpoints', 'as-php-checkup' ); ?></h4>
						<p class="description"><?php esc_html_e( 'Access PHP Checkup data programmatically via REST API:', 'as-php-checkup' ); ?></p>
						<table class="api-endpoints">
							<tr>
								<td><code>GET /wp-json/as-php-checkup/v1/status</code></td>
								<td><?php esc_html_e( 'Get current status and health score', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>GET /wp-json/as-php-checkup/v1/system-info</code></td>
								<td><?php esc_html_e( 'Get system information', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>GET /wp-json/as-php-checkup/v1/plugin-analysis</code></td>
								<td><?php esc_html_e( 'Get plugin requirements analysis', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>POST /wp-json/as-php-checkup/v1/refresh</code></td>
								<td><?php esc_html_e( 'Refresh the check', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>GET /wp-json/as-php-checkup/v1/export</code></td>
								<td><?php esc_html_e( 'Export report (JSON/CSV)', 'as-php-checkup' ); ?></td>
							</tr>
						</table>
					</div>
					
					<div class="cli-section">
						<h4><?php esc_html_e( 'WP-CLI Commands', 'as-php-checkup' ); ?></h4>
						<p class="description"><?php esc_html_e( 'Available WP-CLI commands:', 'as-php-checkup' ); ?></p>
						<table class="cli-commands">
							<tr>
								<td><code>wp as-php-checkup status</code></td>
								<td><?php esc_html_e( 'Check PHP configuration status', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>wp as-php-checkup analyze</code></td>
								<td><?php esc_html_e( 'Analyze plugin requirements', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>wp as-php-checkup system</code></td>
								<td><?php esc_html_e( 'Get system information', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>wp as-php-checkup export</code></td>
								<td><?php esc_html_e( 'Export report', 'as-php-checkup' ); ?></td>
							</tr>
							<tr>
								<td><code>wp as-php-checkup check</code></td>
								<td><?php esc_html_e( 'Quick check with exit codes', 'as-php-checkup' ); ?></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			
			<div class="as-php-checkup-footer">
				<p>
					<?php
					printf(
						/* translators: %s: Author name */
						esc_html__( 'AS PHP Checkup by %s', 'as-php-checkup' ),
						'<a href="https://mirschel.biz" target="_blank">Marc Mirschel</a>'
					);
					?>
					|
					<?php
					printf(
						/* translators: %s: Last check time */
						esc_html__( 'Last check: %s', 'as-php-checkup' ),
						'<span id="last-check-time">' . esc_html( current_time( 'mysql' ) ) . '</span>'
					);
					?>
					|
					<?php
					printf(
						/* translators: %s: Last analysis time */
						esc_html__( 'Plugin analysis: %s', 'as-php-checkup' ),
						'<span id="last-analysis-time">' . esc_html( human_time_diff( get_option( 'as_php_checkup_analysis_time', current_time( 'timestamp' ) ) ) ) . ' ' . esc_html__( 'ago', 'as-php-checkup' ) . '</span>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for refresh check
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_refresh_check() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		// Clear any cached data
		wp_cache_flush();
		
		// Update last check time
		update_option( 'as_php_checkup_last_check', current_time( 'timestamp' ) );
		
		// Get fresh results
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		
		wp_send_json_success( array(
			'message' => __( 'Check completed successfully', 'as-php-checkup' ),
			'results' => $results,
			'time'    => current_time( 'mysql' ),
		) );
	}

	/**
	 * AJAX handler for plugin analysis
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function ajax_analyze_plugins() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		// Re-analyze plugins
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$analyzed = $analyzer->analyze_all_plugins();
		
		// Get fresh results with new plugin requirements
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		
		wp_send_json_success( array(
			'message'  => sprintf(
				/* translators: %d: number of plugins */
				__( 'Analyzed %d plugin(s) successfully', 'as-php-checkup' ),
				count( $analyzed )
			),
			'results'  => $results,
			'analyzed' => $analyzed,
			'time'     => current_time( 'mysql' ),
		) );
	}

	/**
	 * AJAX handler for export report
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_export_report() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		$system_info = $checkup->get_system_info();
		
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$plugin_analysis = $analyzer->get_analyzed_data();
		
		// Generate CSV content
		$csv_content = $this->generate_csv_report( $results, $system_info, $plugin_analysis );
		
		// Generate filename
		$filename = 'php-checkup-report-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		
		wp_send_json_success( array(
			'content'  => $csv_content,
			'filename' => $filename,
		) );
	}

	/**
	 * Generate CSV report
	 *
	 * @since 1.0.0
	 * @param array $results Check results.
	 * @param array $system_info System information.
	 * @param array $plugin_analysis Plugin analysis data.
	 * @return string CSV content
	 */
	private function generate_csv_report( $results, $system_info, $plugin_analysis ) {
		$csv = array();
		
		// Add header
		$csv[] = 'AS PHP Checkup Report';
		$csv[] = 'Generated: ' . current_time( 'mysql' );
		$csv[] = 'Version: ' . AS_PHP_CHECKUP_VERSION;
		$csv[] = '';
		
		// Add column headers
		$csv[] = 'Category,Setting,Current Value,Recommended,Minimum,Status,Required By';
		
		// Add results
		foreach ( $results as $category_key => $category ) {
			foreach ( $category['items'] as $key => $item ) {
				$csv[] = sprintf(
					'%s,%s,%s,%s,%s,%s,%s',
					$category['label'],
					$item['label'],
					$item['current'] ? $item['current'] : 'Not set',
					$item['recommended'],
					$item['minimum'],
					ucfirst( $item['status'] ),
					! empty( $item['source'] ) ? $item['source'] : 'Base recommendation'
				);
			}
		}
		
		// Add plugin analysis
		if ( ! empty( $plugin_analysis ) ) {
			$csv[] = '';
			$csv[] = 'Plugin Requirements Analysis';
			$csv[] = 'Plugin,Requirement,Value';
			
			foreach ( $plugin_analysis as $plugin_file => $requirements ) {
				foreach ( $requirements as $key => $value ) {
					if ( 'name' !== $key ) {
						$csv[] = sprintf(
							'%s,%s,%s',
							$requirements['name'],
							$key,
							$value
						);
					}
				}
			}
		}
		
		// Add system info
		$csv[] = '';
		$csv[] = 'System Information';
		$csv[] = 'Component,Property,Value';
		
		// WordPress info
		foreach ( $system_info['wordpress'] as $key => $value ) {
			$label = ucwords( str_replace( '_', ' ', $key ) );
			$display_value = is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : $value;
			$csv[] = sprintf( 'WordPress,%s,%s', $label, $display_value );
		}
		
		// Server info
		foreach ( $system_info['server'] as $key => $value ) {
			$label = ucwords( str_replace( '_', ' ', $key ) );
			$csv[] = sprintf( 'Server,%s,%s', $label, $value );
		}
		
		// PHP Extensions
		foreach ( $system_info['php_extensions'] as $extension => $loaded ) {
			$csv[] = sprintf( 'PHP Extension,%s,%s', strtoupper( $extension ), $loaded ? 'Loaded' : 'Not Loaded' );
		}
		
		return implode( "\n", $csv );
	}
}