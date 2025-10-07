<?php
/**
 * PHP Checkup Core Class
 *
 * @package AS_PHP_Checkup
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_PHP_Checkup class
 *
 * @since 1.0.0
 */
class AS_PHP_Checkup {

	/**
	 * Instance of this class
	 *
	 * @since 1.0.0
	 * @var AS_PHP_Checkup|null
	 */
	private static $instance = null;

	/**
	 * Recommended PHP settings
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $recommended_settings = array();

	/**
	 * Plugin-based requirements
	 *
	 * @since 1.1.0
	 * @var array
	 */
	private $plugin_requirements = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->set_recommended_settings();
		$this->load_plugin_requirements();
	}

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return AS_PHP_Checkup
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load plugin-based requirements
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private function load_plugin_requirements() {
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$this->plugin_requirements = $analyzer->get_combined_requirements();
		
		// Merge plugin requirements with base recommendations
		$this->merge_plugin_requirements();
	}

	/**
	 * Merge plugin requirements with base settings
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private function merge_plugin_requirements() {
		if ( empty( $this->plugin_requirements ) ) {
			return;
		}
		
		// Update basic settings based on plugin requirements
		if ( isset( $this->plugin_requirements['memory_limit'] ) ) {
			$this->recommended_settings['basic']['memory_limit']['recommended'] = $this->plugin_requirements['memory_limit'];
		}
		
		if ( isset( $this->plugin_requirements['max_input_vars'] ) ) {
			$this->recommended_settings['basic']['max_input_vars']['recommended'] = $this->plugin_requirements['max_input_vars'];
		}
		
		if ( isset( $this->plugin_requirements['max_execution_time'] ) ) {
			$this->recommended_settings['basic']['max_execution_time']['recommended'] = $this->plugin_requirements['max_execution_time'];
		}
		
		if ( isset( $this->plugin_requirements['upload_max_filesize'] ) ) {
			$this->recommended_settings['basic']['upload_max_filesize']['recommended'] = $this->plugin_requirements['upload_max_filesize'];
		}
		
		if ( isset( $this->plugin_requirements['post_max_size'] ) ) {
			$this->recommended_settings['basic']['post_max_size']['recommended'] = $this->plugin_requirements['post_max_size'];
		}
		
		// PHP version requirement
		if ( isset( $this->plugin_requirements['php_version'] ) ) {
			$this->recommended_settings['basic']['php_version']['recommended'] = $this->plugin_requirements['php_version'];
		}
	}

	/**
	 * Set recommended PHP settings
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function set_recommended_settings() {
		$this->recommended_settings = array(
			'basic' => array(
				'php_version' => array(
					'label'       => __( 'PHP Version', 'as-php-checkup' ),
					'recommended' => '8.3',
					'minimum'     => '7.4',
					'type'        => 'version',
					'description' => __( 'PHP 8.3+ recommended for best performance and security', 'as-php-checkup' ),
				),
				'memory_limit' => array(
					'label'       => __( 'Memory Limit', 'as-php-checkup' ),
					'recommended' => '768M',
					'minimum'     => '256M',
					'type'        => 'memory',
					'description' => __( 'Higher memory limit allows for complex operations', 'as-php-checkup' ),
				),
				'max_input_vars' => array(
					'label'       => __( 'Max Input Vars', 'as-php-checkup' ),
					'recommended' => 6000,
					'minimum'     => 3000,
					'type'        => 'integer',
					'description' => __( 'Required for complex forms and menu structures', 'as-php-checkup' ),
				),
				'post_max_size' => array(
					'label'       => __( 'Max Post Size', 'as-php-checkup' ),
					'recommended' => '256M',
					'minimum'     => '64M',
					'type'        => 'memory',
					'description' => __( 'Maximum size of POST data', 'as-php-checkup' ),
				),
				'upload_max_filesize' => array(
					'label'       => __( 'Max Upload Size', 'as-php-checkup' ),
					'recommended' => '256M',
					'minimum'     => '64M',
					'type'        => 'memory',
					'description' => __( 'Maximum allowed file upload size', 'as-php-checkup' ),
				),
				'max_execution_time' => array(
					'label'       => __( 'Max Execution Time', 'as-php-checkup' ),
					'recommended' => 600,
					'minimum'     => 300,
					'type'        => 'integer',
					'description' => __( 'Maximum script execution time in seconds', 'as-php-checkup' ),
				),
				'max_input_time' => array(
					'label'       => __( 'Max Input Time', 'as-php-checkup' ),
					'recommended' => 600,
					'minimum'     => 60,
					'type'        => 'integer',
					'description' => __( 'Maximum time to parse input data', 'as-php-checkup' ),
				),
			),
			'session' => array(
				'session.gc_maxlifetime' => array(
					'label'       => __( 'Session GC Max Lifetime', 'as-php-checkup' ),
					'recommended' => 1440,
					'minimum'     => 1440,
					'type'        => 'integer',
					'description' => __( 'Session garbage collection lifetime', 'as-php-checkup' ),
				),
			),
			'opcache' => array(
				'opcache.enable' => array(
					'label'       => __( 'OPcache Enable', 'as-php-checkup' ),
					'recommended' => 1,
					'minimum'     => 1,
					'type'        => 'boolean',
					'description' => __( 'Enable OPcache for better performance', 'as-php-checkup' ),
				),
				'opcache.memory_consumption' => array(
					'label'       => __( 'OPcache Memory', 'as-php-checkup' ),
					'recommended' => 384,
					'minimum'     => 128,
					'type'        => 'integer',
					'description' => __( 'OPcache memory consumption in MB', 'as-php-checkup' ),
				),
				'opcache.max_accelerated_files' => array(
					'label'       => __( 'OPcache Max Files', 'as-php-checkup' ),
					'recommended' => 150000,
					'minimum'     => 10000,
					'type'        => 'integer',
					'description' => __( 'Maximum number of cached files', 'as-php-checkup' ),
				),
				'opcache.interned_strings_buffer' => array(
					'label'       => __( 'OPcache Interned Strings', 'as-php-checkup' ),
					'recommended' => 24,
					'minimum'     => 8,
					'type'        => 'integer',
					'description' => __( 'Buffer for interned strings in MB', 'as-php-checkup' ),
				),
				'opcache.validate_timestamps' => array(
					'label'       => __( 'OPcache Validate Timestamps', 'as-php-checkup' ),
					'recommended' => 1,
					'minimum'     => 1,
					'type'        => 'boolean',
					'description' => __( 'Check file timestamps for updates', 'as-php-checkup' ),
				),
				'opcache.revalidate_freq' => array(
					'label'       => __( 'OPcache Revalidate Frequency', 'as-php-checkup' ),
					'recommended' => 1,
					'minimum'     => 2,
					'type'        => 'integer',
					'description' => __( 'How often to check for updates (seconds)', 'as-php-checkup' ),
				),
				'opcache.save_comments' => array(
					'label'       => __( 'OPcache Save Comments', 'as-php-checkup' ),
					'recommended' => 1,
					'minimum'     => 1,
					'type'        => 'boolean',
					'description' => __( 'Save doc comments (required for annotations)', 'as-php-checkup' ),
				),
				'opcache.fast_shutdown' => array(
					'label'       => __( 'OPcache Fast Shutdown', 'as-php-checkup' ),
					'recommended' => 1,
					'minimum'     => 1,
					'type'        => 'boolean',
					'description' => __( 'Enable fast shutdown for better performance', 'as-php-checkup' ),
				),
			),
			'performance' => array(
				'realpath_cache_size' => array(
					'label'       => __( 'Realpath Cache Size', 'as-php-checkup' ),
					'recommended' => '8192K',
					'minimum'     => '4096K',
					'type'        => 'memory',
					'description' => __( 'Cache for real file paths', 'as-php-checkup' ),
				),
				'realpath_cache_ttl' => array(
					'label'       => __( 'Realpath Cache TTL', 'as-php-checkup' ),
					'recommended' => 120,
					'minimum'     => 120,
					'type'        => 'integer',
					'description' => __( 'Time to live for realpath cache', 'as-php-checkup' ),
				),
				'max_input_nesting_level' => array(
					'label'       => __( 'Max Input Nesting Level', 'as-php-checkup' ),
					'recommended' => 256,
					'minimum'     => 64,
					'type'        => 'integer',
					'description' => __( 'Maximum depth of input variable nesting', 'as-php-checkup' ),
				),
				'zlib.output_compression' => array(
					'label'       => __( 'Zlib Output Compression', 'as-php-checkup' ),
					'recommended' => 'On',
					'minimum'     => 'Off',
					'type'        => 'boolean_string',
					'description' => __( 'Enable output compression for better performance', 'as-php-checkup' ),
				),
			),
		);
	}

	/**
	 * Get current PHP settings
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_current_settings() {
		$current = array();
		
		// Basic settings
		$current['php_version'] = PHP_VERSION;
		$current['memory_limit'] = ini_get( 'memory_limit' );
		$current['max_input_vars'] = ini_get( 'max_input_vars' );
		$current['post_max_size'] = ini_get( 'post_max_size' );
		$current['upload_max_filesize'] = ini_get( 'upload_max_filesize' );
		$current['max_execution_time'] = ini_get( 'max_execution_time' );
		$current['max_input_time'] = ini_get( 'max_input_time' );
		
		// Session settings
		$current['session.gc_maxlifetime'] = ini_get( 'session.gc_maxlifetime' );
		
		// OPcache settings
		$current['opcache.enable'] = ini_get( 'opcache.enable' );
		$current['opcache.memory_consumption'] = ini_get( 'opcache.memory_consumption' );
		$current['opcache.max_accelerated_files'] = ini_get( 'opcache.max_accelerated_files' );
		$current['opcache.interned_strings_buffer'] = ini_get( 'opcache.interned_strings_buffer' );
		$current['opcache.validate_timestamps'] = ini_get( 'opcache.validate_timestamps' );
		$current['opcache.revalidate_freq'] = ini_get( 'opcache.revalidate_freq' );
		$current['opcache.save_comments'] = ini_get( 'opcache.save_comments' );
		$current['opcache.fast_shutdown'] = ini_get( 'opcache.fast_shutdown' );
		
		// Performance settings
		$current['realpath_cache_size'] = ini_get( 'realpath_cache_size' );
		$current['realpath_cache_ttl'] = ini_get( 'realpath_cache_ttl' );
		$current['max_input_nesting_level'] = ini_get( 'max_input_nesting_level' );
		$current['zlib.output_compression'] = ini_get( 'zlib.output_compression' );
		
		return $current;
	}

	/**
	 * Check if a value meets requirements
	 *
	 * @since 1.0.0
	 * @param mixed  $current Current value.
	 * @param mixed  $recommended Recommended value.
	 * @param mixed  $minimum Minimum value.
	 * @param string $type Type of comparison.
	 * @return string Status: optimal, acceptable, or warning
	 */
	public function check_value( $current, $recommended, $minimum, $type ) {
		if ( empty( $current ) && 'boolean' !== $type && 'boolean_string' !== $type ) {
			return 'warning';
		}
		
		switch ( $type ) {
			case 'version':
				if ( version_compare( $current, $recommended, '>=' ) ) {
					return 'optimal';
				} elseif ( version_compare( $current, $minimum, '>=' ) ) {
					return 'acceptable';
				}
				return 'warning';
				
			case 'memory':
				$current_bytes = $this->convert_to_bytes( $current );
				$recommended_bytes = $this->convert_to_bytes( $recommended );
				$minimum_bytes = $this->convert_to_bytes( $minimum );
				
				if ( $current_bytes >= $recommended_bytes ) {
					return 'optimal';
				} elseif ( $current_bytes >= $minimum_bytes ) {
					return 'acceptable';
				}
				return 'warning';
				
			case 'integer':
				$current_int = intval( $current );
				$recommended_int = intval( $recommended );
				$minimum_int = intval( $minimum );
				
				if ( $current_int >= $recommended_int ) {
					return 'optimal';
				} elseif ( $current_int >= $minimum_int ) {
					return 'acceptable';
				}
				return 'warning';
				
			case 'boolean':
				if ( $current == $recommended ) {
					return 'optimal';
				}
				return 'warning';
				
			case 'boolean_string':
				$current_bool = ( 'On' === $current || '1' === $current || 1 === $current );
				$recommended_bool = ( 'On' === $recommended || '1' === $recommended || 1 === $recommended );
				
				if ( $current_bool === $recommended_bool ) {
					return 'optimal';
				}
				return 'warning';
				
			default:
				return 'warning';
		}
	}

	/**
	 * Convert memory string to bytes
	 *
	 * @since 1.0.0
	 * @param string $value Memory value string.
	 * @return int Bytes
	 */
	public function convert_to_bytes( $value ) {
		$value = trim( $value );
		if ( empty( $value ) ) {
			return 0;
		}
		
		$last = strtolower( $value[ strlen( $value ) - 1 ] );
		$value = intval( $value );
		
		switch ( $last ) {
			case 'g':
				$value *= 1024 * 1024 * 1024;
				break;
			case 'm':
				$value *= 1024 * 1024;
				break;
			case 'k':
				$value *= 1024;
				break;
		}
		
		return $value;
	}

	/**
	 * Get all check results
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_check_results() {
		// Reload plugin requirements to get latest
		$this->load_plugin_requirements();
		
		$current_settings = $this->get_current_settings();
		$results = array();
		
		foreach ( $this->recommended_settings as $category => $settings ) {
			$results[ $category ] = array(
				'label' => $this->get_category_label( $category ),
				'items' => array(),
			);
			
			foreach ( $settings as $key => $config ) {
				$current_value = isset( $current_settings[ $key ] ) ? $current_settings[ $key ] : '';
				$status = $this->check_value(
					$current_value,
					$config['recommended'],
					$config['minimum'],
					$config['type']
				);
				
				// Add source information if from plugin
				$source = '';
				if ( isset( $this->plugin_requirements['sources'][ $key ] ) ) {
					$source = implode( ', ', $this->plugin_requirements['sources'][ $key ] );
				}
				
				$results[ $category ]['items'][ $key ] = array(
					'label'       => $config['label'],
					'current'     => $current_value,
					'recommended' => $config['recommended'],
					'minimum'     => $config['minimum'],
					'status'      => $status,
					'description' => $config['description'],
					'type'        => $config['type'],
					'source'      => $source,
				);
			}
		}
		
		return $results;
	}

	/**
	 * Get category label
	 *
	 * @since 1.0.0
	 * @param string $category Category key.
	 * @return string
	 */
	private function get_category_label( $category ) {
		$labels = array(
			'basic'       => __( 'Basic PHP Settings', 'as-php-checkup' ),
			'session'     => __( 'Session Settings', 'as-php-checkup' ),
			'opcache'     => __( 'OPcache Settings', 'as-php-checkup' ),
			'performance' => __( 'Performance Settings', 'as-php-checkup' ),
		);
		
		return isset( $labels[ $category ] ) ? $labels[ $category ] : $category;
	}

	/**
	 * Get system information
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_system_info() {
		global $wpdb;
		
		$info = array(
			'wordpress' => array(
				'version'      => get_bloginfo( 'version' ),
				'multisite'    => is_multisite(),
				'memory_limit' => WP_MEMORY_LIMIT,
				'debug_mode'   => WP_DEBUG,
				'language'     => get_locale(),
			),
			'server' => array(
				'software'     => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
				'php_version'  => PHP_VERSION,
				'mysql_version' => $wpdb->db_version(),
				'max_workers'  => $this->estimate_max_workers(),
			),
			'php_extensions' => array(
				'curl'     => extension_loaded( 'curl' ),
				'dom'      => extension_loaded( 'dom' ),
				'exif'     => extension_loaded( 'exif' ),
				'fileinfo' => extension_loaded( 'fileinfo' ),
				'hash'     => extension_loaded( 'hash' ),
				'imagick'  => extension_loaded( 'imagick' ),
				'json'     => extension_loaded( 'json' ),
				'mbstring' => extension_loaded( 'mbstring' ),
				'openssl'  => extension_loaded( 'openssl' ),
				'pcre'     => extension_loaded( 'pcre' ),
				'xml'      => extension_loaded( 'xml' ),
				'zip'      => extension_loaded( 'zip' ),
			),
		);
		
		return $info;
	}

	/**
	 * Estimate max PHP workers
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function estimate_max_workers() {
		// This is an estimation based on common configurations
		// Actual value depends on server configuration
		$memory_limit = $this->convert_to_bytes( ini_get( 'memory_limit' ) );
		
		if ( $memory_limit > 0 ) {
			// Assume each worker uses around 128MB
			$estimated = floor( $memory_limit / ( 128 * 1024 * 1024 ) );
			return $estimated > 0 ? strval( $estimated ) : __( 'Unable to determine', 'as-php-checkup' );
		}
		
		return __( 'Unable to determine', 'as-php-checkup' );
	}

	/**
	 * Get plugin requirements
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_plugin_requirements() {
		return $this->plugin_requirements;
	}

	/**
	 * Get recommended settings
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_recommended_settings() {
		return $this->recommended_settings;
	}
}