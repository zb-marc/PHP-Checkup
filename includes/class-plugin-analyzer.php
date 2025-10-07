<?php
/**
 * Plugin Analyzer Class
 *
 * @package AS_PHP_Checkup
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_PHP_Checkup_Plugin_Analyzer class
 *
 * @since 1.1.0
 */
class AS_PHP_Checkup_Plugin_Analyzer {

	/**
	 * Instance of this class
	 *
	 * @since 1.1.0
	 * @var AS_PHP_Checkup_Plugin_Analyzer|null
	 */
	private static $instance = null;

	/**
	 * Known plugin requirements database
	 *
	 * @since 1.1.0
	 * @var array
	 */
	private $known_requirements = array();

	/**
	 * Analyzed plugin data cache
	 *
	 * @since 1.1.0
	 * @var array
	 */
	private $analyzed_data = array();

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	private function __construct() {
		$this->init_known_requirements();
		$this->load_analyzed_data();
	}

	/**
	 * Get singleton instance
	 *
	 * @since 1.1.0
	 * @return AS_PHP_Checkup_Plugin_Analyzer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize known plugin requirements
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private function init_known_requirements() {
		$this->known_requirements = array(
			// Page Builders
			'elementor/elementor.php' => array(
				'name'                => 'Elementor',
				'php_version'         => '7.4',
				'memory_limit'        => '256M',
				'max_input_vars'      => 5000,
				'max_execution_time'  => 300,
				'upload_max_filesize' => '128M',
				'post_max_size'       => '128M',
			),
			'elementor-pro/elementor-pro.php' => array(
				'name'                => 'Elementor Pro',
				'php_version'         => '7.4',
				'memory_limit'        => '512M',
				'max_input_vars'      => 5000,
				'max_execution_time'  => 300,
				'upload_max_filesize' => '256M',
				'post_max_size'       => '256M',
			),
			'beaver-builder-lite-version/fl-builder.php' => array(
				'name'                => 'Beaver Builder',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
				'max_execution_time'  => 300,
			),
			'divi-builder/divi-builder.php' => array(
				'name'                => 'Divi Builder',
				'php_version'         => '7.4',
				'memory_limit'        => '256M',
				'max_input_vars'      => 4000,
				'max_execution_time'  => 300,
			),
			'wp-page-builder/wp-page-builder.php' => array(
				'name'                => 'WP Page Builder',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
				'max_execution_time'  => 300,
			),
			
			// E-Commerce
			'woocommerce/woocommerce.php' => array(
				'name'                => 'WooCommerce',
				'php_version'         => '7.4',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
				'max_execution_time'  => 300,
				'upload_max_filesize' => '64M',
				'post_max_size'       => '64M',
			),
			'easy-digital-downloads/easy-digital-downloads.php' => array(
				'name'                => 'Easy Digital Downloads',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_input_vars'      => 2000,
				'max_execution_time'  => 300,
			),
			
			// Multi-language
			'translatepress-multilingual/index.php' => array(
				'name'                => 'TranslatePress',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 6000,
				'max_execution_time'  => 600,
			),
			'polylang/polylang.php' => array(
				'name'                => 'Polylang',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
				'max_execution_time'  => 300,
			),
			'wpml-multilingual-cms/sitepress.php' => array(
				'name'                => 'WPML',
				'php_version'         => '7.0',
				'memory_limit'        => '512M',
				'max_input_vars'      => 5000,
				'max_execution_time'  => 600,
			),
			
			// Image Optimization
			'imagify/imagify.php' => array(
				'name'                => 'Imagify',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_execution_time'  => 600,
				'upload_max_filesize' => '256M',
				'post_max_size'       => '256M',
			),
			'wp-smushit/wp-smush.php' => array(
				'name'                => 'Smush',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_execution_time'  => 600,
				'upload_max_filesize' => '128M',
				'post_max_size'       => '128M',
			),
			'ewww-image-optimizer/ewww-image-optimizer.php' => array(
				'name'                => 'EWWW Image Optimizer',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_execution_time'  => 900,
				'upload_max_filesize' => '256M',
				'post_max_size'       => '256M',
			),
			
			// Backup Plugins
			'updraftplus/updraftplus.php' => array(
				'name'                => 'UpdraftPlus',
				'php_version'         => '7.0',
				'memory_limit'        => '512M',
				'max_execution_time'  => 900,
				'upload_max_filesize' => '512M',
				'post_max_size'       => '512M',
			),
			'backwpup/backwpup.php' => array(
				'name'                => 'BackWPup',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_execution_time'  => 600,
			),
			'duplicator/duplicator.php' => array(
				'name'                => 'Duplicator',
				'php_version'         => '7.2',
				'memory_limit'        => '512M',
				'max_execution_time'  => 900,
				'upload_max_filesize' => '512M',
				'post_max_size'       => '512M',
			),
			
			// SEO Plugins
			'wordpress-seo/wp-seo.php' => array(
				'name'                => 'Yoast SEO',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
			),
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => array(
				'name'                => 'All in One SEO',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
			),
			'seo-by-rank-math/rank-math.php' => array(
				'name'                => 'Rank Math',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
			),
			
			// Form Plugins
			'contact-form-7/wp-contact-form-7.php' => array(
				'name'                => 'Contact Form 7',
				'php_version'         => '7.0',
				'memory_limit'        => '128M',
				'max_input_vars'      => 2000,
			),
			'wpforms-lite/wpforms.php' => array(
				'name'                => 'WPForms',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
			),
			'ninja-forms/ninja-forms.php' => array(
				'name'                => 'Ninja Forms',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
			),
			'formidable/formidable.php' => array(
				'name'                => 'Formidable Forms',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
			),
			
			// Security Plugins
			'wordfence/wordfence.php' => array(
				'name'                => 'Wordfence',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_execution_time'  => 600,
			),
			'sucuri-scanner/sucuri.php' => array(
				'name'                => 'Sucuri',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_execution_time'  => 600,
			),
			'all-in-one-wp-security-and-firewall/wp-security.php' => array(
				'name'                => 'All In One WP Security',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
			),
			
			// Cache Plugins
			'wp-rocket/wp-rocket.php' => array(
				'name'                => 'WP Rocket',
				'php_version'         => '7.3',
				'memory_limit'        => '256M',
			),
			'w3-total-cache/w3-total-cache.php' => array(
				'name'                => 'W3 Total Cache',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
			),
			'wp-super-cache/wp-cache.php' => array(
				'name'                => 'WP Super Cache',
				'php_version'         => '7.0',
				'memory_limit'        => '128M',
			),
			'litespeed-cache/litespeed-cache.php' => array(
				'name'                => 'LiteSpeed Cache',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
			),
			
			// LMS Plugins
			'learnpress/learnpress.php' => array(
				'name'                => 'LearnPress',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
				'max_execution_time'  => 300,
			),
			'tutor/tutor.php' => array(
				'name'                => 'Tutor LMS',
				'php_version'         => '7.0',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
				'max_execution_time'  => 300,
			),
			'sensei-lms/sensei-lms.php' => array(
				'name'                => 'Sensei LMS',
				'php_version'         => '7.2',
				'memory_limit'        => '256M',
				'max_input_vars'      => 3000,
			),
		);
	}

	/**
	 * Analyze all active plugins
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function analyze_all_plugins() {
		$active_plugins = get_option( 'active_plugins', array() );
		$analyzed = array();
		
		foreach ( $active_plugins as $plugin ) {
			$requirements = $this->get_plugin_requirements( $plugin );
			if ( $requirements ) {
				$analyzed[ $plugin ] = $requirements;
			}
		}
		
		// Check for network activated plugins on multisite
		if ( is_multisite() ) {
			$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
			foreach ( $network_plugins as $plugin => $time ) {
				$requirements = $this->get_plugin_requirements( $plugin );
				if ( $requirements ) {
					$analyzed[ $plugin ] = $requirements;
				}
			}
		}
		
		// Store analyzed data
		$this->analyzed_data = $analyzed;
		$this->save_analyzed_data();
		
		return $analyzed;
	}

	/**
	 * Get requirements for a specific plugin
	 *
	 * @since 1.1.0
	 * @param string $plugin_file Plugin file path.
	 * @return array|false
	 */
	private function get_plugin_requirements( $plugin_file ) {
		// Check if we have known requirements
		if ( isset( $this->known_requirements[ $plugin_file ] ) ) {
			return $this->known_requirements[ $plugin_file ];
		}
		
		// Try to detect requirements from plugin headers
		$plugin_data = $this->analyze_plugin_file( $plugin_file );
		if ( $plugin_data ) {
			return $plugin_data;
		}
		
		return false;
	}

