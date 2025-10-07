<?php
/**
 * PHPUnit Test Suite for AS_PHP_Checkup
 *
 * @package AS_PHP_Checkup
 * @subpackage Tests
 * @since 1.1.0
 */

/**
 * Test_AS_PHP_Checkup class
 *
 * @since 1.1.0
 */
class Test_AS_PHP_Checkup extends WP_UnitTestCase {

	/**
	 * Checkup instance
	 *
	 * @var AS_PHP_Checkup
	 */
	protected $checkup;

	/**
	 * Plugin analyzer instance
	 *
	 * @var AS_PHP_Checkup_Plugin_Analyzer
	 */
	protected $analyzer;

	/**
	 * Setup test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Get instances
		$this->checkup = AS_PHP_Checkup::get_instance();
		$this->analyzer = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
	}

	/**
	 * Test singleton pattern
	 *
	 * @return void
	 */
	public function test_singleton_pattern() {
		$instance1 = AS_PHP_Checkup::get_instance();
		$instance2 = AS_PHP_Checkup::get_instance();
		
		$this->assertSame( $instance1, $instance2, 'Singleton should return same instance' );
	}

	/**
	 * Test convert_to_bytes method
	 *
	 * @dataProvider provider_memory_values
	 * @param string $input Input value.
	 * @param int    $expected Expected bytes.
	 * @return void
	 */
	public function test_convert_to_bytes( $input, $expected ) {
		$reflection = new ReflectionClass( $this->checkup );
		$method = $reflection->getMethod( 'convert_to_bytes' );
		$method->setAccessible( true );
		
		$result = $method->invoke( $this->checkup, $input );
		
		$this->assertEquals( $expected, $result, "Failed converting {$input} to bytes" );
	}

	/**
	 * Provider for memory values
	 *
	 * @return array
	 */
	public function provider_memory_values() {
		return array(
			array( '1024', 1024 ),
			array( '1K', 1024 ),
			array( '1k', 1024 ),
			array( '1M', 1024 * 1024 ),
			array( '1m', 1024 * 1024 ),
			array( '1G', 1024 * 1024 * 1024 ),
			array( '1g', 1024 * 1024 * 1024 ),
			array( '256M', 256 * 1024 * 1024 ),
			array( '512M', 512 * 1024 * 1024 ),
			array( '768M', 768 * 1024 * 1024 ),
			array( '', 0 ),
			array( '0', 0 ),
		);
	}

	/**
	 * Test check_value method for version comparison
	 *
	 * @return void
	 */
	public function test_check_value_version() {
		// Test optimal
		$status = $this->checkup->check_value( '8.3.0', '8.3', '7.4', 'version' );
		$this->assertEquals( 'optimal', $status, 'Version 8.3.0 should be optimal' );
		
		// Test acceptable
		$status = $this->checkup->check_value( '7.4.0', '8.3', '7.4', 'version' );
		$this->assertEquals( 'acceptable', $status, 'Version 7.4.0 should be acceptable' );
		
		// Test warning
		$status = $this->checkup->check_value( '7.2.0', '8.3', '7.4', 'version' );
		$this->assertEquals( 'warning', $status, 'Version 7.2.0 should be warning' );
	}

	/**
	 * Test check_value method for memory comparison
	 *
	 * @return void
	 */
	public function test_check_value_memory() {
		// Test optimal
		$status = $this->checkup->check_value( '768M', '768M', '256M', 'memory' );
		$this->assertEquals( 'optimal', $status, '768M should be optimal' );
		
		// Test acceptable
		$status = $this->checkup->check_value( '256M', '768M', '256M', 'memory' );
		$this->assertEquals( 'acceptable', $status, '256M should be acceptable' );
		
		// Test warning
		$status = $this->checkup->check_value( '128M', '768M', '256M', 'memory' );
		$this->assertEquals( 'warning', $status, '128M should be warning' );
	}

