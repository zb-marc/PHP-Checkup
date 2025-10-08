<?php
/**
 * Dashboard Widget Handler
 *
 * @package AS_PHP_Checkup
 * @since 1.1.0
 * @version 1.3.1
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
	private $widget_id = 'as_php_checkup_dashboard';

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	private function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_widget_assets' ) );
		add_action( 'wp_ajax_as_php_checkup_refresh_widget', array( $this, 'ajax_refresh_widget' ) );
		add_action( 'wp_ajax_as_php_checkup_toggle_widget_view', array( $this, 'ajax_toggle_widget_view' ) );
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
	 * Register dashboard widget
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function register_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			$this->widget_id,
			__( 'PHP Configuration Status', 'as-php-checkup' ),
			array( $this, 'render_widget' )
		);

		// Move widget to top
		global $wp_meta_boxes;
		$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		
		if ( isset( $dashboard[ $this->widget_id ] ) ) {
			$widget_backup = $dashboard[ $this->widget_id ];
			unset( $dashboard[ $this->widget_id ] );
			$dashboard = array_merge( array( $this->widget_id => $widget_backup ), $dashboard );
			$wp_meta_boxes['dashboard']['normal']['core'] = $dashboard;
		}
	}

/**
 * Render widget content
 *
 * @since 1.1.0
 * @version 1.3.2 - Fixed critical issues to only show errors, not warnings
 * @return void
 */
