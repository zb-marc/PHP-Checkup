<?php
/**
 * AS PHP Checkup
 *
 * @package           AS_PHP_Checkup
 * @author            Marc Mirschel
 * @copyright         2025 Marc Mirschel
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       AS PHP Checkup
 * Plugin URI:        https://akkusys.de
 * Description:       Intelligent PHP configuration checker with automatic solution provider, one-click fixes, and configuration generators
 * Version:           1.2.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Marc Mirschel
 * Author URI:        https://mirschel.biz
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       as-php-checkup
 * Domain Path:       /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'AS_PHP_CHECKUP_VERSION', '1.2.1' );
define( 'AS_PHP_CHECKUP_PLUGIN_FILE', __FILE__ );
define( 'AS_PHP_CHECKUP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AS_PHP_CHECKUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AS_PHP_CHECKUP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes
 *
 * @since 1.1.0
 * @param string $class_name Class name to load.
 * @return void
 */
function as_php_checkup_autoloader( $class_name ) {
	// Check if it's our plugin's class
	if ( strpos( $class_name, 'AS_PHP_Checkup' ) !== 0 ) {
		return;
	}
	
	// Convert class name to file name
	$class_file = strtolower( str_replace( '_', '-', $class_name ) );
	$class_file = str_replace( 'as-php-checkup-', 'class-', $class_file );
	
	// Build file path
	$file_path = AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/' . $class_file . '.php';
	
	// Include file if it exists
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}
spl_autoload_register( 'as_php_checkup_autoloader' );

/**
 * Load Composer autoloader if available
 *
 * @since 1.1.0
 * @return void
 */
function as_php_checkup_load_composer() {
	$composer_autoload = AS_PHP_CHECKUP_PLUGIN_DIR . 'vendor/autoload.php';
	if ( file_exists( $composer_autoload ) ) {
		require_once $composer_autoload;
	}
}
add_action( 'plugins_loaded', 'as_php_checkup_load_composer', 5 );

/**
 * Load plugin textdomain
 *
 * @since 1.0.0
 * @return void
 */
