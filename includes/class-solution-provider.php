<?php
/**
 * Solution Provider Class
 *
 * @package AS_PHP_Checkup
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_PHP_Checkup_Solution_Provider class
 *
 * @since 1.2.0
 */
class AS_PHP_Checkup_Solution_Provider {

	/**
	 * Instance of this class
	 *
	 * @since 1.2.0
	 * @var AS_PHP_Checkup_Solution_Provider|null
	 */
	private static $instance = null;

	/**
	 * Detected server type
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $server_type = '';

	/**
	 * Hosting provider
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $hosting_provider = '';

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	private function __construct() {
		$this->detect_environment();
		
		// Add AJAX handlers
		add_action( 'wp_ajax_as_php_checkup_apply_solution', array( $this, 'ajax_apply_solution' ) );
		add_action( 'wp_ajax_as_php_checkup_download_config', array( $this, 'ajax_download_config' ) );
		add_action( 'wp_ajax_as_php_checkup_test_writeable', array( $this, 'ajax_test_writeable' ) );
	}

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 * @return AS_PHP_Checkup_Solution_Provider
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Detect server environment
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function detect_environment() {
		// Detect server type
		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_software = sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );
			
			if ( stripos( $server_software, 'nginx' ) !== false ) {
				$this->server_type = 'nginx';
			} elseif ( stripos( $server_software, 'apache' ) !== false ) {
				$this->server_type = 'apache';
			} elseif ( stripos( $server_software, 'litespeed' ) !== false ) {
				$this->server_type = 'litespeed';
			} elseif ( stripos( $server_software, 'iis' ) !== false ) {
				$this->server_type = 'iis';
			} else {
				$this->server_type = 'unknown';
			}
		}
		
		// Detect hosting provider
		$this->hosting_provider = $this->detect_hosting_provider();
	}

	/**
	 * Detect hosting provider
	 *
	 * @since 1.2.0
	 * @return string
	 */
	private function detect_hosting_provider() {
		// Check for common hosting provider indicators
		$indicators = array(
			'wpengine'     => array( 'WPE_APIKEY', 'WPE_CLUSTER_ID' ),
			'kinsta'       => array( 'KINSTA_CACHE_ZONE' ),
			'siteground'   => array( 'SITEGROUND_ACCOUNT_NAME' ),
			'cloudways'    => array( 'CLOUDWAYS_APPLICATION_NAME' ),
			'godaddy'      => array( 'GD_PHP_HANDLER' ),
			'bluehost'     => array( 'BLUEHOST_DOMAIN' ),
			'dreamhost'    => array( 'DH_USER' ),
			'hostgator'    => array( 'HOSTGATOR_BILLING' ),
			'wp_com'       => array( 'IS_WPCOM' ),
			'flywheel'     => array( 'FLYWHEEL_CONFIG_DIR' ),
			'pantheon'     => array( 'PANTHEON_ENVIRONMENT' ),
			'platformsh'   => array( 'PLATFORM_PROJECT' ),
			'gridpane'     => array( 'GRIDPANE' ),
			'runcloud'     => array( 'RUNCLOUD_APP_ID' ),
			'serverpilot'  => array( 'SERVERPILOT' ),
			'plesk'        => array( 'PLESK_VHOST_ID' ),
			'cpanel'       => array( 'CPANEL' ),
		);
		
		foreach ( $indicators as $provider => $env_vars ) {
			foreach ( $env_vars as $env_var ) {
				if ( getenv( $env_var ) !== false ) {
					return $provider;
				}
			}
		}
		
		// Check by file existence
		if ( file_exists( '/etc/wpengine' ) ) {
			return 'wpengine';
		}
		if ( file_exists( '/kinsta' ) ) {
			return 'kinsta';
		}
		
		// Check by function existence
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			return 'siteground';
		}
		