public function render_widget() {
	$checkup = AS_PHP_Checkup::get_instance();
	$results = $checkup->get_check_results();
	
	// Calculate status counts
	$status_counts = array(
		'ok'      => 0,
		'warning' => 0,
		'error'   => 0,
	);
	
	$critical_issues = array();  // Only for errors
	$warnings = array();          // Separate array for warnings
	$total_checks = 0;
	
	foreach ( $results as $category ) {
		foreach ( $category['items'] as $item ) {
			$total_checks++;
			
			// Map status correctly
			if ( 'ok' === $item['status'] ) {
				$status_counts['ok']++;
			} elseif ( 'warning' === $item['status'] ) {
				$status_counts['warning']++;
				// Warnings are NOT critical - store them separately if needed
				$warnings[] = array(
					'label'   => $item['label'],
					'current' => $item['current'],
					'needed'  => $item['recommended'],
				);
			} elseif ( 'error' === $item['status'] ) {
				$status_counts['error']++;
				// Only errors are critical issues
				$critical_issues[] = array(
					'label'   => $item['label'],
					'current' => $item['current'],
					'needed'  => $item['recommended'],
				);
			}
		}
	}
		
		// Calculate health score
		$health_score = $checkup->get_health_score();
		
		// Get last check time
		$last_check = get_option( 'as_php_checkup_last_check', current_time( 'timestamp' ) );
		$time_diff = human_time_diff( $last_check, current_time( 'timestamp' ) );
		
		// Get plugin analysis info
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$analysis_report = $analyzer->get_analysis_report();
		$plugins_analyzed = isset( $analysis_report['total_plugins'] ) ? $analysis_report['total_plugins'] : 0;
		
		// Output widget HTML - Clean structure without any floating elements
		echo '<div class="as-php-checkup-widget-container">';
		
		// Health Score Circle
		echo '<div class="widget-health-score">';
		echo '<div class="health-circle ' . esc_attr( $this->get_health_class( $health_score ) ) . '">';
		echo '<svg viewBox="0 0 36 36" class="circular-chart">';
		echo '<path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />';
		echo '<path class="circle" stroke-dasharray="' . esc_attr( $health_score ) . ', 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />';
		echo '<text x="18" y="21.5" class="percentage">' . esc_html( $health_score ) . '%</text>';
		echo '</svg>';
		echo '<div class="health-label">' . esc_html__( 'HEALTH', 'as-php-checkup' ) . '</div>';
		echo '</div>';
		echo '</div>';
		
		// Stats Grid
		echo '<div class="widget-stats-grid">';
		
		// PHP Version
		echo '<div class="stat-box">';
		echo '<div class="stat-value">' . esc_html( PHP_VERSION ) . '</div>';
		echo '<div class="stat-label">' . esc_html__( 'PHP VERSION', 'as-php-checkup' ) . '</div>';
		echo '</div>';
		
		// Last Check
		echo '<div class="stat-box">';
		echo '<div class="stat-value">' . esc_html( $time_diff ) . '</div>';
		echo '<div class="stat-label">' . esc_html__( 'LAST CHECK', 'as-php-checkup' ) . '</div>';
		echo '</div>';
		
		// Plugins Analyzed
		echo '<div class="stat-box">';
		echo '<div class="stat-value">' . esc_html( $plugins_analyzed ) . '</div>';
		echo '<div class="stat-label">' . esc_html__( 'PLUGINS ANALYZED', 'as-php-checkup' ) . '</div>';
		echo '</div>';
		
		echo '</div>'; // End stats grid
		
		// Status Bar
		echo '<div class="widget-status-bar">';
		
		if ( $total_checks > 0 ) {
			$ok_width = round( ( $status_counts['ok'] / $total_checks ) * 100, 1 );
			$warning_width = round( ( $status_counts['warning'] / $total_checks ) * 100, 1 );
			$error_width = round( ( $status_counts['error'] / $total_checks ) * 100, 1 );
			
			if ( $ok_width > 0 ) {
				echo '<div class="status-segment ok" style="width: ' . esc_attr( $ok_width ) . '%;" title="' . 
					 esc_attr( sprintf( __( '%d Passed', 'as-php-checkup' ), $status_counts['ok'] ) ) . '">';
				echo '<span>' . esc_html( $status_counts['ok'] ) . '</span>';
				echo '</div>';
			}
			
			if ( $warning_width > 0 ) {
				echo '<div class="status-segment warning" style="width: ' . esc_attr( $warning_width ) . '%;" title="' . 
					 esc_attr( sprintf( __( '%d Warnings', 'as-php-checkup' ), $status_counts['warning'] ) ) . '">';
				echo '<span>' . esc_html( $status_counts['warning'] ) . '</span>';
				echo '</div>';
			}
			
			// if ( $error_width > 0 ) {
			// 	echo '<div class="status-segment error" style="width: ' . esc_attr( $error_width ) . '%;" title="' . 
			// 		 esc_attr( sprintf( __( '%d Failed', 'as-php-checkup' ), $status_counts['error'] ) ) . '">';
			// 	echo '<span>' . esc_html( $status_counts['error'] ) . '</span>';
			// 	echo '</div>';
			// }
		}
		
		echo '</div>'; // End status bar
		
		// Status Legend
		echo '<div class="widget-status-legend">';
		echo '<span class="legend-item ok">● ' . sprintf( esc_html__( 'Passed: %d', 'as-php-checkup' ), $status_counts['ok'] ) . '</span>';
		echo '<span class="legend-item warning">● ' . sprintf( esc_html__( 'Warnings: %d', 'as-php-checkup' ), $status_counts['warning'] ) . '</span>';
		echo '<span class="legend-item error">● ' . sprintf( esc_html__( 'Failed: %d', 'as-php-checkup' ), $status_counts['error'] ) . '</span>';
		echo '</div>';
		
		// Critical Issues Section - Only show if there are REAL errors
	if ( ! empty( $critical_issues ) ) {
		echo '<div class="widget-critical-section">';
		echo '<h4>' . esc_html__( 'CRITICAL ISSUES', 'as-php-checkup' ) . ' (' . count( $critical_issues ) . ')</h4>';
		
		$displayed_issues = array_slice( $critical_issues, 0, 3 );
		foreach ( $displayed_issues as $issue ) {
			echo '<div class="critical-issue">';
			echo '<div class="issue-name">' . esc_html( $issue['label'] ) . '</div>';
			echo '<div class="issue-values">';
			echo '<span class="current-val">' . esc_html( $issue['current'] ?: 'Not set' ) . '</span>';
			echo '<span class="arrow">→</span>';
			echo '<span class="needed-val">' . esc_html( $issue['needed'] ) . '</span>';
			echo '</div>';
			echo '</div>';
		}
		
		if ( count( $critical_issues ) > 3 ) {
			echo '<div class="more-issues">... ' . sprintf( 
				esc_html__( 'and %d more', 'as-php-checkup' ),
				count( $critical_issues ) - 3 
			) . '</div>';
		}
		
		echo '</div>'; // End critical section
	}
	
	// Optional: Show warnings section separately if desired
	if ( ! empty( $warnings ) && false ) { // Set to true if you want to show warnings separately
		echo '<div class="widget-warnings-section">';
		echo '<h4>' . esc_html__( 'WARNINGS', 'as-php-checkup' ) . ' (' . count( $warnings ) . ')</h4>';
		// ... Display warnings here if needed ...
		echo '</div>';
	}
		
		// Action Buttons
		echo '<div class="widget-actions">';
		echo '<a href="' . esc_url( admin_url( 'tools.php?page=as-php-checkup' ) ) . '" class="button button-primary">';
		echo '<span class="dashicons dashicons-admin-tools"></span> ' . esc_html__( 'View Details', 'as-php-checkup' );
		echo '</a>';
		echo '<button class="button refresh-widget" data-nonce="' . esc_attr( wp_create_nonce( 'as_php_checkup_widget' ) ) . '">';
		echo '<span class="dashicons dashicons-update"></span> ' . esc_html__( 'Refresh', 'as-php-checkup' );
		echo '</button>';
		echo '</div>';
		
		// Auto-refresh indicator
		echo '<div class="widget-footer">';
		echo '<span class="auto-refresh-indicator">● ' . esc_html__( 'Auto-refresh enabled', 'as-php-checkup' ) . '</span>';
		echo '</div>';
		
		echo '</div>'; // End widget container
	}

	/**
	 * Get health score class
	 *
	 * @since 1.1.0
	 * @param int $score Health score.
	 * @return string
	 */
	private function get_health_class( $score ) {
		if ( $score >= 90 ) {
			return 'excellent';
		} elseif ( $score >= 70 ) {
			return 'good';
		} elseif ( $score >= 50 ) {
			return 'warning';
		}
		return 'critical';
	}

	/**
	 * Enqueue widget assets
	 *
	 * @since 1.1.0
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_widget_assets( $hook_suffix ) {
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}

		// Check if scripts are registered before enqueueing
		if ( ! wp_script_is( 'as-php-checkup-widget', 'registered' ) ) {
			wp_register_script(
				'as-php-checkup-widget',
				AS_PHP_CHECKUP_PLUGIN_URL . 'assets/js/widget-script.js',
				array( 'jquery', 'dashboard' ),
				AS_PHP_CHECKUP_VERSION,
				true
			);
		}
		
		wp_enqueue_script( 'as-php-checkup-widget' );
		
		wp_localize_script( 'as-php-checkup-widget', 'asPhpCheckupWidget', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'as_php_checkup_widget' ),
			'strings' => array(
				'refreshing'     => __( 'Refreshing...', 'as-php-checkup' ),
				'refresh_error'  => __( 'Error refreshing data', 'as-php-checkup' ),
			),
		) );

		if ( ! wp_style_is( 'as-php-checkup-widget', 'registered' ) ) {
			wp_register_style(
				'as-php-checkup-widget',
				AS_PHP_CHECKUP_PLUGIN_URL . 'assets/css/widget-style.css',
				array(),
				AS_PHP_CHECKUP_VERSION
			);
		}
		
		wp_enqueue_style( 'as-php-checkup-widget' );
	}

	/**
	 * AJAX handler to refresh widget
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function ajax_refresh_widget() {
		check_ajax_referer( 'as_php_checkup_widget', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'as-php-checkup' ) );
		}

		// Clear cache and get fresh data
		$checkup = AS_PHP_Checkup::get_instance();
		$checkup->clear_cache();
		
		// Render widget content
		ob_start();
		$this->render_widget();
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
		) );
	}

	/**
	 * AJAX handler to toggle widget view
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function ajax_toggle_widget_view() {
		check_ajax_referer( 'as_php_checkup_widget', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'as-php-checkup' ) );
		}

		// For now, just refresh the widget
		ob_start();
		$this->render_widget();
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
		) );
	}
}