	/**
	 * Analyze plugin file for requirements
	 *
	 * @since 1.1.0
	 * @param string $plugin_file Plugin file path.
	 * @return array|false
	 */
	private function analyze_plugin_file( $plugin_file ) {
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		
		if ( ! file_exists( $plugin_path ) ) {
			return false;
		}
		
		// Get plugin data
		$plugin_data = get_plugin_data( $plugin_path );
		if ( empty( $plugin_data['Name'] ) ) {
			return false;
		}
		
		$requirements = array(
			'name' => $plugin_data['Name'],
		);
		
		// Check PHP version requirement
		if ( ! empty( $plugin_data['RequiresPHP'] ) ) {
			$requirements['php_version'] = $plugin_data['RequiresPHP'];
		}
		
		// Try to detect requirements from readme.txt
		$readme_requirements = $this->parse_readme_requirements( dirname( $plugin_path ) );
		if ( $readme_requirements ) {
			$requirements = array_merge( $requirements, $readme_requirements );
		}
		
		// Apply heuristics based on plugin type
		$heuristic_requirements = $this->apply_heuristics( $plugin_data );
		if ( $heuristic_requirements ) {
			$requirements = array_merge( $requirements, $heuristic_requirements );
		}
		
		return ! empty( $requirements ) ? $requirements : false;
	}

