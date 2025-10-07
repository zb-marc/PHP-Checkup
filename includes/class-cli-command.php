<?php
/**
 * WP-CLI Command Handler
 *
 * @package AS_PHP_Checkup
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_PHP_Checkup_CLI_Command class
 *
 * @since 1.1.0
 */
class AS_PHP_Checkup_CLI_Command extends WP_CLI_Command {

	/**
	 * Check PHP configuration status
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 *   - csv
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Fields to display in output.
	 *
	 * ## EXAMPLES
	 *
	 *     wp as-php-checkup status
	 *     wp as-php-checkup status --format=json
	 *     wp as-php-checkup status --fields=setting,current,status
	 *
	 * @since 1.1.0
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function status( $args, $assoc_args ) {
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		
		// Prepare data for output
		$output_data = array();
		
		foreach ( $results as $category_key => $category ) {
			foreach ( $category['items'] as $key => $item ) {
				$output_data[] = array(
					'category'    => $category['label'],
					'setting'     => $item['label'],
					'current'     => $item['current'] ? $item['current'] : 'Not set',
					'recommended' => $item['recommended'],
					'minimum'     => $item['minimum'],
					'status'      => $item['status'],
					'source'      => ! empty( $item['source'] ) ? $item['source'] : 'Base',
				);
			}
		}
		
		// Default fields
		$default_fields = array( 'category', 'setting', 'current', 'recommended', 'status' );
		
		// Get format
		$format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		
		// Get fields
		$fields = WP_CLI\Utils\get_flag_value( $assoc_args, 'fields', $default_fields );
		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
		}
		
		// Format and output
		WP_CLI\Utils\format_items( $format, $output_data, $fields );
		
		// Show summary
		if ( 'table' === $format ) {
			$this->show_summary( $results );
		}
	}

	/**
	 * Analyze installed plugins for PHP requirements
	 *
	 * ## OPTIONS
	 *
	 * [--refresh]
	 * : Force refresh the analysis
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp as-php-checkup analyze
	 *     wp as-php-checkup analyze --refresh
	 *     wp as-php-checkup analyze --format=json
	 *
	 * @since 1.1.0
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function analyze( $args, $assoc_args ) {
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		
		// Check if refresh is requested
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'refresh', false ) ) {
			WP_CLI::log( __( 'Refreshing plugin analysis...', 'as-php-checkup' ) );
			$analyzer->analyze_all_plugins();
		}
		
		$analyzed_data = $analyzer->get_analyzed_data();
		$combined = $analyzer->get_combined_requirements();
		
		if ( empty( $analyzed_data ) ) {
			WP_CLI::warning( __( 'No plugins with requirements found.', 'as-php-checkup' ) );
			return;
		}
		
		// Prepare data for output
		$output_data = array();
		
		foreach ( $analyzed_data as $plugin_file => $requirements ) {
			foreach ( $requirements as $key => $value ) {
				if ( 'name' !== $key ) {
					$output_data[] = array(
						'plugin'      => $requirements['name'],
						'requirement' => $key,
						'value'       => $value,
					);
				}
			}
		}
		
		// Get format
		$format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		
		// Output plugin requirements
		WP_CLI::log( WP_CLI::colorize( '%B' . __( 'Plugin Requirements:', 'as-php-checkup' ) . '%n' ) );
		WP_CLI\Utils\format_items( $format, $output_data, array( 'plugin', 'requirement', 'value' ) );
		
		// Show combined requirements
		if ( 'table' === $format && ! empty( $combined ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( WP_CLI::colorize( '%B' . __( 'Combined Requirements:', 'as-php-checkup' ) . '%n' ) );
			
			$combined_output = array();
			foreach ( $combined as $key => $value ) {
				if ( 'sources' !== $key ) {
					$combined_output[] = array(
						'requirement' => $key,
						'value'       => $value,
						'required_by' => isset( $combined['sources'][ $key ] ) ? 
						               implode( ', ', array_slice( $combined['sources'][ $key ], 0, 3 ) ) : '',
					);
				}
			}
			
			WP_CLI\Utils\format_items( 'table', $combined_output, array( 'requirement', 'value', 'required_by' ) );
		}
	}

	/**
	 * Get system information
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp as-php-checkup system
	 *     wp as-php-checkup system --format=json
	 *
	 * @since 1.1.0
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function system( $args, $assoc_args ) {
		$checkup = AS_PHP_Checkup::get_instance();
		$system_info = $checkup->get_system_info();
		
		// Get format
		$format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		
		if ( 'json' === $format || 'yaml' === $format ) {
			WP_CLI\Utils\format_items( $format, array( $system_info ), array_keys( $system_info ) );
			return;
		}
		
		// WordPress Info
		WP_CLI::log( WP_CLI::colorize( '%B' . __( 'WordPress Information:', 'as-php-checkup' ) . '%n' ) );
		$wp_data = array();
		foreach ( $system_info['wordpress'] as $key => $value ) {
			$wp_data[] = array(
				'property' => ucwords( str_replace( '_', ' ', $key ) ),
				'value'    => is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : $value,
			);
		}
		WP_CLI\Utils\format_items( 'table', $wp_data, array( 'property', 'value' ) );
		
		// Server Info
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%B' . __( 'Server Information:', 'as-php-checkup' ) . '%n' ) );
		$server_data = array();
		foreach ( $system_info['server'] as $key => $value ) {
			$server_data[] = array(
				'property' => ucwords( str_replace( '_', ' ', $key ) ),
				'value'    => $value,
			);
		}
		WP_CLI\Utils\format_items( 'table', $server_data, array( 'property', 'value' ) );
		
		// PHP Extensions
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%B' . __( 'PHP Extensions:', 'as-php-checkup' ) . '%n' ) );
		$ext_data = array();
		foreach ( $system_info['php_extensions'] as $extension => $loaded ) {
			$ext_data[] = array(
				'extension' => strtoupper( $extension ),
				'status'    => $loaded ? 
				              WP_CLI::colorize( '%G✓ Loaded%n' ) : 
				              WP_CLI::colorize( '%R✗ Not loaded%n' ),
			);
		}
		WP_CLI\Utils\format_items( 'table', $ext_data, array( 'extension', 'status' ) );
	}

	/**
	 * Export PHP checkup report
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Export format.
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - csv
	 * ---
	 *
	 * [--output=<file>]
	 * : Output file path. If not specified, outputs to stdout.
	 *
	 * ## EXAMPLES
	 *
	 *     wp as-php-checkup export
	 *     wp as-php-checkup export --format=csv --output=report.csv
	 *     wp as-php-checkup export --format=json --output=/path/to/report.json
	 *
	 * @since 1.1.0
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function export( $args, $assoc_args ) {
		$format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'json' );
		$output = WP_CLI\Utils\get_flag_value( $assoc_args, 'output', null );
		
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		$system_info = $checkup->get_system_info();
		
		$analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$plugin_analysis = $analyzer->get_analyzed_data();
		
		if ( 'csv' === $format ) {
			$content = $this->generate_csv_report( $results, $system_info, $plugin_analysis );
		} else {
			$export_data = array(
				'report'          => 'AS PHP Checkup Report',
				'version'         => AS_PHP_CHECKUP_VERSION,
				'generated'       => current_time( 'mysql' ),
				'results'         => $results,
				'system_info'     => $system_info,
				'plugin_analysis' => $plugin_analysis,
			);
			$content = wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		}
		
		if ( $output ) {
			// Save to file
			$result = file_put_contents( $output, $content );
			if ( false === $result ) {
				WP_CLI::error( sprintf(
					/* translators: %s: file path */
					__( 'Failed to write to file: %s', 'as-php-checkup' ),
					$output
				) );
			} else {
				WP_CLI::success( sprintf(
					/* translators: %s: file path */
					__( 'Report exported to: %s', 'as-php-checkup' ),
					$output
				) );
			}
		} else {
			// Output to stdout
			WP_CLI::log( $content );
		}
	}

	/**
	 * Check for optimal configuration
	 *
	 * ## EXAMPLES
	 *
	 *     wp as-php-checkup check
	 *
	 * @since 1.1.0
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function check( $args, $assoc_args ) {
		$checkup = AS_PHP_Checkup::get_instance();
		$results = $checkup->get_check_results();
		
		$has_warnings = false;
		$has_acceptable = false;
		$warnings = array();
		$acceptables = array();
		
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				if ( 'warning' === $item['status'] ) {
					$has_warnings = true;
					$warnings[] = $item['label'];
				} elseif ( 'acceptable' === $item['status'] ) {
					$has_acceptable = true;
					$acceptables[] = $item['label'];
				}
			}
		}
		
		if ( $has_warnings ) {
			WP_CLI::warning( sprintf(
				/* translators: %s: comma-separated list of settings */
				__( 'The following settings need attention: %s', 'as-php-checkup' ),
				implode( ', ', $warnings )
			) );
			
			// Exit with error code
			exit( 1 );
		} elseif ( $has_acceptable ) {
			WP_CLI::log( WP_CLI::colorize( '%Y' . sprintf(
				/* translators: %s: comma-separated list of settings */
				__( 'The following settings are acceptable but could be improved: %s', 'as-php-checkup' ),
				implode( ', ', $acceptables )
			) . '%n' ) );
			
			// Exit with warning code
			exit( 0 );
		} else {
			WP_CLI::success( __( 'All PHP settings are optimal!', 'as-php-checkup' ) );
			exit( 0 );
		}
	}

	/**
	 * Show summary of check results
	 *
	 * @since 1.1.0
	 * @param array $results Check results.
	 * @return void
	 */
	private function show_summary( $results ) {
		$optimal = 0;
		$acceptable = 0;
		$warning = 0;
		
		foreach ( $results as $category ) {
			foreach ( $category['items'] as $item ) {
				switch ( $item['status'] ) {
					case 'optimal':
						$optimal++;
						break;
					case 'acceptable':
						$acceptable++;
						break;
					case 'warning':
						$warning++;
						break;
				}
			}
		}
		
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%B' . __( 'Summary:', 'as-php-checkup' ) . '%n' ) );
		WP_CLI::log( WP_CLI::colorize( sprintf(
			'  %G✓ Optimal: %d%n  %Y! Acceptable: %d%n  %R✗ Needs Attention: %d%n',
			$optimal,
			$acceptable,
			$warning
		) ) );
		
		$total = $optimal + $acceptable + $warning;
		$health_score = $total > 0 ? round( ( ( $optimal * 100 ) + ( $acceptable * 50 ) ) / $total ) : 0;
		
		WP_CLI::log( '' );
		WP_CLI::log( sprintf(
			/* translators: %d: health score percentage */
			__( 'Health Score: %d%%', 'as-php-checkup' ),
			$health_score
		) );
	}

	/**
	 * Generate CSV report
	 *
	 * @since 1.1.0
	 * @param array $results Check results.
	 * @param array $system_info System information.
	 * @param array $plugin_analysis Plugin analysis data.
	 * @return string
	 */
	private function generate_csv_report( $results, $system_info, $plugin_analysis ) {
		$csv = array();
		
		// Header
		$csv[] = 'AS PHP Checkup Report';
		$csv[] = 'Generated: ' . current_time( 'mysql' );
		$csv[] = 'Version: ' . AS_PHP_CHECKUP_VERSION;
		$csv[] = '';
		
		// PHP Settings
		$csv[] = 'PHP SETTINGS';
		$csv[] = 'Category,Setting,Current Value,Recommended,Minimum,Status,Required By';
		
		foreach ( $results as $category_key => $category ) {
			foreach ( $category['items'] as $key => $item ) {
				$csv[] = sprintf(
					'"%s","%s","%s","%s","%s","%s","%s"',
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
		
		// Plugin Analysis
		if ( ! empty( $plugin_analysis ) ) {
			$csv[] = '';
			$csv[] = 'PLUGIN REQUIREMENTS';
			$csv[] = 'Plugin,Requirement,Value';
			
			foreach ( $plugin_analysis as $plugin_file => $requirements ) {
				foreach ( $requirements as $key => $value ) {
					if ( 'name' !== $key ) {
						$csv[] = sprintf(
							'"%s","%s","%s"',
							$requirements['name'],
							$key,
							$value
						);
					}
				}
			}
		}
		
		// System Info
		$csv[] = '';
		$csv[] = 'SYSTEM INFORMATION';
		$csv[] = 'Component,Property,Value';
		
		// WordPress info
		foreach ( $system_info['wordpress'] as $key => $value ) {
			$label = ucwords( str_replace( '_', ' ', $key ) );
			$display_value = is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : $value;
			$csv[] = sprintf( '"%s","%s","%s"', 'WordPress', $label, $display_value );
		}
		
		// Server info
		foreach ( $system_info['server'] as $key => $value ) {
			$label = ucwords( str_replace( '_', ' ', $key ) );
			$csv[] = sprintf( '"%s","%s","%s"', 'Server', $label, $value );
		}
		
		// PHP Extensions
		foreach ( $system_info['php_extensions'] as $extension => $loaded ) {
			$csv[] = sprintf( '"%s","%s","%s"', 'PHP Extension', strtoupper( $extension ), $loaded ? 'Loaded' : 'Not Loaded' );
		}
		
		return implode( "\n", $csv );
	}
}