		return 'generic';
	}

	/**
	 * Get available solutions for current issues
	 *
	 * @since 1.2.0
	 * @param array $results Check results.
	 * @return array
	 */
	public function get_solutions( $results ) {
		$solutions = array();
		$issues = $this->collect_issues( $results );
		
		if ( empty( $issues ) ) {
			return $solutions;
		}
		
		// PHP.ini solution
		if ( $this->can_use_php_ini() ) {
			$solutions['php_ini'] = array(
				'type'        => 'php_ini',
				'title'       => __( 'Generate php.ini File', 'as-php-checkup' ),
				'description' => __( 'Create a custom php.ini file with optimal settings for your WordPress installation.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => $this->can_write_php_ini(),
				'icon'        => 'dashicons-admin-tools',
				'priority'    => 1,
			);
		}
		
		// .htaccess solution (Apache/LiteSpeed)
		if ( in_array( $this->server_type, array( 'apache', 'litespeed' ), true ) ) {
			$solutions['htaccess'] = array(
				'type'        => 'htaccess',
				'title'       => __( 'Update .htaccess File', 'as-php-checkup' ),
				'description' => __( 'Add PHP configuration directives to your .htaccess file.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => $this->can_write_htaccess(),
				'icon'        => 'dashicons-media-code',
				'priority'    => 2,
			);
		}
		
		// NGINX configuration
		if ( 'nginx' === $this->server_type ) {
			$solutions['nginx'] = array(
				'type'        => 'nginx',
				'title'       => __( 'NGINX Configuration', 'as-php-checkup' ),
				'description' => __( 'Generate NGINX configuration snippets for your server.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-networking',
				'priority'    => 3,
			);
		}
		
		// wp-config.php constants
		$solutions['wp_config'] = array(
			'type'        => 'wp_config',
			'title'       => __( 'WP-Config Constants', 'as-php-checkup' ),
			'description' => __( 'Add memory limit constants to your wp-config.php file.', 'as-php-checkup' ),
			'applicable'  => true,
			'auto_apply'  => $this->can_write_wp_config(),
			'icon'        => 'dashicons-wordpress-alt',
			'priority'    => 4,
		);
		
		// Hosting-specific solutions
		$hosting_solution = $this->get_hosting_specific_solution();
		if ( $hosting_solution ) {
			$solutions['hosting'] = $hosting_solution;
		}
		
		// Sort by priority
		uasort( $solutions, function( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );
		
		return $solutions;
	}

	/**
	 * Get hosting-specific solution
	 *
	 * @since 1.2.0
	 * @return array|false
	 */
	private function get_hosting_specific_solution() {
		$hosting_solutions = array(
			'wpengine' => array(
				'type'        => 'hosting_wpengine',
				'title'       => __( 'WP Engine Configuration', 'as-php-checkup' ),
				'description' => __( 'Instructions for configuring PHP settings in WP Engine dashboard.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'instructions' => array(
					__( 'Log in to your WP Engine User Portal', 'as-php-checkup' ),
					__( 'Navigate to your site environment', 'as-php-checkup' ),
					__( 'Click on "PHP Version" in the left menu', 'as-php-checkup' ),
					__( 'Select PHP 8.3 or higher', 'as-php-checkup' ),
					__( 'Contact support for memory limit increases', 'as-php-checkup' ),
				),
			),
			'kinsta' => array(
				'type'        => 'hosting_kinsta',
				'title'       => __( 'Kinsta Configuration', 'as-php-checkup' ),
				'description' => __( 'Configure PHP settings through MyKinsta dashboard.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'instructions' => array(
					__( 'Log in to MyKinsta dashboard', 'as-php-checkup' ),
					__( 'Select your site', 'as-php-checkup' ),
					__( 'Go to Tools > PHP Engine', 'as-php-checkup' ),
					__( 'Select PHP 8.3', 'as-php-checkup' ),
					__( 'Memory limit is automatically optimized', 'as-php-checkup' ),
				),
			),
			'siteground' => array(
				'type'        => 'hosting_siteground',
				'title'       => __( 'SiteGround Configuration', 'as-php-checkup' ),
				'description' => __( 'Use Site Tools to configure PHP settings.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'instructions' => array(
					__( 'Log in to SiteGround Site Tools', 'as-php-checkup' ),
					__( 'Navigate to Dev > PHP Manager', 'as-php-checkup' ),
					__( 'Select PHP 8.3', 'as-php-checkup' ),
					__( 'Go to PHP Variables to adjust limits', 'as-php-checkup' ),
					__( 'Save your changes', 'as-php-checkup' ),
				),
			),
			'cloudways' => array(
				'type'        => 'hosting_cloudways',
				'title'       => __( 'Cloudways Configuration', 'as-php-checkup' ),
				'description' => __( 'Configure PHP settings in Cloudways platform.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'instructions' => array(
					__( 'Log in to Cloudways Platform', 'as-php-checkup' ),
					__( 'Select your application', 'as-php-checkup' ),
					__( 'Go to Settings & Packages > Advanced', 'as-php-checkup' ),
					__( 'Modify PHP settings as needed', 'as-php-checkup' ),
					__( 'Save and restart PHP-FPM', 'as-php-checkup' ),
				),
			),
			'cpanel' => array(
				'type'        => 'hosting_cpanel',
				'title'       => __( 'cPanel Configuration', 'as-php-checkup' ),
				'description' => __( 'Use MultiPHP INI Editor in cPanel.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'instructions' => array(
					__( 'Log in to cPanel', 'as-php-checkup' ),
					__( 'Navigate to Software > Select PHP Version', 'as-php-checkup' ),
					__( 'Choose PHP 8.3', 'as-php-checkup' ),
					__( 'Click on "Options" tab', 'as-php-checkup' ),
					__( 'Adjust memory_limit and other settings', 'as-php-checkup' ),
					__( 'Or use MultiPHP INI Editor for detailed control', 'as-php-checkup' ),
				),
			),
			'plesk' => array(
				'type'        => 'hosting_plesk',
				'title'       => __( 'Plesk Configuration', 'as-php-checkup' ),
				'description' => __( 'Configure PHP settings in Plesk panel.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'instructions' => array(
					__( 'Log in to Plesk', 'as-php-checkup' ),
					__( 'Go to Websites & Domains', 'as-php-checkup' ),
					__( 'Click on PHP Settings', 'as-php-checkup' ),
					__( 'Select PHP 8.3 version', 'as-php-checkup' ),
					__( 'Adjust performance settings', 'as-php-checkup' ),
					__( 'Click OK to save', 'as-php-checkup' ),
				),
			),
			'gridpane' => array(
				'type'        => 'hosting_gridpane',
				'title'       => __( 'GridPane Configuration', 'as-php-checkup' ),
				'description' => __( 'Configure PHP settings via GridPane dashboard.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => true, // GridPane allows custom configs
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'custom_config' => true,
			),
			'runcloud' => array(
				'type'        => 'hosting_runcloud',
				'title'       => __( 'RunCloud Configuration', 'as-php-checkup' ),
				'description' => __( 'Use RunCloud dashboard to adjust PHP settings.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => true, // RunCloud allows custom configs
				'icon'        => 'dashicons-cloud',
				'priority'    => 0,
				'custom_config' => true,
			),
		);
		
		if ( isset( $hosting_solutions[ $this->hosting_provider ] ) ) {
			return $hosting_solutions[ $this->hosting_provider ];
		}
		
		// Generic hosting solution
		if ( 'generic' === $this->hosting_provider ) {
			return array(
				'type'        => 'hosting_generic',
				'title'       => __( 'Hosting Control Panel', 'as-php-checkup' ),
				'description' => __( 'Contact your hosting provider or check their control panel for PHP configuration options.', 'as-php-checkup' ),
				'applicable'  => true,
				'auto_apply'  => false,
				'icon'        => 'dashicons-admin-generic',
				'priority'    => 5,
			);
		}
		
		return false;
	}

	/**
	 * Collect issues from results
	 *
	 * @since 1.2.0
	 * @param array $results Check results.
	 * @return array
	 */
	private function collect_issues( $results ) {
		$issues = array();
		
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $key => $item ) {
				if ( in_array( $item['status'], array( 'warning', 'acceptable' ), true ) ) {
					$issues[ $key ] = $item;
				}
			}
		}
		
		return $issues;
	}

	/**
	 * Check if php.ini can be used
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function can_use_php_ini() {
		// Check if user.ini is loaded
		$ini_files = php_ini_loaded_file();
		$additional = php_ini_scanned_files();
		
		if ( strpos( $ini_files . $additional, 'user.ini' ) !== false ) {
			return true;
		}
		
		// Check if php.ini exists in WordPress root
		$php_ini_path = ABSPATH . 'php.ini';
		$user_ini_path = ABSPATH . '.user.ini';
		
		if ( file_exists( $php_ini_path ) || file_exists( $user_ini_path ) ) {
			return true;
		}
		
		// Check if hosting allows custom php.ini
		$hosting_allows = in_array( 
			$this->hosting_provider, 
			array( 'generic', 'cpanel', 'plesk', 'gridpane', 'runcloud' ),
			true
		);
		
		return $hosting_allows;
	}

	/**
	 * Check if php.ini can be written
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function can_write_php_ini() {
		$paths = array(
			ABSPATH . '.user.ini',
			ABSPATH . 'php.ini',
		);
		
		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return is_writable( $path );
			}
		}
		
		// Check if directory is writable
		return is_writable( ABSPATH );
	}

	/**
	 * Check if .htaccess can be written
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function can_write_htaccess() {
		$htaccess_path = ABSPATH . '.htaccess';
		
		if ( file_exists( $htaccess_path ) ) {
			return is_writable( $htaccess_path );
		}
		
		return is_writable( ABSPATH );
	}

	/**
	 * Check if wp-config.php can be written
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function can_write_wp_config() {
		$config_path = ABSPATH . 'wp-config.php';
		
		if ( ! file_exists( $config_path ) ) {
			$config_path = dirname( ABSPATH ) . '/wp-config.php';
		}
		
		return file_exists( $config_path ) && is_writable( $config_path );
	}

	/**
	 * Apply solution
	 *
	 * @since 1.2.0
	 * @param string $solution_type Solution type.
	 * @param array  $issues Current issues.
	 * @return array Result.
	 */
	public function apply_solution( $solution_type, $issues ) {
		$generator = AS_PHP_Checkup_Config_Generator::get_instance();
		
		switch ( $solution_type ) {
			case 'php_ini':
				return $this->apply_php_ini_solution( $generator, $issues );
				
			case 'htaccess':
				return $this->apply_htaccess_solution( $generator, $issues );
				
			case 'wp_config':
				return $this->apply_wp_config_solution( $generator, $issues );
				
			default:
				return array(
					'success' => false,
					'message' => __( 'Unknown solution type', 'as-php-checkup' ),
				);
		}
	}

	/**
	 * Apply PHP.ini solution
	 *
	 * @since 1.2.0
	 * @param AS_PHP_Checkup_Config_Generator $generator Config generator.
	 * @param array                            $issues Issues.
	 * @return array
	 */
	private function apply_php_ini_solution( $generator, $issues ) {
		$content = $generator->generate_php_ini( $issues );
		
		// Determine file path
		$file_path = ABSPATH . '.user.ini';
		if ( file_exists( ABSPATH . 'php.ini' ) ) {
			$file_path = ABSPATH . 'php.ini';
		}
		
		// Backup existing file
		if ( file_exists( $file_path ) ) {
			$backup_path = $file_path . '.backup-' . date( 'Y-m-d-H-i-s' );
			copy( $file_path, $backup_path );
		}
		
		// Write new content
		$result = file_put_contents( $file_path, $content );
		
		if ( false !== $result ) {
			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: file path */
					__( 'PHP configuration saved to %s', 'as-php-checkup' ),
					$file_path
				),
				'file_path' => $file_path,
			);
		}
		
		return array(
			'success' => false,
			'message' => __( 'Failed to write PHP configuration file', 'as-php-checkup' ),
		);
	}

	/**
	 * Apply .htaccess solution
	 *
	 * @since 1.2.0
	 * @param AS_PHP_Checkup_Config_Generator $generator Config generator.
	 * @param array                            $issues Issues.
	 * @return array
	 */
	private function apply_htaccess_solution( $generator, $issues ) {
		$htaccess_path = ABSPATH . '.htaccess';
		$directives = $generator->generate_htaccess_directives( $issues );
		
		// Read existing .htaccess
		$existing_content = '';
		if ( file_exists( $htaccess_path ) ) {
			$existing_content = file_get_contents( $htaccess_path );
			
			// Backup
			$backup_path = $htaccess_path . '.backup-' . date( 'Y-m-d-H-i-s' );
			copy( $htaccess_path, $backup_path );
		}
		
		// Check if our block already exists
		$start_marker = '# BEGIN AS PHP Checkup';
		$end_marker = '# END AS PHP Checkup';
		
		if ( strpos( $existing_content, $start_marker ) !== false ) {
			// Replace existing block
			$pattern = '/' . preg_quote( $start_marker, '/' ) . '.*?' . preg_quote( $end_marker, '/' ) . '/s';
			$new_content = preg_replace( $pattern, $directives, $existing_content );
		} else {
			// Add new block at the beginning
			$new_content = $directives . "\n\n" . $existing_content;
		}
		
		// Write updated content
		$result = file_put_contents( $htaccess_path, $new_content );
		
		if ( false !== $result ) {
			return array(
				'success' => true,
				'message' => __( '.htaccess file updated successfully', 'as-php-checkup' ),
				'file_path' => $htaccess_path,
			);
		}
		
		return array(
			'success' => false,
			'message' => __( 'Failed to update .htaccess file', 'as-php-checkup' ),
		);
	}

	/**
	 * Apply wp-config.php solution
	 *
	 * @since 1.2.0
	 * @param AS_PHP_Checkup_Config_Generator $generator Config generator.
	 * @param array                            $issues Issues.
	 * @return array
	 */
	private function apply_wp_config_solution( $generator, $issues ) {
		$config_path = ABSPATH . 'wp-config.php';
		if ( ! file_exists( $config_path ) ) {
			$config_path = dirname( ABSPATH ) . '/wp-config.php';
		}
		
		if ( ! file_exists( $config_path ) ) {
			return array(
				'success' => false,
				'message' => __( 'wp-config.php file not found', 'as-php-checkup' ),
			);
		}
		
		// Read existing config
		$config_content = file_get_contents( $config_path );
		
		// Backup
		$backup_path = $config_path . '.backup-' . date( 'Y-m-d-H-i-s' );
		copy( $config_path, $backup_path );
		
		// Generate constants
		$constants = $generator->generate_wp_config_constants( $issues );
		
		// Find position to insert (before "That's all, stop editing!")
		$insert_position = strpos( $config_content, "/* That's all, stop editing!" );
		if ( false === $insert_position ) {
			$insert_position = strlen( $config_content );
		}
		
		// Check if constants already exist
		$needs_update = false;
		foreach ( $constants as $constant => $value ) {
			if ( ! preg_match( "/define\s*\(\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]/", $config_content ) ) {
				$needs_update = true;
				break;
			}
		}
		
		if ( ! $needs_update ) {
			return array(
				'success' => true,
				'message' => __( 'wp-config.php already contains necessary constants', 'as-php-checkup' ),
			);
		}
		
		// Generate code to insert
		$code_to_insert = "\n/* AS PHP Checkup Optimizations */\n";
		foreach ( $constants as $constant => $value ) {
			if ( ! defined( $constant ) ) {
				$code_to_insert .= "define( '{$constant}', '{$value}' );\n";
			}
		}
		$code_to_insert .= "/* End AS PHP Checkup */\n\n";
		
		// Insert code
		$new_content = substr( $config_content, 0, $insert_position ) . 
		              $code_to_insert . 
		              substr( $config_content, $insert_position );
		
		// Write updated content
		$result = file_put_contents( $config_path, $new_content );
		
		if ( false !== $result ) {
			return array(
				'success' => true,
				'message' => __( 'wp-config.php updated successfully', 'as-php-checkup' ),
				'file_path' => $config_path,
			);
		}
		
		return array(
			'success' => false,
			'message' => __( 'Failed to update wp-config.php', 'as-php-checkup' ),
		);
	}

	/**
	 * AJAX handler for applying solution
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ajax_apply_solution() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		$solution_type = isset( $_POST['solution_type'] ) ? sanitize_text_field( wp_unslash( $_POST['solution_type'] ) ) : '';
		
		// Get current issues
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		$issues = $this->collect_issues( $results );
		
		// Apply solution
		$result = $this->apply_solution( $solution_type, $issues );
		
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for downloading configuration
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ajax_download_config() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		$config_type = isset( $_POST['config_type'] ) ? sanitize_text_field( wp_unslash( $_POST['config_type'] ) ) : '';
		
		// Get current issues
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		$issues = $this->collect_issues( $results );
		
		// Generate configuration
		$generator = AS_PHP_Checkup_Config_Generator::get_instance();
		$content = '';
		$filename = '';
		
		switch ( $config_type ) {
			case 'php_ini':
				$content = $generator->generate_php_ini( $issues );
				$filename = 'php.ini';
				break;
				
			case 'user_ini':
				$content = $generator->generate_php_ini( $issues );
				$filename = '.user.ini';
				break;
				
			case 'htaccess':
				$content = $generator->generate_htaccess_directives( $issues );
				$filename = 'htaccess-snippet.txt';
				break;
				
			case 'nginx':
				$content = $generator->generate_nginx_config( $issues );
				$filename = 'nginx-php-config.conf';
				break;
				
			case 'wp_config':
				$constants = $generator->generate_wp_config_constants( $issues );
				$content = "<?php\n/* Add these constants to your wp-config.php file */\n\n";
				foreach ( $constants as $constant => $value ) {
					$content .= "define( '{$constant}', '{$value}' );\n";
				}
				$filename = 'wp-config-constants.php';
				break;
				
			default:
				wp_send_json_error( __( 'Invalid configuration type', 'as-php-checkup' ) );
		}
		
		wp_send_json_success( array(
			'content'  => $content,
			'filename' => $filename,
			'type'     => $config_type,
		) );
	}

	/**
	 * AJAX handler for testing write permissions
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ajax_test_writeable() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'as-php-checkup' ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'as-php-checkup' ) );
		}
		
		$permissions = array(
			'php_ini'   => $this->can_write_php_ini(),
			'htaccess'  => $this->can_write_htaccess(),
			'wp_config' => $this->can_write_wp_config(),
		);
		
		wp_send_json_success( $permissions );
	}

	/**
	 * Get server type
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_server_type() {
		return $this->server_type;
	}

	/**
	 * Get hosting provider
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_hosting_provider() {
		return $this->hosting_provider;
	}
}