	/**
	 * Parse readme.txt for requirements
	 *
	 * @since 1.1.0
	 * @param string $plugin_dir Plugin directory path.
	 * @return array|false
	 */
	private function parse_readme_requirements( $plugin_dir ) {
		$readme_files = array( 'readme.txt', 'README.txt', 'readme.md', 'README.md' );
		$requirements = array();
		
		foreach ( $readme_files as $readme ) {
			$readme_path = $plugin_dir . '/' . $readme;
			if ( file_exists( $readme_path ) ) {
				$content = file_get_contents( $readme_path );
				
				// Look for PHP version
				if ( preg_match( '/Requires PHP:\s*([0-9.]+)/i', $content, $matches ) ) {
					$requirements['php_version'] = $matches[1];
				}
				
				// Look for memory requirements
				if ( preg_match( '/memory.{0,20}limit.{0,20}([0-9]+)M/i', $content, $matches ) ) {
					$requirements['memory_limit'] = $matches[1] . 'M';
				}
				
				// Look for max_input_vars
				if ( preg_match( '/max.{0,5}input.{0,5}vars.{0,20}([0-9]+)/i', $content, $matches ) ) {
					$requirements['max_input_vars'] = intval( $matches[1] );
				}
				
				break;
			}
		}
		
		return ! empty( $requirements ) ? $requirements : false;
	}

	/**
	 * Apply heuristics based on plugin characteristics
	 *
	 * @since 1.1.0
	 * @param array $plugin_data Plugin data.
	 * @return array
	 */
	private function apply_heuristics( $plugin_data ) {
		$requirements = array();
		$name_lower = strtolower( $plugin_data['Name'] );
		$description_lower = strtolower( $plugin_data['Description'] );
		$combined = $name_lower . ' ' . $description_lower;
		
		// E-commerce plugins
		if ( strpos( $combined, 'shop' ) !== false || 
		     strpos( $combined, 'commerce' ) !== false || 
		     strpos( $combined, 'store' ) !== false ||
		     strpos( $combined, 'payment' ) !== false ) {
			$requirements['memory_limit'] = '256M';
			$requirements['max_input_vars'] = 3000;
			$requirements['max_execution_time'] = 300;
		}
		
		// Page builders
		if ( strpos( $combined, 'builder' ) !== false || 
		     strpos( $combined, 'composer' ) !== false ||
		     strpos( $combined, 'editor' ) !== false ) {
			$requirements['memory_limit'] = '256M';
			$requirements['max_input_vars'] = 5000;
			$requirements['max_execution_time'] = 300;
		}
		
		// Image processing
		if ( strpos( $combined, 'image' ) !== false || 
		     strpos( $combined, 'photo' ) !== false ||
		     strpos( $combined, 'gallery' ) !== false ||
		     strpos( $combined, 'media' ) !== false ) {
			$requirements['memory_limit'] = '256M';
			$requirements['upload_max_filesize'] = '128M';
			$requirements['post_max_size'] = '128M';
			$requirements['max_execution_time'] = 600;
		}
		
		// Backup/Migration plugins
		if ( strpos( $combined, 'backup' ) !== false || 
		     strpos( $combined, 'migrate' ) !== false ||
		     strpos( $combined, 'clone' ) !== false ||
		     strpos( $combined, 'duplicate' ) !== false ) {
			$requirements['memory_limit'] = '512M';
			$requirements['max_execution_time'] = 900;
			$requirements['upload_max_filesize'] = '512M';
			$requirements['post_max_size'] = '512M';
		}
		
		// Multi-language plugins
		if ( strpos( $combined, 'translate' ) !== false || 
		     strpos( $combined, 'multilingual' ) !== false ||
		     strpos( $combined, 'language' ) !== false ||
		     strpos( $combined, 'wpml' ) !== false ) {
			$requirements['memory_limit'] = '256M';
			$requirements['max_input_vars'] = 5000;
			$requirements['max_execution_time'] = 600;
		}
		
		// Forms plugins
		if ( strpos( $combined, 'form' ) !== false || 
		     strpos( $combined, 'contact' ) !== false ) {
			$requirements['max_input_vars'] = 3000;
			$requirements['memory_limit'] = '256M';
		}
		
		// Security plugins
		if ( strpos( $combined, 'security' ) !== false || 
		     strpos( $combined, 'firewall' ) !== false ||
		     strpos( $combined, 'antivirus' ) !== false ||
		     strpos( $combined, 'malware' ) !== false ) {
			$requirements['memory_limit'] = '256M';
			$requirements['max_execution_time'] = 600;
		}
		
		// LMS plugins
		if ( strpos( $combined, 'learn' ) !== false || 
		     strpos( $combined, 'course' ) !== false ||
		     strpos( $combined, 'lms' ) !== false ||
		     strpos( $combined, 'lesson' ) !== false ) {
			$requirements['memory_limit'] = '256M';
			$requirements['max_input_vars'] = 3000;
			$requirements['max_execution_time'] = 300;
		}
		
		return $requirements;
	}