	/**
	 * Test check_value method for integer comparison
	 *
	 * @return void
	 */
	public function test_check_value_integer() {
		// Test optimal
		$status = $this->checkup->check_value( '6000', 6000, 3000, 'integer' );
		$this->assertEquals( 'optimal', $status, '6000 should be optimal' );
		
		// Test acceptable
		$status = $this->checkup->check_value( '3000', 6000, 3000, 'integer' );
		$this->assertEquals( 'acceptable', $status, '3000 should be acceptable' );
		
		// Test warning
		$status = $this->checkup->check_value( '1000', 6000, 3000, 'integer' );
		$this->assertEquals( 'warning', $status, '1000 should be warning' );
	}

	/**
	 * Test check_value method for boolean comparison
	 *
	 * @return void
	 */
	public function test_check_value_boolean() {
		// Test optimal
		$status = $this->checkup->check_value( 1, 1, 1, 'boolean' );
		$this->assertEquals( 'optimal', $status, '1 should be optimal when 1 is recommended' );
		
		// Test warning
		$status = $this->checkup->check_value( 0, 1, 1, 'boolean' );
		$this->assertEquals( 'warning', $status, '0 should be warning when 1 is recommended' );
	}

	/**
	 * Test get_current_settings returns array
	 *
	 * @return void
	 */
	public function test_get_current_settings() {
		$settings = $this->checkup->get_current_settings();
		
		$this->assertIsArray( $settings, 'Settings should be an array' );
		$this->assertArrayHasKey( 'php_version', $settings, 'Should have php_version key' );
		$this->assertArrayHasKey( 'memory_limit', $settings, 'Should have memory_limit key' );
		$this->assertArrayHasKey( 'max_input_vars', $settings, 'Should have max_input_vars key' );
	}

	/**
	 * Test get_check_results structure
	 *
	 * @return void
	 */
	public function test_get_check_results_structure() {
		$results = $this->checkup->get_check_results();
		
		$this->assertIsArray( $results, 'Results should be an array' );
		
		// Check categories exist
		$this->assertArrayHasKey( 'basic', $results, 'Should have basic category' );
		$this->assertArrayHasKey( 'session', $results, 'Should have session category' );
		$this->assertArrayHasKey( 'opcache', $results, 'Should have opcache category' );
		$this->assertArrayHasKey( 'performance', $results, 'Should have performance category' );
		
		// Check basic category structure
		$basic = $results['basic'];
		$this->assertArrayHasKey( 'label', $basic, 'Category should have label' );
		$this->assertArrayHasKey( 'items', $basic, 'Category should have items' );
		
		// Check item structure
		$first_item = reset( $basic['items'] );
		$this->assertArrayHasKey( 'label', $first_item, 'Item should have label' );
		$this->assertArrayHasKey( 'current', $first_item, 'Item should have current' );
		$this->assertArrayHasKey( 'recommended', $first_item, 'Item should have recommended' );
		$this->assertArrayHasKey( 'minimum', $first_item, 'Item should have minimum' );
		$this->assertArrayHasKey( 'status', $first_item, 'Item should have status' );
		$this->assertArrayHasKey( 'description', $first_item, 'Item should have description' );
	}

	/**
	 * Test get_system_info structure
	 *
	 * @return void
	 */
	public function test_get_system_info_structure() {
		$info = $this->checkup->get_system_info();
		
		$this->assertIsArray( $info, 'System info should be an array' );
		
		// Check main sections
		$this->assertArrayHasKey( 'wordpress', $info, 'Should have wordpress section' );
		$this->assertArrayHasKey( 'server', $info, 'Should have server section' );
		$this->assertArrayHasKey( 'php_extensions', $info, 'Should have php_extensions section' );
		
		// Check WordPress info
		$wp = $info['wordpress'];
		$this->assertArrayHasKey( 'version', $wp, 'WordPress should have version' );
		$this->assertArrayHasKey( 'multisite', $wp, 'WordPress should have multisite' );
		$this->assertArrayHasKey( 'memory_limit', $wp, 'WordPress should have memory_limit' );
		
		// Check server info
		$server = $info['server'];
		$this->assertArrayHasKey( 'php_version', $server, 'Server should have php_version' );
		$this->assertArrayHasKey( 'mysql_version', $server, 'Server should have mysql_version' );
		
		// Check PHP extensions
		$extensions = $info['php_extensions'];
		$this->assertArrayHasKey( 'curl', $extensions, 'Should check curl extension' );
		$this->assertArrayHasKey( 'json', $extensions, 'Should check json extension' );
		$this->assertArrayHasKey( 'mbstring', $extensions, 'Should check mbstring extension' );
	}