function as_php_checkup_load_textdomain() {
	load_plugin_textdomain(
		'as-php-checkup',
		false,
		dirname( AS_PHP_CHECKUP_PLUGIN_BASENAME ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'as_php_checkup_load_textdomain' );

/**
 * Include required files
 *
 * @since 1.0.0
 * @return void
 */
function as_php_checkup_includes() {
	// Core classes
	require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-checkup.php';
	require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-plugin-analyzer.php';
	
	// Solution providers - New in 1.2.0
	require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-solution-provider.php';
	require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-config-generator.php';
	
	// REST API
	require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-rest-controller.php';
	
	// Dashboard Widget
	require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-dashboard-widget.php';
	
	// Admin
	if ( is_admin() ) {
		require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'admin/admin-page.php';
	}
	
	// WP-CLI
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-cli-command.php';
		WP_CLI::add_command( 'as-php-checkup', 'AS_PHP_Checkup_CLI_Command' );
	}
}
add_action( 'plugins_loaded', 'as_php_checkup_includes' );

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 * @return void
 */
function as_php_checkup_init() {
	// Initialize the checkup class
	AS_PHP_Checkup::get_instance();
	
	// Initialize plugin analyzer
	AS_PHP_Checkup_Plugin_Analyzer::get_instance();
	
	// Initialize solution provider - New in 1.2.0
	AS_PHP_Checkup_Solution_Provider::get_instance();
	AS_PHP_Checkup_Config_Generator::get_instance();
	
	// Initialize REST API
	AS_PHP_Checkup_REST_Controller::get_instance();
	
	// Initialize Dashboard Widget
	AS_PHP_Checkup_Dashboard_Widget::get_instance();
	
	// Initialize admin if in admin area
	if ( is_admin() ) {
		AS_PHP_Checkup_Admin::get_instance();
	}
}
add_action( 'init', 'as_php_checkup_init' );

/**
 * Register REST API routes
 *
 * @since 1.1.0
 * @return void
 */
function as_php_checkup_rest_api_init() {
	$controller = AS_PHP_Checkup_REST_Controller::get_instance();
	$controller->register_routes();
}
add_action( 'rest_api_init', 'as_php_checkup_rest_api_init' );

/**
 * Plugin activation hook
 *
 * @since 1.0.0
 * @return void
 */
function as_php_checkup_activate() {
	// Set default options if not exists
	if ( ! get_option( 'as_php_checkup_last_check' ) ) {
		update_option( 'as_php_checkup_last_check', current_time( 'timestamp' ) );
	}
	
	// Set default widget options
	if ( ! get_option( 'as_php_checkup_widget_options' ) ) {
		update_option( 'as_php_checkup_widget_options', array(
			'auto_refresh'      => true,
			'show_critical'     => true,
			'show_plugin_info'  => true,
			'compact_mode'      => false,
		) );
	}
	
	// Schedule daily plugin analysis
	if ( ! wp_next_scheduled( 'as_php_checkup_daily_analysis' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'as_php_checkup_daily_analysis' );
	}
	
	// Clear any cached data
	wp_cache_flush();
	
	// Run initial plugin analysis
	AS_PHP_Checkup_Plugin_Analyzer::get_instance()->analyze_all_plugins();
}
register_activation_hook( __FILE__, 'as_php_checkup_activate' );

/**
 * Plugin deactivation hook
 *
 * @since 1.0.0
 * @return void
 */
function as_php_checkup_deactivate() {
	// Clean up scheduled tasks
	wp_clear_scheduled_hook( 'as_php_checkup_daily_analysis' );
}
register_deactivation_hook( __FILE__, 'as_php_checkup_deactivate' );

/**
 * Add plugin action links
 *
 * @since 1.0.0
 * @return array Modified action links
 */
function as_php_checkup_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=as-php-checkup' ) ),
		esc_html__( 'Check Status', 'as-php-checkup' )
	);
	
	$dashboard_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'index.php' ) ),
		esc_html__( 'Dashboard Widget', 'as-php-checkup' )
	);
	
	array_unshift( $links, $settings_link, $dashboard_link );
	
	return $links;
}
add_filter( 'plugin_action_links_' . AS_PHP_CHECKUP_PLUGIN_BASENAME, 'as_php_checkup_action_links' );

/**
 * Daily plugin analysis cron job
 *
 * @since 1.1.0
 * @return void
 */
function as_php_checkup_run_daily_analysis() {
	AS_PHP_Checkup_Plugin_Analyzer::get_instance()->analyze_all_plugins();
}
add_action( 'as_php_checkup_daily_analysis', 'as_php_checkup_run_daily_analysis' );

/**
 * Add admin notice for critical issues - New in 1.2.0
 *
 * @since 1.2.0
 * @return void
 */
function as_php_checkup_admin_notices() {
	// Only show on dashboard and for admins
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	$screen = get_current_screen();
	if ( ! $screen || 'dashboard' !== $screen->id ) {
		return;
	}
	
	// Check if there are critical issues
	$checkup = AS_PHP_Checkup::get_instance();
	$results = $checkup->get_check_results();
	
	$critical_count = 0;
	foreach ( $results as $category ) {
		foreach ( $category['items'] as $item ) {
			if ( 'warning' === $item['status'] ) {
				$critical_count++;
			}
		}
	}
	
	if ( $critical_count > 0 ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'PHP Configuration Alert:', 'as-php-checkup' ); ?></strong>
				<?php
				printf(
					/* translators: 1: number of issues, 2: link to checkup page */
					esc_html__( 'Your site has %1$d PHP configuration issues that need attention. %2$s', 'as-php-checkup' ),
					$critical_count,
					'<a href="' . esc_url( admin_url( 'tools.php?page=as-php-checkup' ) ) . '">' . esc_html__( 'View Solutions →', 'as-php-checkup' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'as_php_checkup_admin_notices' );