	/**
	 * Get combined requirements from all active plugins
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_combined_requirements() {
		if ( empty( $this->analyzed_data ) ) {
			$this->analyze_all_plugins();
		}
		
		$combined = array(
			'sources' => array(),
		);
		
		foreach ( $this->analyzed_data as $plugin_file => $requirements ) {
			foreach ( $requirements as $key => $value ) {
				if ( 'name' === $key ) {
					continue;
				}
				
				// Track sources
				if ( ! isset( $combined['sources'][ $key ] ) ) {
					$combined['sources'][ $key ] = array();
				}
				$combined['sources'][ $key ][] = $requirements['name'];
				
				// Combine requirements (use highest/most demanding)
				switch ( $key ) {
					case 'php_version':
						if ( ! isset( $combined[ $key ] ) || 
						     version_compare( $value, $combined[ $key ], '>' ) ) {
							$combined[ $key ] = $value;
						}
						break;
						
					case 'memory_limit':
					case 'upload_max_filesize':
					case 'post_max_size':
					case 'realpath_cache_size':
						$current_bytes = isset( $combined[ $key ] ) ? 
						                $this->convert_to_bytes( $combined[ $key ] ) : 0;
						$new_bytes = $this->convert_to_bytes( $value );
						if ( $new_bytes > $current_bytes ) {
							$combined[ $key ] = $value;
						}
						break;
						
					case 'max_input_vars':
					case 'max_execution_time':
					case 'max_input_time':
					case 'max_input_nesting_level':
						$current_val = isset( $combined[ $key ] ) ? intval( $combined[ $key ] ) : 0;
						$new_val = intval( $value );
						if ( $new_val > $current_val ) {
							$combined[ $key ] = $new_val;
						}
						break;
						
					default:
						// For other values, use if not set
						if ( ! isset( $combined[ $key ] ) ) {
							$combined[ $key ] = $value;
						}
						break;
				}
			}
		}
		
		return $combined;
	}

	/**
	 * Convert memory string to bytes
	 *
	 * @since 1.1.0
	 * @param string $value Memory value.
	 * @return int
	 */
	private function convert_to_bytes( $value ) {
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
	 * Save analyzed data to database
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private function save_analyzed_data() {
		update_option( 'as_php_checkup_plugin_analysis', $this->analyzed_data, false );
		update_option( 'as_php_checkup_analysis_time', current_time( 'timestamp' ), false );
	}

	/**
	 * Load analyzed data from database
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private function load_analyzed_data() {
		$this->analyzed_data = get_option( 'as_php_checkup_plugin_analysis', array() );
		
		// Re-analyze if data is older than 24 hours
		$last_analysis = get_option( 'as_php_checkup_analysis_time', 0 );
		if ( ( current_time( 'timestamp' ) - $last_analysis ) > DAY_IN_SECONDS ) {
			$this->analyze_all_plugins();
		}
	}

	/**
	 * Get analyzed plugin data
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_analyzed_data() {
		if ( empty( $this->analyzed_data ) ) {
			$this->analyze_all_plugins();
		}
		return $this->analyzed_data;
	}
}