	/**
	 * Test plugin analyzer singleton
	 *
	 * @return void
	 */
	public function test_analyzer_singleton() {
		$instance1 = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		$instance2 = AS_PHP_Checkup_Plugin_Analyzer::get_instance();
		
		$this->assertSame( $instance1, $instance2, 'Analyzer singleton should return same instance' );
	}

	/**
	 * Test analyzer get_combined_requirements
	 *
	 * @return void
	 */
	public function test_analyzer_get_combined_requirements() {
		$combined = $this->analyzer->get_combined_requirements();
		
		$this->assertIsArray( $combined, 'Combined requirements should be an array' );
		$this->assertArrayHasKey( 'sources', $combined, 'Should have sources tracking' );
	}

	/**
	 * Test REST controller instantiation
	 *
	 * @return void
	 */
	public function test_rest_controller_instance() {
		$controller = AS_PHP_Checkup_REST_Controller::get_instance();
		
		$this->assertInstanceOf( 'AS_PHP_Checkup_REST_Controller', $controller, 'Should return REST controller instance' );
		
		// Test singleton
		$controller2 = AS_PHP_Checkup_REST_Controller::get_instance();
		$this->assertSame( $controller, $controller2, 'REST controller should be singleton' );
	}

	/**
	 * Test CLI command exists when WP_CLI is available
	 *
	 * @return void
	 */
	public function test_cli_command_class_exists() {
		// Load the CLI command file
		require_once AS_PHP_CHECKUP_PLUGIN_DIR . 'includes/class-cli-command.php';
		
		$this->assertTrue( class_exists( 'AS_PHP_Checkup_CLI_Command' ), 'CLI command class should exist' );
	}

	/**
	 * Test plugin activation creates options
	 *
	 * @return void
	 */
	public function test_plugin_activation() {
		// Delete options first
		delete_option( 'as_php_checkup_last_check' );
		
		// Run activation
		as_php_checkup_activate();
		
		// Check option was created
		$last_check = get_option( 'as_php_checkup_last_check' );
		$this->assertNotFalse( $last_check, 'Last check option should be created on activation' );
		
		// Check cron was scheduled
		$timestamp = wp_next_scheduled( 'as_php_checkup_daily_analysis' );
		$this->assertNotFalse( $timestamp, 'Daily analysis cron should be scheduled' );
	}

	/**
	 * Test plugin deactivation clears scheduled tasks
	 *
	 * @return void
	 */
	public function test_plugin_deactivation() {
		// Schedule event first
		wp_schedule_event( time(), 'daily', 'as_php_checkup_daily_analysis' );
		
		// Run deactivation
		as_php_checkup_deactivate();
		
		// Check cron was cleared
		$timestamp = wp_next_scheduled( 'as_php_checkup_daily_analysis' );
		$this->assertFalse( $timestamp, 'Daily analysis cron should be cleared on deactivation' );
	}

	/**
	 * Test admin class instantiation
	 *
	 * @return void
	 */
	public function test_admin_class() {
		$admin = AS_PHP_Checkup_Admin::get_instance();
		
		$this->assertInstanceOf( 'AS_PHP_Checkup_Admin', $admin, 'Should return admin instance' );
		
		// Test singleton
		$admin2 = AS_PHP_Checkup_Admin::get_instance();
		$this->assertSame( $admin, $admin2, 'Admin should be singleton' );
	}
}