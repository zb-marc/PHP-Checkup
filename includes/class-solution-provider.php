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
	
	// Include Security Trait for enhanced security - New in 1.3.0
	use AS_PHP_Checkup_Solution_Provider_Secure;

	/**
	 * Instance of this class
	 *
	 * @since 1.2.0
	 * @var AS_PHP_Checkup_Solution_Provider|null
	 */
	private static $instance = null;

	/**
	 * Available solution types
	 *
	 * @since 1.2.0
	 * @var array
	 */
	private $solution_types = array();

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	private function __construct() {
		$this->init_solution_types();
		add_action( 'wp_ajax_as_php_checkup_apply_solution', array( $this, 'ajax_apply_solution' ) );
		add_action( 'wp_ajax_as_php_checkup_download_config', array( $this, 'ajax_download_config' ) );
		add_action( 'wp_ajax_as_php_checkup_test_write', array( $this, 'ajax_test_write_permissions' ) );
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
	 * Initialize solution types based on server detection
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function init_solution_types() {
		$server_info = $this->detect_server_environment();
		
		// Common solutions available for all environments
		$this->solution_types = array(
			'php_ini' => array(
				'label'       => __( 'PHP.ini Configuration', 'as-php-checkup' ),
				'description' => __( 'Direct PHP.ini modifications', 'as-php-checkup' ),
				'available'   => $this->can_modify_php_ini(),
				'priority'    => 10,
			),
			'user_ini' => array(
				'label'       => __( '.user.ini Configuration', 'as-php-checkup' ),
				'description' => __( 'Per-directory PHP settings', 'as-php-checkup' ),
				'available'   => $this->can_use_user_ini(),
				'priority'    => 20,
			),
			'htaccess' => array(
				'label'       => __( '.htaccess Rules', 'as-php-checkup' ),
				'description' => __( 'Apache/LiteSpeed configuration', 'as-php-checkup' ),
				'available'   => $this->can_use_htaccess(),
				'priority'    => 30,
			),
			'wp_config' => array(
				'label'       => __( 'wp-config.php Constants', 'as-php-checkup' ),
				'description' => __( 'WordPress configuration constants', 'as-php-checkup' ),
				'available'   => true,
				'priority'    => 40,
			),
		);

		// Add server-specific solutions
		if ( 'nginx' === $server_info['type'] ) {
			$this->solution_types['nginx'] = array(
				'label'       => __( 'NGINX Configuration', 'as-php-checkup' ),
				'description' => __( 'NGINX server configuration', 'as-php-checkup' ),
				'available'   => true,
				'priority'    => 25,
			);
		}

		// Add hosting-specific solutions
		if ( ! empty( $server_info['hosting'] ) ) {
			$hosting_solutions = $this->get_hosting_specific_solutions( $server_info['hosting'] );
			$this->solution_types = array_merge( $this->solution_types, $hosting_solutions );
		}
	}

	/**
	 * Detect server environment
	 *
	 * @since 1.2.0
	 * @return array
	 */
	private function detect_server_environment() {
		$server_info = array(
			'type'    => 'unknown',
			'hosting' => '',
			'php_sapi' => php_sapi_name(),
		);

		// Detect web server type
		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_software = sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );
			
			if ( stripos( $server_software, 'apache' ) !== false ) {
				$server_info['type'] = 'apache';
			} elseif ( stripos( $server_software, 'nginx' ) !== false ) {
				$server_info['type'] = 'nginx';
			} elseif ( stripos( $server_software, 'litespeed' ) !== false ) {
				$server_info['type'] = 'litespeed';
			} elseif ( stripos( $server_software, 'iis' ) !== false ) {
				$server_info['type'] = 'iis';
			}
		}

		// Detect managed hosting
		$server_info['hosting'] = $this->detect_hosting_provider();

		return $server_info;
	}

	/**
	 * Detect hosting provider
	 *
	 * @since 1.2.0
	 * @return string
	 */
	private function detect_hosting_provider() {
		// Check for WP Engine
		if ( defined( 'WPE_APIKEY' ) || function_exists( 'is_wpe' ) ) {
			return 'wpengine';
		}

		// Check for Kinsta
		if ( defined( 'KINSTA_CACHE_ZONE' ) || isset( $_SERVER['KINSTA_CACHE_ZONE'] ) ) {
			return 'kinsta';
		}

		// Check for SiteGround
		if ( isset( $_SERVER['SITEGROUND_ACCOUNT_NAME'] ) || defined( 'SG_ACCOUNT_NAME' ) ) {
			return 'siteground';
		}

		// Check for Cloudways
		if ( isset( $_SERVER['cw_allowed_ip'] ) || isset( $_SERVER['HTTP_X_CLOUDWAYS_APP'] ) ) {
			return 'cloudways';
		}

		// Check for GoDaddy
		if ( isset( $_SERVER['GDHOST'] ) || defined( 'GD_SYSTEM_PLUGIN_DIR' ) ) {
			return 'godaddy';
		}

		// Check for Bluehost
		if ( isset( $_SERVER['BLUEHOST'] ) || file_exists( '/home/.sites/auth' ) ) {
			return 'bluehost';
		}

		// Check for DreamHost
		if ( isset( $_SERVER['DH_USER'] ) || strpos( $_SERVER['SERVER_SOFTWARE'] ?? '', 'DreamHost' ) !== false ) {
			return 'dreamhost';
		}

		// Check for HostGator
		if ( isset( $_SERVER['HOSTGATOR'] ) || strpos( gethostname(), 'hostgator' ) !== false ) {
			return 'hostgator';
		}

		// Check for WordPress.com
		if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			return 'wpcom';
		}

		// Check for Flywheel
		if ( isset( $_SERVER['FLYWHEEL_CONFIG_DIR'] ) || function_exists( 'fw_cache_flush' ) ) {
			return 'flywheel';
		}

		// Check for Pantheon
		if ( isset( $_ENV['PANTHEON_ENVIRONMENT'] ) || defined( 'PANTHEON_ENVIRONMENT' ) ) {
			return 'pantheon';
		}

		// Check for Platform.sh
		if ( isset( $_ENV['PLATFORM_PROJECT'] ) || isset( $_ENV['PLATFORM_BRANCH'] ) ) {
			return 'platformsh';
		}

		// Check for GridPane
		if ( file_exists( '/etc/gridpane/gridpane.conf' ) || isset( $_SERVER['GP_STACK'] ) ) {
			return 'gridpane';
		}

		// Check for RunCloud
		if ( isset( $_SERVER['RUNCLOUD_APP_ID'] ) || file_exists( '/etc/runcloud/runcloud.conf' ) ) {
			return 'runcloud';
		}

		// Check for ServerPilot
		if ( isset( $_SERVER['SERVERPILOT'] ) || file_exists( '/etc/serverpilot/serverpilot.conf' ) ) {
			return 'serverpilot';
		}

		// Check for Plesk
		if ( file_exists( '/usr/local/psa/version' ) || isset( $_SERVER['PLESK_VHOSTS_DIR'] ) ) {
			return 'plesk';
		}

		// Check for cPanel
		if ( file_exists( '/usr/local/cpanel/version' ) || isset( $_SERVER['CPANEL'] ) ) {
			return 'cpanel';
		}

		return '';
	}

	/**
	 * Get hosting-specific solutions
	 *
	 * @since 1.2.0
	 * @param string $hosting Hosting provider.
	 * @return array
	 */
	private function get_hosting_specific_solutions( $hosting ) {
		$solutions = array();

		switch ( $hosting ) {
			case 'wpengine':
				$solutions['hosting_wpengine'] = array(
					'label'       => __( 'WP Engine Configuration', 'as-php-checkup' ),
					'description' => __( 'WP Engine specific settings via User Portal', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_wpengine_instructions(),
				);
				break;

			case 'kinsta':
				$solutions['hosting_kinsta'] = array(
					'label'       => __( 'Kinsta Configuration', 'as-php-checkup' ),
					'description' => __( 'Kinsta MyKinsta dashboard settings', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_kinsta_instructions(),
				);
				break;

			case 'siteground':
				$solutions['hosting_siteground'] = array(
					'label'       => __( 'SiteGround Configuration', 'as-php-checkup' ),
					'description' => __( 'SiteGround Site Tools configuration', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_siteground_instructions(),
				);
				break;

			case 'cloudways':
				$solutions['hosting_cloudways'] = array(
					'label'       => __( 'Cloudways Configuration', 'as-php-checkup' ),
					'description' => __( 'Cloudways platform settings', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_cloudways_instructions(),
				);
				break;

			case 'cpanel':
				$solutions['hosting_cpanel'] = array(
					'label'       => __( 'cPanel Configuration', 'as-php-checkup' ),
					'description' => __( 'cPanel MultiPHP settings', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_cpanel_instructions(),
				);
				break;

			case 'plesk':
				$solutions['hosting_plesk'] = array(
					'label'       => __( 'Plesk Configuration', 'as-php-checkup' ),
					'description' => __( 'Plesk PHP settings', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_plesk_instructions(),
				);
				break;

			case 'gridpane':
				$solutions['hosting_gridpane'] = array(
					'label'       => __( 'GridPane Configuration', 'as-php-checkup' ),
					'description' => __( 'GridPane PHP configuration', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_gridpane_instructions(),
				);
				break;

			case 'runcloud':
				$solutions['hosting_runcloud'] = array(
					'label'       => __( 'RunCloud Configuration', 'as-php-checkup' ),
					'description' => __( 'RunCloud PHP settings', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 5,
					'instructions' => $this->get_runcloud_instructions(),
				);
				break;

			default:
				$solutions['hosting_generic'] = array(
					'label'       => __( 'Hosting Control Panel', 'as-php-checkup' ),
					'description' => __( 'Contact your hosting provider or use control panel', 'as-php-checkup' ),
					'available'   => true,
					'priority'    => 50,
					'instructions' => $this->get_generic_hosting_instructions(),
				);
				break;
		}

		return $solutions;
	}

	/**
	 * Check if PHP.ini can be modified
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function can_modify_php_ini() {
		$ini_path = php_ini_loaded_file();
		if ( ! $ini_path ) {
			return false;
		}

		// Check if we can write to the file
		if ( ! is_writable( $ini_path ) ) {
			return false;
		}

		// Check if we're in a managed hosting environment that doesn't allow it
		$hosting = $this->detect_hosting_provider();
		$restricted_hosts = array( 'wpengine', 'kinsta', 'flywheel', 'wpcom', 'pantheon' );
		
		if ( in_array( $hosting, $restricted_hosts, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if .user.ini can be used
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function can_use_user_ini() {
		// Check if PHP is running as CGI/FastCGI
		$sapi = php_sapi_name();
		if ( strpos( $sapi, 'cgi' ) === false && strpos( $sapi, 'fpm' ) === false ) {
			return false;
		}

		// Check if user.ini files are enabled
		$user_ini_filename = ini_get( 'user_ini.filename' );
		if ( empty( $user_ini_filename ) ) {
			return false;
		}

		// Check if we can write to the WordPress root
		if ( ! is_writable( ABSPATH ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if .htaccess can be used
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function can_use_htaccess() {
		// Check if it's Apache or LiteSpeed
		$server_info = $this->detect_server_environment();
		if ( ! in_array( $server_info['type'], array( 'apache', 'litespeed' ), true ) ) {
			return false;
		}

		// Check if .htaccess file exists and is writable
		$htaccess_file = ABSPATH . '.htaccess';
		if ( file_exists( $htaccess_file ) && ! is_writable( $htaccess_file ) ) {
			return false;
		} elseif ( ! file_exists( $htaccess_file ) && ! is_writable( ABSPATH ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get available solutions
	 *
	 * @since 1.2.0
	 * @return array
	 */
	public function get_available_solutions() {
		// Sort by priority
		uasort( $this->solution_types, function( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		return array_filter( $this->solution_types, function( $solution ) {
			return $solution['available'];
		} );
	}

	/**
	 * Generate solution for a specific issue
	 *
	 * @since 1.2.0
	 * @param string $setting PHP setting name.
	 * @param mixed  $recommended_value Recommended value.
	 * @param mixed  $current_value Current value.
	 * @param string $solution_type Type of solution.
	 * @return array
	 */
	public function generate_solution( $setting, $recommended_value, $current_value, $solution_type ) {
		// Validate solution type with security trait - Enhanced in 1.3.0
		if ( ! $this->validate_solution_type( $solution_type ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid solution type', 'as-php-checkup' ),
			);
		}

		$solution = array(
			'setting'          => $setting,
			'recommended'      => $recommended_value,
			'current'          => $current_value,
			'type'            => $solution_type,
			'code'            => '',
			'instructions'    => '',
			'can_auto_apply'  => false,
		);

		switch ( $solution_type ) {
			case 'php_ini':
				$solution['code'] = $this->generate_php_ini_code( $setting, $recommended_value );
				$solution['instructions'] = $this->get_php_ini_instructions();
				$solution['can_auto_apply'] = $this->can_modify_php_ini();
				break;

			case 'user_ini':
				$solution['code'] = $this->generate_user_ini_code( $setting, $recommended_value );
				$solution['instructions'] = $this->get_user_ini_instructions();
				$solution['can_auto_apply'] = $this->can_use_user_ini();
				break;

			case 'htaccess':
				$solution['code'] = $this->generate_htaccess_code( $setting, $recommended_value );
				$solution['instructions'] = $this->get_htaccess_instructions();
				$solution['can_auto_apply'] = $this->can_use_htaccess();
				break;

			case 'wp_config':
				$solution['code'] = $this->generate_wp_config_code( $setting, $recommended_value );
				$solution['instructions'] = $this->get_wp_config_instructions();
				$solution['can_auto_apply'] = is_writable( ABSPATH . 'wp-config.php' );
				break;

			case 'nginx':
				$solution['code'] = $this->generate_nginx_code( $setting, $recommended_value );
				$solution['instructions'] = $this->get_nginx_instructions();
				$solution['can_auto_apply'] = false;
				break;

			default:
				if ( strpos( $solution_type, 'hosting_' ) === 0 ) {
					$solution['instructions'] = $this->solution_types[ $solution_type ]['instructions'] ?? '';
					$solution['can_auto_apply'] = false;
				}
				break;
		}

		return $solution;
	}

	/**
	 * Generate PHP.ini code
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return string
	 */
	private function generate_php_ini_code( $setting, $value ) {
		// Format value based on type
		if ( is_bool( $value ) || in_array( $value, array( 'On', 'Off', 'on', 'off' ), true ) ) {
			$formatted_value = $value ? 'On' : 'Off';
		} elseif ( is_numeric( $value ) && strpos( $setting, 'memory' ) !== false ) {
			$formatted_value = $this->format_bytes_value( $value );
		} else {
			$formatted_value = $value;
		}

		return sprintf( "%s = %s\n", $setting, $formatted_value );
	}

	/**
	 * Generate .user.ini code
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return string
	 */
	private function generate_user_ini_code( $setting, $value ) {
		// Same format as PHP.ini
		return $this->generate_php_ini_code( $setting, $value );
	}

	/**
	 * Generate .htaccess code
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return string
	 */
	private function generate_htaccess_code( $setting, $value ) {
		// Format value based on type
		if ( is_bool( $value ) ) {
			$formatted_value = $value ? 'On' : 'Off';
		} elseif ( is_numeric( $value ) && strpos( $setting, 'memory' ) !== false ) {
			$formatted_value = $this->format_bytes_value( $value );
		} else {
			$formatted_value = $value;
		}

		$code = "# PHP Configuration via .htaccess\n";
		$code .= "<IfModule mod_php7.c>\n";
		$code .= sprintf( "    php_value %s %s\n", $setting, $formatted_value );
		$code .= "</IfModule>\n";
		$code .= "<IfModule mod_php8.c>\n";
		$code .= sprintf( "    php_value %s %s\n", $setting, $formatted_value );
		$code .= "</IfModule>\n";

		return $code;
	}

	/**
	 * Generate wp-config.php code
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return string
	 */
	private function generate_wp_config_code( $setting, $value ) {
		$code = "// PHP Configuration via wp-config.php\n";
		
		// Map PHP settings to WordPress constants where applicable
		$wp_constants = array(
			'memory_limit'         => 'WP_MEMORY_LIMIT',
			'max_execution_time'   => 'WP_MAX_EXECUTION_TIME',
			'max_input_time'       => 'WP_MAX_INPUT_TIME',
			'post_max_size'        => 'WP_POST_MAX_SIZE',
			'upload_max_filesize'  => 'WP_UPLOAD_MAX_FILESIZE',
		);

		if ( isset( $wp_constants[ $setting ] ) ) {
			$formatted_value = is_numeric( $value ) && strpos( $setting, 'memory' ) !== false ? 
				$this->format_bytes_value( $value ) : $value;
			
			$code .= sprintf( "define( '%s', '%s' );\n", $wp_constants[ $setting ], $formatted_value );
			
			// Also set via ini_set for immediate effect
			$code .= sprintf( "@ini_set( '%s', '%s' );\n", $setting, $formatted_value );
		} else {
			// Use ini_set for other settings
			$formatted_value = is_bool( $value ) ? ( $value ? '1' : '0' ) : $value;
			$code .= sprintf( "@ini_set( '%s', '%s' );\n", $setting, $formatted_value );
		}

		return $code;
	}

	/**
	 * Generate NGINX code
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return string
	 */
	private function generate_nginx_code( $setting, $value ) {
		// Format value based on type
		if ( is_bool( $value ) ) {
			$formatted_value = $value ? 'on' : 'off';
		} elseif ( is_numeric( $value ) && strpos( $setting, 'memory' ) !== false ) {
			$formatted_value = $this->format_bytes_value( $value );
		} else {
			$formatted_value = $value;
		}

		$code = "# PHP Configuration for NGINX\n";
		$code .= "location ~ \\.php$ {\n";
		$code .= sprintf( "    fastcgi_param PHP_VALUE \"%s=%s\";\n", $setting, $formatted_value );
		$code .= "}\n";

		return $code;
	}

	/**
	 * Format bytes value
	 *
	 * @since 1.2.0
	 * @param int|string $value Value in bytes or with suffix.
	 * @return string
	 */
	private function format_bytes_value( $value ) {
		if ( is_string( $value ) && preg_match( '/^(\d+)([KMG])?$/i', $value, $matches ) ) {
			return $value; // Already formatted
		}

		$bytes = intval( $value );
		
		if ( $bytes >= 1073741824 ) {
			return ( $bytes / 1073741824 ) . 'G';
		} elseif ( $bytes >= 1048576 ) {
			return ( $bytes / 1048576 ) . 'M';
		} elseif ( $bytes >= 1024 ) {
			return ( $bytes / 1024 ) . 'K';
		}
		
		return $bytes;
	}

	/**
	 * Apply solution automatically
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @param string $solution_type Solution type.
	 * @return array
	 */
	public function apply_solution( $setting, $value, $solution_type ) {
		// Validate solution type with security trait - Enhanced in 1.3.0
		if ( ! $this->validate_solution_type( $solution_type ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid solution type', 'as-php-checkup' ),
			);
		}

		switch ( $solution_type ) {
			case 'user_ini':
				return $this->apply_user_ini_solution( $setting, $value );
			
			case 'htaccess':
				return $this->apply_htaccess_solution( $setting, $value );
			
			case 'wp_config':
				return $this->apply_wp_config_solution( $setting, $value );
			
			default:
				return array(
					'success' => false,
					'message' => __( 'This solution cannot be applied automatically', 'as-php-checkup' ),
				);
		}
	}

	/**
	 * Apply .user.ini solution
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return array
	 */
	private function apply_user_ini_solution( $setting, $value ) {
		$user_ini_file = ABSPATH . '.user.ini';
		$code = $this->generate_user_ini_code( $setting, $value );
		
		// Backup existing file if it exists
		if ( file_exists( $user_ini_file ) ) {
			$backup_file = $user_ini_file . '.backup.' . time();
			if ( ! copy( $user_ini_file, $backup_file ) ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to create backup', 'as-php-checkup' ),
				);
			}
		}

		// Read existing content
		$existing_content = file_exists( $user_ini_file ) ? file_get_contents( $user_ini_file ) : '';
		
		// Check if setting already exists
		$pattern = '/^' . preg_quote( $setting, '/' ) . '\s*=.*$/m';
		if ( preg_match( $pattern, $existing_content ) ) {
			// Replace existing setting
			$new_content = preg_replace( $pattern, trim( $code ), $existing_content );
		} else {
			// Add new setting
			$new_content = $existing_content . "\n" . $code;
		}

		// Use atomic write from security trait - Enhanced in 1.3.0
		$result = $this->write_file_atomic( $user_ini_file, $new_content, true );
		
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Configuration applied successfully', 'as-php-checkup' ),
			'backup'  => isset( $backup_file ) ? $backup_file : null,
		);
	}

	/**
	 * Apply .htaccess solution
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return array
	 */
	private function apply_htaccess_solution( $setting, $value ) {
		$htaccess_file = ABSPATH . '.htaccess';
		$code = $this->generate_htaccess_code( $setting, $value );
		
		// Backup existing file if it exists
		if ( file_exists( $htaccess_file ) ) {
			$backup_file = $htaccess_file . '.backup.' . time();
			if ( ! copy( $htaccess_file, $backup_file ) ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to create backup', 'as-php-checkup' ),
				);
			}
		}

		// Read existing content
		$existing_content = file_exists( $htaccess_file ) ? file_get_contents( $htaccess_file ) : '';
		
		// Add our configuration before WordPress rules
		$marker_start = '# BEGIN AS PHP Checkup';
		$marker_end = '# END AS PHP Checkup';
		
		// Remove existing AS PHP Checkup block if it exists
		$pattern = '/' . preg_quote( $marker_start, '/' ) . '.*?' . preg_quote( $marker_end, '/' ) . '/s';
		$existing_content = preg_replace( $pattern, '', $existing_content );
		
		// Add new configuration
		$new_block = $marker_start . "\n" . $code . $marker_end . "\n\n";
		$new_content = $new_block . $existing_content;

		// Use atomic write from security trait - Enhanced in 1.3.0
		$result = $this->write_file_atomic( $htaccess_file, $new_content, true );
		
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Configuration applied successfully', 'as-php-checkup' ),
			'backup'  => isset( $backup_file ) ? $backup_file : null,
		);
	}

	/**
	 * Apply wp-config.php solution
	 *
	 * @since 1.2.0
	 * @param string $setting Setting name.
	 * @param mixed  $value Value to set.
	 * @return array
	 */
	private function apply_wp_config_solution( $setting, $value ) {
		// For security reasons, we don't automatically modify wp-config.php
		// Instead, we provide the code for manual insertion
		return array(
			'success' => false,
			'message' => __( 'For security reasons, wp-config.php must be edited manually', 'as-php-checkup' ),
			'code'    => $this->generate_wp_config_code( $setting, $value ),
		);
	}

	/**
	 * AJAX handler for applying solutions
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ajax_apply_solution() {
		// Security checks
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'as-php-checkup' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'as-php-checkup' ) );
		}

		$solution_type = isset( $_POST['solution_type'] ) ? sanitize_text_field( wp_unslash( $_POST['solution_type'] ) ) : '';
		$setting = isset( $_POST['setting'] ) ? sanitize_text_field( wp_unslash( $_POST['setting'] ) ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		if ( empty( $solution_type ) || empty( $setting ) || empty( $value ) ) {
			wp_send_json_error( array(
				'message' => __( 'Missing required parameters', 'as-php-checkup' ),
			) );
		}

		$result = $this->apply_solution( $setting, $value, $solution_type );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler for downloading configuration
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ajax_download_config() {
		// Security checks
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'as-php-checkup' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'as-php-checkup' ) );
		}

		$config_type = isset( $_POST['config_type'] ) ? sanitize_text_field( wp_unslash( $_POST['config_type'] ) ) : '';
		
		// Validate config type with security trait - Enhanced in 1.3.0
		if ( ! $this->validate_config_type( $config_type ) ) {
			wp_die( esc_html__( 'Invalid configuration type', 'as-php-checkup' ) );
		}

		// Get all issues from the checkup
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		
		$config_content = '';
		$filename = '';

		switch ( $config_type ) {
			case 'php_ini':
				$config_content = $this->generate_full_php_ini_config( $results );
				$filename = 'php.ini';
				break;

			case 'user_ini':
				$config_content = $this->generate_full_user_ini_config( $results );
				$filename = '.user.ini';
				break;

			case 'htaccess':
				$config_content = $this->generate_full_htaccess_config( $results );
				$filename = 'htaccess-additions.txt';
				break;

			case 'nginx':
				$config_content = $this->generate_full_nginx_config( $results );
				$filename = 'nginx-php.conf';
				break;

			case 'wp_config':
				$config_content = $this->generate_full_wp_config( $results );
				$filename = 'wp-config-additions.php';
				break;
		}

		if ( empty( $config_content ) ) {
			wp_die( esc_html__( 'Failed to generate configuration', 'as-php-checkup' ) );
		}

		// Send file headers
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $config_content ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: 0' );

		echo $config_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * AJAX handler for testing write permissions
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ajax_test_write_permissions() {
		// Security checks
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'as_php_checkup_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'as-php-checkup' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'as-php-checkup' ) );
		}

		$permissions = array(
			'php_ini'   => $this->can_modify_php_ini(),
			'user_ini'  => $this->can_use_user_ini(),
			'htaccess'  => $this->can_use_htaccess(),
			'wp_config' => is_writable( ABSPATH . 'wp-config.php' ),
		);

		wp_send_json_success( $permissions );
	}

	// Instruction methods for various providers
	private function get_php_ini_instructions() {
		return __( 'Add these lines to your php.ini file. Location varies by server configuration.', 'as-php-checkup' );
	}

	private function get_user_ini_instructions() {
		return __( 'Add these lines to .user.ini in your WordPress root directory.', 'as-php-checkup' );
	}

	private function get_htaccess_instructions() {
		return __( 'Add these lines to your .htaccess file in WordPress root directory.', 'as-php-checkup' );
	}

	private function get_wp_config_instructions() {
		return __( 'Add these lines to wp-config.php above the line "/* That\'s all, stop editing! */".', 'as-php-checkup' );
	}

	private function get_nginx_instructions() {
		return __( 'Add these lines to your NGINX server configuration file.', 'as-php-checkup' );
	}

	private function get_wpengine_instructions() {
		$instructions = __( 'WP Engine PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to WP Engine User Portal', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Navigate to your site', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Click on "PHP Version" or "Site Settings"', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Some settings can be modified via .user.ini file', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. For advanced settings, contact WP Engine support', 'as-php-checkup' );
		return $instructions;
	}

	private function get_kinsta_instructions() {
		$instructions = __( 'Kinsta PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to MyKinsta dashboard', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Select your site', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Go to Tools → PHP Engine', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Modify PHP version and restart PHP', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Use Kinsta MU plugin for additional settings', 'as-php-checkup' );
		return $instructions;
	}

	private function get_siteground_instructions() {
		$instructions = __( 'SiteGround PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to Site Tools', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Go to Dev → PHP Manager', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Select PHP version and options', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Use PHP Variables section for custom values', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Changes take effect immediately', 'as-php-checkup' );
		return $instructions;
	}

	private function get_cloudways_instructions() {
		$instructions = __( 'Cloudways PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to Cloudways Platform', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Select your application', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Go to Application Settings', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Navigate to PHP FPM Settings', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Modify values and save changes', 'as-php-checkup' );
		return $instructions;
	}

	private function get_cpanel_instructions() {
		$instructions = __( 'cPanel PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to cPanel', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Go to Software → Select PHP Version', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Click on "Options" tab', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Modify PHP settings as needed', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Changes are applied automatically', 'as-php-checkup' );
		return $instructions;
	}

	private function get_plesk_instructions() {
		$instructions = __( 'Plesk PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to Plesk', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Go to Websites & Domains', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Click on PHP Settings', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Modify settings as needed', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Click OK to apply changes', 'as-php-checkup' );
		return $instructions;
	}

	private function get_gridpane_instructions() {
		$instructions = __( 'GridPane PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to GridPane dashboard', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Navigate to your site', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Click on PHP Settings', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Modify PHP configuration values', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Deploy changes to server', 'as-php-checkup' );
		return $instructions;
	}

	private function get_runcloud_instructions() {
		$instructions = __( 'RunCloud PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Log in to RunCloud dashboard', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Select your web application', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Go to Settings → PHP Settings', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Modify PHP configuration', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Click Update PHP Settings', 'as-php-checkup' );
		return $instructions;
	}

	private function get_generic_hosting_instructions() {
		$instructions = __( 'Generic Hosting PHP Configuration:', 'as-php-checkup' ) . "\n\n";
		$instructions .= __( '1. Access your hosting control panel', 'as-php-checkup' ) . "\n";
		$instructions .= __( '2. Look for PHP Settings or PHP Configuration', 'as-php-checkup' ) . "\n";
		$instructions .= __( '3. Modify the required settings', 'as-php-checkup' ) . "\n";
		$instructions .= __( '4. Save changes and restart PHP if needed', 'as-php-checkup' ) . "\n";
		$instructions .= __( '5. Contact support if you need assistance', 'as-php-checkup' );
		return $instructions;
	}

	/**
	 * Generate full configuration files
	 */
	private function generate_full_php_ini_config( $results ) {
		$config = "; PHP Configuration generated by AS PHP Checkup\n";
		$config .= "; Generated on " . current_time( 'mysql' ) . "\n\n";

		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				if ( 'warning' === $item['status'] ) {
					$config .= $this->generate_php_ini_code( $item['setting'], $item['recommended'] );
				}
			}
		}

		return $config;
	}

	private function generate_full_user_ini_config( $results ) {
		$config = "; User PHP Configuration generated by AS PHP Checkup\n";
		$config .= "; Generated on " . current_time( 'mysql' ) . "\n\n";

		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				if ( 'warning' === $item['status'] ) {
					$config .= $this->generate_user_ini_code( $item['setting'], $item['recommended'] );
				}
			}
		}

		return $config;
	}

	private function generate_full_htaccess_config( $results ) {
		$config = "# PHP Configuration generated by AS PHP Checkup\n";
		$config .= "# Generated on " . current_time( 'mysql' ) . "\n";
		$config .= "# Add these lines to your .htaccess file\n\n";

		$config .= "# BEGIN AS PHP Checkup\n";
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				if ( 'warning' === $item['status'] ) {
					$config .= $this->generate_htaccess_code( $item['setting'], $item['recommended'] );
					$config .= "\n";
				}
			}
		}
		$config .= "# END AS PHP Checkup\n";

		return $config;
	}

	private function generate_full_nginx_config( $results ) {
		$config = "# NGINX PHP Configuration generated by AS PHP Checkup\n";
		$config .= "# Generated on " . current_time( 'mysql' ) . "\n";
		$config .= "# Add these lines to your NGINX server configuration\n\n";

		$config .= "location ~ \\.php$ {\n";
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				if ( 'warning' === $item['status'] ) {
					$formatted_value = is_bool( $item['recommended'] ) ? 
						( $item['recommended'] ? 'on' : 'off' ) : $item['recommended'];
					$config .= sprintf( "    fastcgi_param PHP_VALUE \"%s=%s\";\n", 
						$item['setting'], $formatted_value );
				}
			}
		}
		$config .= "}\n";

		return $config;
	}

	private function generate_full_wp_config( $results ) {
		$config = "<?php\n";
		$config .= "/**\n";
		$config .= " * PHP Configuration generated by AS PHP Checkup\n";
		$config .= " * Generated on " . current_time( 'mysql' ) . "\n";
		$config .= " * Add these lines to your wp-config.php file\n";
		$config .= " */\n\n";

		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				if ( 'warning' === $item['status'] ) {
					$config .= $this->generate_wp_config_code( $item['setting'], $item['recommended'] );
					$config .= "\n";
				}
			}
		}

		return $config;
	}
}